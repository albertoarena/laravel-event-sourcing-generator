<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Traversers;

use Albertoarena\LaravelEventSourcingGenerator\Concerns\HasBlueprintColumnType;
use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Contracts\BlueprintUnsupportedInterface;
use Albertoarena\LaravelEventSourcingGenerator\Exceptions\ParserFailedException;
use Albertoarena\LaravelEventSourcingGenerator\Exceptions\UpdateMigrationIsNotSupportedException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

class BlueprintClassNodeVisitor extends NodeVisitorAbstract
{
    use HasBlueprintColumnType;

    public function __construct(
        protected NodeTraverser $createSchemaNodeTraverser,
        protected array $injectProperties,
        protected array $options,
    ) {}

    /**
     * @throws ParserFailedException
     */
    protected function createChainedMethodCall(array $chain): Node\Expr\MethodCall
    {
        $last = array_pop($chain);
        $name = $last['name'] ?? null;
        if (! $name) {
            throw new ParserFailedException('Invalid chained method');
        }
        $args = $last['args'] ?? [];
        if (! is_array($args)) {
            $args = [$args];
        }

        return new Node\Expr\MethodCall(
            ! count($chain) ?
                new Node\Expr\Variable('table') :
                $this->createChainedMethodCall($chain),
            $name,
            Arr::map($args, function ($arg) {
                return new Node\Arg(
                    new Node\Scalar\String_($arg)
                );
            })
        );
    }

    protected function handleInjectProperties(Node\Expr\Closure $closure): void
    {
        // Inject properties
        if ($this->injectProperties) {
            // Leave timestamps as the last item
            $timestampsExpr = null;
            /** @var Node\Stmt\Expression $lastStatement */
            $lastStatement = Arr::last($closure->stmts);
            if ($lastStatement->expr instanceof Node\Expr\MethodCall) {
                if ($lastStatement->expr->name->name === 'timestamps') {
                    $timestampsExpr = array_pop($closure->stmts);
                }
            }

            foreach ($this->injectProperties as $variableName => $type) {
                $nullable = false;
                if (Str::startsWith($type, '?')) {
                    $nullable = true;
                    $type = Str::after($type, '?');
                }

                // Exclude non supported methods
                if (in_array($type, BlueprintUnsupportedInterface::SKIPPED_METHODS, true)) {
                    continue;
                }

                // Exclude non-supported columns
                if (in_array($type, BlueprintUnsupportedInterface::UNSUPPORTED_COLUMN_TYPES, true)) {
                    continue;
                }

                if ($nullable) {
                    $newExpression = new Node\Stmt\Expression(
                        new Node\Expr\MethodCall(
                            new Node\Expr\MethodCall(
                                new Node\Expr\Variable('table'),
                                new Node\Identifier($this->builtInTypeToColumnType($type)),
                                [
                                    new Node\Arg(
                                        new Node\Scalar\String_($variableName)
                                    ),
                                ]
                            ),
                            'nullable'
                        )
                    );
                } else {
                    $newExpression = new Node\Stmt\Expression(
                        new Node\Expr\MethodCall(
                            new Node\Expr\Variable('table'),
                            new Node\Identifier($this->builtInTypeToColumnType($type)),
                            [
                                new Node\Arg(
                                    new Node\Scalar\String_($variableName)
                                ),
                            ]
                        )
                    );
                }

                $closure->stmts[] = $newExpression;
            }

            if ($timestampsExpr) {
                $closure->stmts[] = $timestampsExpr;
            }
        }

        // @phpstan-ignore assign.propertyType
        $closure->stmts = $this->createSchemaNodeTraverser->traverse($closure->stmts);
    }

    /**
     * @throws ParserFailedException
     */
    protected function handleReplacements(Node\Expr\Closure $closure): void
    {
        // Get primary key
        $primaryKey = $this->options[':primary'] ?? null;
        if ($primaryKey) {
            $primaryKeyArgs = [];
            if (is_array($primaryKey)) {
                $primaryKeyArgs = array_slice($primaryKey, 1);
                $primaryKey = $primaryKey[0] ?? null;
            }

            // Update primary key
            /** @var Node\Stmt\Expression $firstStatement */
            $firstStatement = Arr::first($closure->stmts);
            if ($firstStatement->expr instanceof Node\Expr\MethodCall) {
                if ($primaryKey && $firstStatement->expr->name->name !== $primaryKey) {
                    $firstStatement->expr->name->name = $primaryKey;
                    if ($primaryKeyArgs) {
                        foreach ($primaryKeyArgs as $primaryKeyArg) {
                            $newArg = new Node\Arg(
                                new Node\Scalar\String_($primaryKeyArg),
                            );

                            $firstStatement->expr->args[] = $newArg;
                        }
                    }
                }
            }
        }

        // Inject custom method calls
        $injects = $this->options[':injects'] ?? [];
        foreach ($injects as $inject) {
            $chain = Arr::map($inject, fn ($value, $key) => ['name' => $key, 'args' => $value]);
            $newExpression = new Node\Stmt\Expression(
                $this->createChainedMethodCall($chain)
            );

            $closure->stmts[] = $newExpression;
        }

        // @phpstan-ignore assign.propertyType
        $closure->stmts = $this->createSchemaNodeTraverser->traverse($closure->stmts);
    }

    /**
     * @throws UpdateMigrationIsNotSupportedException
     * @throws ParserFailedException
     */
    public function enterNode(Node $node): ?Node
    {
        if ($node instanceof Node\Stmt\Class_) {
            $upMethod = $node->getMethod('up');
            if (! $upMethod) {
                return $node;
            }

            /** @var Node\Stmt\Expression $expression */
            $expression = $upMethod->getStmts()[0] ?? null;
            if (! $expression instanceof Node\Stmt\Expression) {
                return $node;
            }

            if ($expression->expr instanceof Node\Expr\StaticCall) {
                if ($expression->expr->class->name === 'Schema') {
                    if ($expression->expr->name->name === 'create') {
                        // Look for Blueprint table definition
                        foreach ($expression->expr->args as $arg) {
                            if ($arg->value instanceof Node\Expr\Closure) {
                                if ($arg->value->params && $arg->value->params[0] instanceof Node\Param) {
                                    if ($arg->value->params[0]->type->name === 'Blueprint') {
                                        // Inject properties
                                        $this->handleInjectProperties($arg->value);

                                        // Handle replacements
                                        $this->handleReplacements($arg->value);
                                    }
                                }
                            }
                        }
                    } elseif ($expression->expr->name->name === 'table') {
                        throw new UpdateMigrationIsNotSupportedException;
                    }
                }
            }
        }

        return $node;
    }
}

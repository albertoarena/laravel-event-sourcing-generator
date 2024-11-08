<?php

namespace Tests\Domain\PhpParser\Traversers;

use Albertoarena\LaravelEventSourcingGenerator\Domain\Blueprint\Concerns\HasBlueprintColumnType;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Blueprint\Contracts\BlueprintUnsupportedInterface;
use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Concerns\HasSchemaUpNode;
use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Models\EnterNode;
use Albertoarena\LaravelEventSourcingGenerator\Exceptions\ParserFailedException;
use Albertoarena\LaravelEventSourcingGenerator\Exceptions\UpdateMigrationIsNotSupportedException;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Tests\Domain\Migrations\Contracts\MigrationOptionInterface;

class BlueprintClassModifyNodeVisitor extends NodeVisitorAbstract
{
    use HasBlueprintColumnType;
    use HasSchemaUpNode;

    public function __construct(
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

    protected function createMethodCall(string $type, ?string $variableName = null, array $args = []): Node\Expr\MethodCall
    {
        return new Node\Expr\MethodCall(
            new Node\Expr\Variable('table'),
            new Node\Identifier($this->builtInTypeToColumnType($type)),
            array_merge($variableName ? [
                new Node\Arg(
                    new Node\Scalar\String_($variableName)
                ),
            ] : [], $args)
        );
    }

    protected function getMethodCallArgsByType(string $type): array
    {
        return match ($type) {
            'enum', 'set' => [
                new Node\Arg(
                    new Node\Expr\Array_([
                        new Node\ArrayItem(
                            new Node\Scalar\String_(Str::random(6))
                        ),
                        new Node\ArrayItem(
                            new Node\Scalar\String_(Str::random(6))
                        ),
                        new Node\ArrayItem(
                            new Node\Scalar\String_(Str::random(6))
                        ),
                    ])
                ),
            ],
            default => []
        };
    }

    protected function handleInjectProperties(Node\Expr\Closure $closure): self
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

                if ($nullable) {
                    // Handle nullable expression
                    $newExpression = new Node\Stmt\Expression(
                        new Node\Expr\MethodCall(
                            $this->createMethodCall($type, $variableName, $this->getMethodCallArgsByType($type)),
                            'nullable'
                        )
                    );
                } else {
                    // Handle normal expression
                    $newExpression = new Node\Stmt\Expression(
                        $this->createMethodCall($type, $variableName, $this->getMethodCallArgsByType($type))
                    );
                }

                $closure->stmts[] = $newExpression;
            }

            if ($timestampsExpr) {
                $closure->stmts[] = $timestampsExpr;
            }
        }

        return $this;
    }

    protected function getPrimaryKey(string|array|null $primaryKey): array
    {
        $primaryKeyArgs = [];

        // Handle array primary key, e.g. ['bigIncrements' => 'id'] --> $table->bigIncrements('id');
        if (is_array($primaryKey)) {
            $primaryKeyArgs = array_values($primaryKey)[0];
            if (! is_array($primaryKeyArgs)) {
                $primaryKeyArgs = [$primaryKeyArgs];
            }
            $primaryKey = array_keys($primaryKey)[0];
        }

        return [$primaryKey, $primaryKeyArgs];
    }

    /**
     * @throws ParserFailedException
     * @throws Exception
     */
    protected function handleReplacements(Node\Expr\Closure $closure): void
    {
        // Get primary key
        $primaryKey = $this->options[MigrationOptionInterface::PRIMARY_KEY] ?? null;
        if ($primaryKey) {

            // Handle array primary key, e.g. ['bigIncrements' => 'id'] --> $table->bigIncrements('id');
            [$primaryKey, $primaryKeyArgs] = $this->getPrimaryKey($primaryKey);

            // Update primary key
            /** @var Node\Stmt\Expression $firstStatement */
            $firstStatement = Arr::first($closure->stmts);
            if ($firstStatement->expr instanceof Node\Expr\MethodCall) {
                if ($primaryKey && $firstStatement->expr->name->name !== $primaryKey) {
                    if ($primaryKey === 'uuid') {
                        // Handle uuid primary key, e.g. 'uuid' --> $table->uuid('uuid')->primary();
                        $firstStatement->expr = $this->createChainedMethodCall([
                            ['name' => 'uuid', 'args' => $primaryKeyArgs],
                            ['name' => 'primary'],
                        ]);
                    } else {
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
        }

        // Inject custom method calls
        $injects = $this->options[MigrationOptionInterface::INJECTS] ?? [];
        foreach ($injects as $inject) {
            $chain = Arr::map($inject, fn ($value, $key) => ['name' => $key, 'args' => $value]);
            $newExpression = new Node\Stmt\Expression(
                $this->createChainedMethodCall($chain)
            );

            $closure->stmts[] = $newExpression;
        }

        // Inject soft deletes
        $softDeletes = $this->options[MigrationOptionInterface::SOFT_DELETES] ?? false;
        if ($softDeletes) {
            $closure->stmts[] = new Node\Stmt\Expression(
                $this->createMethodCall(is_string($softDeletes) ? $softDeletes : 'softDeletes')
            );
        }
    }

    /**
     * @throws UpdateMigrationIsNotSupportedException
     * @throws ParserFailedException
     */
    public function enterNode(Node $node): ?Node
    {
        return $this->enterSchemaUpNode(
            $node,
            new EnterNode(
                function (Node\Stmt\Expression $expression) {
                    if ($expression->expr instanceof Node\Expr\StaticCall) {
                        if ($expression->expr->class->name === 'Schema') {
                            if ($expression->expr->name->name === 'create') {
                                // Look for Blueprint table definition
                                foreach ($expression->expr->args as $arg) {
                                    if ($arg->value instanceof Node\Expr\Closure) {
                                        if ($arg->value->params && $arg->value->params[0] instanceof Node\Param) {
                                            if ($arg->value->params[0]->type->name === 'Blueprint') {
                                                // Inject properties and handle replacements
                                                $this->handleInjectProperties($arg->value)
                                                    ->handleReplacements($arg->value);
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
            )
        );
    }
}

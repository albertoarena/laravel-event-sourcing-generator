<?php

namespace Albertoarena\LaravelDomainGenerator\Domain\PhpParser\Traversers;

use Albertoarena\LaravelDomainGenerator\Concerns\HasBlueprintColumnType;
use Illuminate\Support\Arr;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

class BlueprintClassNodeVisitor extends NodeVisitorAbstract
{
    use HasBlueprintColumnType;

    public function __construct(
        protected NodeTraverser $createSchemaNodeTraverser,
        protected string $injectPrimaryKey,
        protected array $injectProperties,
    ) {}

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
                if ($expression->expr->class->name === 'Schema' && $expression->expr->name->name === 'create') {
                    // Look for Blueprint table definition
                    foreach ($expression->expr->args as $arg) {
                        if ($arg->value instanceof Node\Expr\Closure) {
                            if ($arg->value->params && $arg->value->params[0] instanceof Node\Param) {
                                if ($arg->value->params[0]->type->name === 'Blueprint') {
                                    // Inject properties
                                    if ($this->injectProperties) {
                                        // Update primary key
                                        /** @var Node\Stmt\Expression $firstStatement */
                                        $firstStatement = Arr::first($arg->value->stmts);
                                        if ($firstStatement->expr instanceof Node\Expr\MethodCall) {
                                            if ($firstStatement->expr->name->name !== $this->injectPrimaryKey) {
                                                $firstStatement->expr->name->name = $this->injectPrimaryKey;
                                            }
                                        }

                                        // Leave timestamps as the last item
                                        $timestampsExpr = null;
                                        /** @var Node\Stmt\Expression $lastStatement */
                                        $lastStatement = Arr::last($arg->value->stmts);
                                        if ($lastStatement->expr instanceof Node\Expr\MethodCall) {
                                            if ($lastStatement->expr->name->name === 'timestamps') {
                                                $timestampsExpr = array_pop($arg->value->stmts);
                                            }
                                        }

                                        foreach ($this->injectProperties as $variableName => $type) {
                                            $newExpression = new Node\Stmt\Expression(
                                                new Node\Expr\MethodCall(
                                                    new Node\Expr\Variable('table'),
                                                    new Node\Identifier($this->getBlueprintColumnType($type)),
                                                    [
                                                        new Node\Arg(
                                                            new Node\Scalar\String_($variableName)
                                                        ),
                                                    ]
                                                )
                                            );

                                            $arg->value->stmts[] = $newExpression;
                                        }

                                        if ($timestampsExpr) {
                                            $arg->value->stmts[] = $timestampsExpr;
                                        }
                                    }

                                    // @phpstan-ignore assign.propertyType
                                    $arg->value->stmts = $this->createSchemaNodeTraverser->traverse($arg->value->stmts);
                                }
                            }
                        }
                    }
                }
            }
        }

        return $node;
    }
}

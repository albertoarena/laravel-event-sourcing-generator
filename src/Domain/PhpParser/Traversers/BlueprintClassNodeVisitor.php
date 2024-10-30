<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Traversers;

use Albertoarena\LaravelEventSourcingGenerator\Domain\Blueprint\Contracts\BlueprintUnsupportedInterface;
use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Concerns\HasSchemaUpNode;
use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Models\EnterNode;
use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Models\MigrationCreateProperty;
use Albertoarena\LaravelEventSourcingGenerator\Exceptions\UpdateMigrationIsNotSupportedException;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class BlueprintClassNodeVisitor extends NodeVisitorAbstract
{
    use HasSchemaUpNode;

    protected array $ignored;

    public function __construct(
        protected array &$properties
    ) {
        $this->ignored = array_merge(
            BlueprintUnsupportedInterface::SKIPPED_METHODS,
            BlueprintUnsupportedInterface::UNSUPPORTED_COLUMN_TYPES
        );
    }

    /**
     * @throws UpdateMigrationIsNotSupportedException
     */
    public function enterNode(Node $node): ?Node
    {
        return $this->enterSchemaUpNode(
            $node,
            new EnterNode(
                function (Node\Stmt\Expression $expression) {
                    if ($expression->expr instanceof Node\Expr\StaticCall) {
                        if ($expression->expr->class->name === 'Schema') {
                            if ($expression->expr->name->name === 'table') {
                                throw new UpdateMigrationIsNotSupportedException;
                            }
                        }
                    }
                },
                function (Node $node) {
                    if ($node instanceof Node\Stmt\Expression) {
                        // Collect properties from Schema::up method
                        if ($node->expr instanceof Node\Expr\MethodCall) {
                            $property = MigrationCreateProperty::createFromExprMethodCall($node->expr);
                            if (! in_array($property->type, $this->ignored)) {
                                $this->properties[$property->name] = $property;
                            }
                        }
                    }
                }
            )
        );
    }
}

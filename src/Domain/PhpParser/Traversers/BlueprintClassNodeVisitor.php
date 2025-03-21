<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Traversers;

use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Concerns\HasSchemaUpNode;
use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Models\EnterNode;
use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Models\MigrationCreateProperty;
use Albertoarena\LaravelEventSourcingGenerator\Exceptions\MigrationInvalidPrimaryKeyException;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class BlueprintClassNodeVisitor extends NodeVisitorAbstract
{
    use HasSchemaUpNode;

    public function __construct(
        protected array &$properties,
        protected array &$ignored,
        protected ?string $currentMethod = null,
    ) {}

    /**
     * @throws MigrationInvalidPrimaryKeyException
     */
    public function enterNode(Node $node): ?Node
    {
        return $this->enterSchemaUpNode(
            $node,
            new EnterNode(
                function (Node\Stmt\Expression $expression) {
                    // Nope
                },
                function (Node $node) {
                    if ($node instanceof Node\Stmt\ClassMethod) {
                        $this->currentMethod = $node->name->name;
                    }

                    if ($node instanceof Node\Stmt\Expression && $this->currentMethod === 'up') {
                        // Collect properties from Schema::up method
                        if ($node->expr instanceof Node\Expr\MethodCall) {
                            foreach (MigrationCreateProperty::createPropertiesFromExprMethodCall($node->expr) as $property) {
                                if ($property->type->isDropped) {
                                    $this->properties[$property->name] = $property;
                                } elseif ($property->type->renameTo) {
                                    $this->properties[$property->name] = $property;
                                } elseif (! $property->type->isIgnored) {
                                    $this->properties[$property->name] = $property;
                                } elseif (! $property->type->isSkipped) {
                                    $this->ignored[$property->name] = $property;
                                }
                            }
                        }
                    }
                }
            )
        );
    }
}

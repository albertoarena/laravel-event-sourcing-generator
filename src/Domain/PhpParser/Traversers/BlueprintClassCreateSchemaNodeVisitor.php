<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Traversers;

use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Contracts\BlueprintUnsupportedInterface;
use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Models\MigrationCreateProperty;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class BlueprintClassCreateSchemaNodeVisitor extends NodeVisitorAbstract
{
    public function __construct(
        protected array &$properties
    ) {}

    public function enterNode(Node $node): ?Node
    {
        if (! $node instanceof Node\Stmt\Expression) {
            return $node;
        }

        if ($node->expr instanceof Node\Expr\MethodCall) {
            $property = MigrationCreateProperty::createFromExprMethodCall($node->expr);
            if (! in_array($property->type, BlueprintUnsupportedInterface::SKIPPED_METHODS) &&
                ! in_array($property->type, BlueprintUnsupportedInterface::UNSUPPORTED_COLUMN_TYPES)) {
                $this->properties[$property->name] = $property;
            }
        }

        return $node;
    }
}

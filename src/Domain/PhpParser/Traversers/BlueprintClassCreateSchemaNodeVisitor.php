<?php

namespace Albertoarena\LaravelDomainGenerator\Domain\PhpParser\Traversers;

use Albertoarena\LaravelDomainGenerator\Domain\PhpParser\Models\MigrationCreateProperty;
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
            $this->properties[] = MigrationCreateProperty::createFromExprMethodCall($node->expr);
        }

        return $node;
    }
}

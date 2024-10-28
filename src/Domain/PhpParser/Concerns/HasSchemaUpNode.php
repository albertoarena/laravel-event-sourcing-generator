<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Concerns;

use Closure;
use PhpParser\Node;

trait HasSchemaUpNode
{
    protected function enterSchemaUpNode(Node $node, Closure $closure, ?Closure $afterClosure = null): ?Node
    {
        // Check if Schema::up() method exists
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

            $closure($expression);

            return $node;
        }

        if ($afterClosure) {
            $afterClosure($node);
        }

        return $node;
    }
}

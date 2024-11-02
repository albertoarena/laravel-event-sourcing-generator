<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Concerns;

use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Models\EnterNode;
use PhpParser\Node;

trait HasMethodNode
{
    protected function enterMethodNode(Node $node, string $method, EnterNode $enterNode): ?Node
    {
        // Check if method exists
        if ($node instanceof Node\Stmt\Class_) {
            $currentMethod = $node->getMethod($method);
            if (! $currentMethod) {
                return $node;
            }

            /** @var Node\Stmt\Expression $expression */
            $expression = $currentMethod->getStmts()[0] ?? null;
            if (! $expression instanceof Node\Stmt\Expression) {
                return $node;
            }

            ($enterNode->onEnter)($expression);

            return $node;
        }

        if ($enterNode->afterEnter) {
            ($enterNode->afterEnter)($node);
        }

        return $node;
    }
}

<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Concerns;

use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Models\EnterNode;
use PhpParser\Node;

trait HasSchemaUpNode
{
    use HasMethodNode;

    protected function enterSchemaUpNode(Node $node, EnterNode $enterNode): ?Node
    {
        return $this->enterMethodNode($node, 'up', $enterNode);
    }
}

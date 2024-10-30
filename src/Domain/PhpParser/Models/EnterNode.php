<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Models;

use Closure;

class EnterNode
{
    public function __construct(
        public Closure $onEnter,
        public ?Closure $afterEnter = null,
    ) {}
}

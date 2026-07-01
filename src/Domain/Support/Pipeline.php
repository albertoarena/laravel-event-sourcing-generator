<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\Support;

/**
 * A minimal left-to-right value pipeline: process($x) runs $x through each
 * layer in order, passing the result of one layer to the next.
 *
 * Replaces the (abandoned) aldemeery/onion dependency, of which only this
 * simple pipe behaviour was ever used.
 */
final class Pipeline
{
    /** @param  list<callable>  $layers */
    public function __construct(private array $layers = []) {}

    public function process(mixed $passable): mixed
    {
        return array_reduce(
            $this->layers,
            fn (mixed $carry, callable $layer): mixed => $layer($carry),
            $passable,
        );
    }
}

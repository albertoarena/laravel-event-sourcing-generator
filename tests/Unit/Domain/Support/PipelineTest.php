<?php

namespace Tests\Unit\Domain\Support;

use Albertoarena\LaravelEventSourcingGenerator\Domain\Support\Pipeline;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PipelineTest extends TestCase
{
    #[Test]
    public function it_returns_the_passable_unchanged_for_an_empty_pipeline()
    {
        $this->assertSame('abc', (new Pipeline)->process('abc'));
        $this->assertSame(42, (new Pipeline([]))->process(42));
    }

    #[Test]
    public function it_applies_a_single_layer()
    {
        $result = (new Pipeline([
            fn (int $n): int => $n + 1,
        ]))->process(1);

        $this->assertSame(2, $result);
    }

    #[Test]
    public function it_applies_layers_left_to_right()
    {
        // 'a' -> append 'b' -> 'ab' -> uppercase -> 'AB'.
        // Right-to-left would yield 'Ab', so this pins the order.
        $result = (new Pipeline([
            fn (string $s): string => $s.'b',
            fn (string $s): string => strtoupper($s),
        ]))->process('a');

        $this->assertSame('AB', $result);
    }

    #[Test]
    public function it_accepts_any_callable_not_only_closures()
    {
        $invokable = new class
        {
            public function __invoke(string $s): string
            {
                return $s.'!';
            }
        };

        $result = (new Pipeline([
            'strtoupper',   // string callable
            $invokable,     // invokable object
        ]))->process('hi');

        $this->assertSame('HI!', $result);
    }
}

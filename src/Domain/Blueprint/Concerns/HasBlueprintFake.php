<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\Blueprint\Concerns;

use Illuminate\Support\Str;

trait HasBlueprintFake
{
    protected const BLUEPRINT_TO_FAKE = [
        'bool' => 'boolean',
        'int' => 'randomNumber()',
        'float' => 'randomNumber(2)',
        'array' => 'randomElements',
        'Carbon' => 'Carbon::parse($this->faker->date())',
        '?Carbon' => 'Carbon::parse($this->faker->date())',
        'Carbon:Y-m-d' => 'Carbon::parse($this->faker->date())',
        'Carbon:H:i:s' => 'Carbon::parse($this->faker->date())',
        'string' => 'word',
    ];

    protected function blueprintToFakeFunction(string $type): string
    {
        $fakeFunction = self::BLUEPRINT_TO_FAKE[$type] ?? 'word';
        if (! Str::endsWith($fakeFunction, ')')) {
            $fakeFunction .= '()';
        }

        return Str::startsWith($fakeFunction, 'Carbon') ? $fakeFunction : "\$this->faker->$fakeFunction";
    }
}

<?php

namespace Albertoarena\LaravelDomainGenerator\Concerns;

trait HasBlueprintColumnType
{
    protected const COLUMN_TYPES = [
        'bool' => 'boolean',
        'int' => 'integer',
        'float' => 'double',
    ];

    /**
     * If needed, add other column types
     * https://laravel.com/docs/11.x/migrations#available-column-types
     */
    protected function getBlueprintColumnType(string $type): string
    {
        return self::COLUMN_TYPES[$type] ?? $type;
    }

    protected function columnTypeToType(string $type): string
    {
        return array_flip(self::COLUMN_TYPES)[$type] ?? $type;
    }

    protected function columnNameToType(string $name): string
    {
        return match ($name) {
            'id' => 'integer',
            'uuid' => 'string',
            'timestamps' => 'Carbon',
            default => $name
        };
    }
}

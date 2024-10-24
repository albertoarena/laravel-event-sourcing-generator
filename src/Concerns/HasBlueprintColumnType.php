<?php

namespace Albertoarena\LaravelDomainGenerator\Concerns;

trait HasBlueprintColumnType
{
    protected function columnTypeToBuiltInType(string $type): string
    {
        return match ($type) {
            'boolean' => 'bool',
            'bigIncrements', 'bigInteger', 'foreignId', 'id', 'increments', 'integer', 'mediumIncrements', 'mediumInteger', 'smallIncrements', 'smallInteger', 'tinyIncrements', 'tinyInteger', 'unsignedBigInteger', 'unsignedInteger', 'unsignedMediumInteger', 'unsignedSmallInteger', 'unsignedTinyInteger', 'year' => 'int',
            'decimal', 'double' => 'float',
            'json' => 'array',
            'dateTimeTz', 'dateTime', 'softDeletesTz', 'softDeletes', 'timestampTz', 'timestamp', 'timestampsTz', 'timestamps' => 'Carbon',
            'nullableTimestamps' => '?Carbon',
            'date' => 'Carbon:Y-m-d',
            'timeTz', 'time' => 'Carbon:H:i:s',
            'char', 'enum', 'foreignUuid', 'ipAddress', 'longText', 'macAddress', 'mediumText', 'rememberToken', 'text', 'tinyText', 'uuid' => 'string',
            default => $type
        };
    }

    protected function carbonToBuiltInType(string $type): string
    {
        return match ($type) {
            'Carbon' => 'date:Y-m-d H:i:s',
            'Carbon:Y-m-d' => 'date:Y-m-d',
            'Carbon:H:i:s' => 'date:H:i:s',
            default => $type
        };
    }

    protected function normaliseCarbon(string $type): string
    {
        return preg_replace('/Carbon:.*/i', 'Carbon', $type);
    }

    protected function builtInTypeToColumnType(string $type): string
    {
        return match ($type) {
            'bool' => 'boolean',
            'int' => 'integer',
            'float' => 'double',
            default => $type
        };
    }
}

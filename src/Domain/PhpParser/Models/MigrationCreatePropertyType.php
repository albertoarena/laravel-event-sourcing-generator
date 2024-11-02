<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Models;

use Albertoarena\LaravelEventSourcingGenerator\Domain\Blueprint\Concerns\HasBlueprintColumnType;
use Aldemeery\Onion\Onion;
use Illuminate\Support\Str;

class MigrationCreatePropertyType
{
    use HasBlueprintColumnType;

    public readonly bool $nullable;

    public function __construct(
        public string $type,
        bool $nullable = false,
    ) {
        if ($nullable) {
            $this->nullable = $nullable;
        } else {
            // Check if built-in type is nullable (e.g. nullableTimestamps)
            if (Str::startsWith($this->columnTypeToBuiltInType($type), '?')) {
                $this->nullable = true;
            }
            // Check if type starts with ? (nullable)
            else {
                $this->nullable = Str::startsWith($type, '?');
                if ($this->nullable) {
                    $this->type = Str::substr($this->type, 1);
                }
            }
        }
    }

    public function toString(): string
    {
        return $this->type;
    }

    public function setAsBuiltInType(): void
    {
        $this->type = $this->columnTypeToBuiltInType($this->type);
    }

    public function toBuiltInType(): string
    {
        return (new Onion([
            fn ($type) => $this->columnTypeToBuiltInType($type),
            fn ($type) => Str::replaceFirst('?', '', $type),
        ]))->peel($this->type);
    }

    public function toNormalisedBuiltInType(): string
    {
        return (new Onion([
            fn ($type) => $this->toBuiltInType(),
            fn ($type) => $this->normaliseCarbon($type),
        ]))->peel($this->type);
    }

    public function toProjection(): string
    {
        return (new Onion([
            fn ($type) => $this->columnTypeToBuiltInType($type),
            fn ($type) => $this->carbonToBuiltInType($type),
            fn ($type) => Str::replaceFirst('?', '', $type),
        ]))->peel($this->type);
    }
}

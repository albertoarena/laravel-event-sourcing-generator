<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Models;

use Albertoarena\LaravelEventSourcingGenerator\Domain\Blueprint\Concerns\HasBlueprintColumnType;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Blueprint\Contracts\BlueprintUnsupportedInterface;
use Aldemeery\Onion\Onion;
use Illuminate\Support\Str;

class MigrationCreatePropertyType
{
    use HasBlueprintColumnType;

    public bool $nullable;

    public readonly bool $isIgnored;

    public function __construct(
        public string $type,
        bool $nullable = false,
        bool $isIgnored = false,
    ) {
        $this->nullable = false;
        if ($nullable) {
            $this->nullable = $nullable;
        } else {
            // Check if built-in type is nullable (e.g. nullableTimestamps)
            if (Str::startsWith($this->columnTypeToBuiltInType($type), '?')) {
                $this->nullable = true;
            }
        }

        $this->isIgnored = $isIgnored || in_array($this->type, BlueprintUnsupportedInterface::IGNORED);
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

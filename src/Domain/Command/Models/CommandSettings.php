<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\Command\Models;

use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Models\MigrationCreateProperties;
use Illuminate\Support\Str;

class CommandSettings
{
    public readonly string $indentSpace;

    public MigrationCreateProperties $modelProperties;

    protected MigrationCreateProperties $properties;

    public function __construct(
        public readonly string $model,
        public readonly string $domain,
        public readonly string $namespace,
        public ?string $migration,
        public ?bool $createAggregateRoot,
        public ?bool $createReactor,
        public readonly int $indentation,
        public bool $useUuid,
        public string $nameAsPrefix = '',
        public string $domainPath = '',
        public bool $useCarbon = false,
    ) {
        $this->indentSpace = Str::repeat(' ', $this->indentation);
        $this->modelProperties = new MigrationCreateProperties;
    }

    public function primaryKey(): string
    {
        return $this->useUuid ? 'uuid' : 'id';
    }
}

<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\Command\Models;

use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Models\MigrationCreateProperties;
use Illuminate\Support\Str;

class CommandSettings
{
    public readonly string $indentSpace;

    public MigrationCreateProperties $modelProperties;

    public MigrationCreateProperties $ignoredProperties;

    public function __construct(
        public readonly string $model,
        public readonly string $domain,
        public readonly string $namespace,
        public ?string $migration,
        public ?bool $createAggregateRoot,
        public ?bool $createReactor,
        public readonly int $indentation,
        public array $notifications,
        public ?bool $useUuid = null,
        public string $nameAsPrefix = '',
        public string $namespacePath = '',
        public string $domainPath = '',
        public string $testDomainPath = '',
        public bool $useCarbon = false,
        public bool $createUnitTest = false,
        public bool $createFailedEvents = false,
    ) {
        $this->indentSpace = Str::repeat(' ', $this->indentation);
        $this->modelProperties = new MigrationCreateProperties;
        $this->ignoredProperties = new MigrationCreateProperties;
    }

    public function primaryKey(): string
    {
        return $this->useUuid ? 'uuid' : 'id';
    }
}

<?php

namespace Albertoarena\LaravelDomainGenerator\Models;

use Illuminate\Support\Str;

class CommandSettings
{
    public readonly string $indentSpace;

    public function __construct(
        public readonly string $nameInput,
        public readonly string $domainBaseRoot,
        public ?string $migration,
        public ?bool $createAggregateRoot,
        public readonly int $indentation,
        public bool $useUuid,
        public string $domainName = '',
        public string $domainId = '',
        public string $domainPath = '',
        public array $modelProperties = [],
        public bool $useCarbon = false,
    ) {
        $this->indentSpace = Str::repeat(' ', $this->indentation);
    }

    public function primaryKey(): string
    {
        return $this->useUuid ? 'uuid' : 'id';
    }
}

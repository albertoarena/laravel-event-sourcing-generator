<?php

namespace Albertoarena\LaravelDomainGenerator\Domain\PhpParser\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MigrationCreateProperties
{
    public const RESERVED_FIELDS = ['id', 'uuid', 'timestamps'];

    protected Collection $collection;

    public function __construct(array|Collection|null $collection = null)
    {
        $this->collection = new Collection;

        // Import existing collection
        if ($collection) {
            $this->import($collection);
        }
    }

    public function add($property): self
    {
        if ($property instanceof MigrationCreateProperty) {
            // Check if property already exists
            if (! $this->collection->offsetExists($property->name)) {
                $this->collection->offsetSet($property->name, $property);
            }
        }

        return $this;
    }

    public function import(array|Collection $modelProperties): self
    {
        $this->collection = new Collection;
        foreach ($modelProperties as $name => $typeOrProperty) {
            if ($typeOrProperty instanceof MigrationCreateProperty) {
                $this->add($typeOrProperty);
            } else {
                $nullable = Str::startsWith($name, '?');
                $name = $nullable ? Str::substr($name, 1) : $name;

                $this->add(new MigrationCreateProperty(
                    name: $name,
                    type: $typeOrProperty,
                    nullable: $nullable,
                ));
            }
        }

        return $this;
    }

    public function withoutReservedFields(): self
    {
        return new self($this->collection->except(self::RESERVED_FIELDS));
    }

    public function toArray(): array
    {
        return $this->collection->toArray();
    }
}

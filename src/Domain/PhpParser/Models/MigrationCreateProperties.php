<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Models;

use Illuminate\Support\Collection;

class MigrationCreateProperties
{
    public const RESERVED_FIELDS = ['id', 'uuid', 'timestamps'];

    public const PRIMARY_KEY = ['id', 'uuid'];

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
        // Check if property type is correct and if it already exists
        if ($property instanceof MigrationCreateProperty &&
            ! $this->collection->offsetExists($property->name)
        ) {
            $this->collection->offsetSet($property->name, $property);
        }

        return $this;
    }

    public function primary(): MigrationCreateProperty
    {
        return $this->collection->where(
            fn (MigrationCreateProperty $property) => in_array($property->name, self::PRIMARY_KEY)
        )[0] ?? $this->collection->first();
    }

    public function import(array|Collection $modelProperties): self
    {
        $this->collection = new Collection;
        foreach ($modelProperties as $name => $typeOrProperty) {
            if ($typeOrProperty instanceof MigrationCreateProperty) {
                $this->add($typeOrProperty);
            } else {
                $this->add(new MigrationCreateProperty(
                    name: $name,
                    type: $typeOrProperty,
                ));
            }
        }

        return $this;
    }

    public function withoutReservedFields(): self
    {
        return new self($this->collection->except(self::RESERVED_FIELDS));
    }

    /**
     * @return MigrationCreateProperty[]
     */
    public function toArray(): array
    {
        return $this->collection->toArray();
    }
}

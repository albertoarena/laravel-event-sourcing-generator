<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Models;

use Albertoarena\LaravelEventSourcingGenerator\Domain\Blueprint\Contracts\BlueprintUnsupportedInterface;
use Illuminate\Support\Collection;

class MigrationCreateProperties
{
    public const RESERVED_FIELDS = ['id', 'uuid', 'timestamps'];

    public const PRIMARY_KEY = ['id', 'uuid'];

    protected Collection $collection;

    protected Collection $rejected;

    public function __construct(array|Collection|null $collection = null)
    {
        $this->collection = new Collection;
        $this->rejected = new Collection;

        // Import existing collection
        if ($collection) {
            $this->import($collection);
        }
    }

    public function add($property, bool $overwriteIfExists = false): self
    {
        // Check if property type is correct and if it already exists
        if ($property instanceof MigrationCreateProperty) {
            $exists = $this->collection->offsetExists($property->name);
            if ($exists && ! $overwriteIfExists) {
                return $this;
            }

            if ($exists) {
                /** @var MigrationCreateProperty $existing */
                $existing = $this->collection->offsetGet($property->name);
                if ($property->type->isDropped) {
                    // Remove item
                    $this->collection->offsetUnset($property->name);

                    return $this;
                } elseif ($property->type->renameTo) {
                    // Rename item
                    $existing->name = $property->type->renameTo;
                    $this->collection->offsetSet($existing->name, $existing);
                    $this->collection->offsetUnset($property->name);

                    return $this;
                }
            }

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

    public function import(array|Collection $modelProperties, bool $reset = true): self
    {
        if ($reset) {
            $this->collection = new Collection;
        }
        foreach ($modelProperties as $name => $typeOrProperty) {
            if ($typeOrProperty instanceof MigrationCreateProperty) {
                $this->add($typeOrProperty, true);
            } else {
                $this->add(new MigrationCreateProperty(
                    name: $name,
                    type: $typeOrProperty,
                ), true);
            }
        }

        return $this;
    }

    public function withoutReservedFields(): self
    {
        return new self($this->collection->except(self::RESERVED_FIELDS));
    }

    public function withoutSkippedMethods(): self
    {
        return new self($this->collection->except(BlueprintUnsupportedInterface::SKIPPED_METHODS));
    }

    /**
     * @return MigrationCreateProperty[]
     */
    public function toArray(): array
    {
        return $this->collection->toArray();
    }
}

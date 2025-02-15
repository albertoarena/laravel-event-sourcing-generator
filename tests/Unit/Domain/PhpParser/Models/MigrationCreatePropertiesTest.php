<?php

namespace Tests\Unit\Domain\PhpParser\Models;

use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Models\MigrationCreateProperties;
use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Models\MigrationCreateProperty;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MigrationCreatePropertiesTest extends TestCase
{
    #[Test]
    public function can_add_property(): void
    {
        $properties = new MigrationCreateProperties;
        $property = new MigrationCreateProperty('name', 'string');

        $properties->add($property);

        $this->assertArrayHasKey('name', $properties->toArray());
        $this->assertEquals('string', $properties->toArray()['name']->type->type);
    }

    #[Test]
    public function can_update_property(): void
    {
        $properties = new MigrationCreateProperties;
        $property1 = new MigrationCreateProperty('name', 'string');
        $property2 = new MigrationCreateProperty('name', 'text');

        $properties->add($property1);
        $properties->add($property2, true);

        $this->assertEquals('text', $properties->toArray()['name']->type->type);
    }

    #[Test]
    public function can_set_primary_key(): void
    {
        $properties = new MigrationCreateProperties;
        $primaryKeyProperty = new MigrationCreateProperty('uuid', 'string');
        $property1 = new MigrationCreateProperty('name', 'string');
        $property2 = new MigrationCreateProperty('surname', 'string');

        $properties->add($primaryKeyProperty)->add($property1)->add($property2);

        $primary = $properties->primary();

        $this->assertEquals('uuid', $primary->name);
        $this->assertEquals('string', $primary->type->type);
    }

    #[Test]
    public function can_set_primary_key_using_id(): void
    {
        $properties = new MigrationCreateProperties;
        $primaryKeyProperty = new MigrationCreateProperty('id', 'integer');
        $property1 = new MigrationCreateProperty('name', 'string');
        $property2 = new MigrationCreateProperty('surname', 'string');

        $properties->add($primaryKeyProperty)->add($property1)->add($property2);

        $primary = $properties->primary();

        $this->assertEquals('id', $primary->name);
        $this->assertEquals('integer', $primary->type->type);
    }

    #[Test]
    public function can_import_properties(): void
    {
        $collection = [
            'name' => 'string',
            'age' => 'integer',
        ];

        $properties = new MigrationCreateProperties;
        $properties->import($collection);

        $this->assertCount(2, $properties->toArray());
        $this->assertArrayHasKey('name', $properties->toArray());
        $this->assertArrayHasKey('age', $properties->toArray());
    }

    #[Test]
    public function can_import_properties_and_update(): void
    {
        $collection1 = [
            'name' => 'string',
            'age' => 'integer',
        ];

        $properties = new MigrationCreateProperties;
        $properties->import($collection1);

        $this->assertCount(2, $properties->toArray());
        $this->assertArrayHasKey('name', $properties->toArray());
        $this->assertArrayHasKey('age', $properties->toArray());
        $this->assertEquals('integer', $properties->toArray()['age']->type->type);

        $collection2 = [
            'age' => 'float',
        ];
        $properties->import($collection2, reset: false);

        $this->assertCount(2, $properties->toArray());
        $this->assertArrayHasKey('name', $properties->toArray());
        $this->assertArrayHasKey('age', $properties->toArray());
        $this->assertEquals('float', $properties->toArray()['age']->type->type);
    }

    #[Test]
    public function can_ignore_reserved_fields(): void
    {
        $properties = new MigrationCreateProperties([
            new MigrationCreateProperty('id', 'integer'),
            new MigrationCreateProperty('uuid', 'string'),
            new MigrationCreateProperty('name', 'string'),
        ]);

        $filteredProperties = $properties->withoutReservedFields();

        $this->assertArrayNotHasKey('id', $filteredProperties->toArray());
        $this->assertArrayNotHasKey('uuid', $filteredProperties->toArray());
        $this->assertArrayHasKey('name', $filteredProperties->toArray());
    }

    #[Test]
    public function can_ignore_skipped_methods(): void
    {
        $properties = new MigrationCreateProperties([
            new MigrationCreateProperty('name', 'string'),
            new MigrationCreateProperty('index', 'string'),
        ]);

        $filteredProperties = $properties->withoutSkippedMethods();

        $this->assertArrayHasKey('name', $filteredProperties->toArray());
        $this->assertArrayNotHasKey('index', $filteredProperties->toArray());
    }

    #[Test]
    public function can_convert_to_array(): void
    {
        $property = new MigrationCreateProperty('name', 'string');
        $properties = new MigrationCreateProperties;
        $properties->add($property);

        $array = $properties->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('name', $array);
        $this->assertInstanceOf(MigrationCreateProperty::class, $array['name']);
    }
}

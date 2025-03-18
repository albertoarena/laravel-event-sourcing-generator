<?php

namespace Tests\Unit\Domain\PhpParser\Models;

use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Models\MigrationCreatePropertyType;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MigrationCreatePropertyTypeTest extends TestCase
{
    #[Test]
    public function can_create_property_type_from_string()
    {
        $obj = new MigrationCreatePropertyType('string');
        $this->assertEquals('string', $obj->type);
        $this->assertFalse($obj->nullable);
        $this->assertEquals('string', $obj->toBuiltInType());
        $this->assertEquals('string', $obj->toNormalisedBuiltInType());
        $this->assertEquals('string', $obj->toProjection());
    }

    #[Test]
    public function can_create_property_type_from_nullable_type()
    {
        $obj = new MigrationCreatePropertyType('?string');
        $this->assertEquals('?string', $obj->type);
        $this->assertTrue($obj->nullable);
        $this->assertEquals('string', $obj->toBuiltInType());
        $this->assertEquals('string', $obj->toNormalisedBuiltInType());
        $this->assertEquals('string', $obj->toProjection());
    }

    #[Test]
    public function can_create_property_type_from_string_with_nullable()
    {
        $obj = new MigrationCreatePropertyType('string', true);
        $this->assertEquals('string', $obj->type);
        $this->assertTrue($obj->nullable);
        $this->assertEquals('string', $obj->toBuiltInType());
        $this->assertEquals('string', $obj->toNormalisedBuiltInType());
        $this->assertEquals('string', $obj->toProjection());
    }

    #[Test]
    public function can_create_property_type_from_nullable_string_with_nullable()
    {
        $obj = new MigrationCreatePropertyType('?string', true);
        $this->assertEquals('?string', $obj->type);
        $this->assertTrue($obj->nullable);
        $this->assertEquals('string', $obj->toBuiltInType());
        $this->assertEquals('string', $obj->toNormalisedBuiltInType());
        $this->assertEquals('string', $obj->toProjection());
    }

    #[Test]
    public function can_create_property_type_from_nullable_custom_type()
    {
        $obj = new MigrationCreatePropertyType('?custom');
        $this->assertEquals('?custom', $obj->type);
        $this->assertTrue($obj->nullable);
        $this->assertEquals('custom', $obj->toBuiltInType());
        $this->assertEquals('custom', $obj->toNormalisedBuiltInType());
        $this->assertEquals('custom', $obj->toProjection());
    }

    #[Test]
    public function can_create_property_type_from_date()
    {
        $obj = new MigrationCreatePropertyType('date');
        $this->assertEquals('date', $obj->type);
        $this->assertFalse($obj->nullable);
        $this->assertEquals('Carbon:Y-m-d', $obj->toBuiltInType());
        $this->assertEquals('Carbon', $obj->toNormalisedBuiltInType());
        $this->assertEquals('date:Y-m-d', $obj->toProjection());
    }

    #[Test]
    public function can_create_property_type_from_time()
    {
        $obj = new MigrationCreatePropertyType('time');
        $this->assertEquals('time', $obj->type);
        $this->assertFalse($obj->nullable);
        $this->assertEquals('Carbon:H:i:s', $obj->toBuiltInType());
        $this->assertEquals('Carbon', $obj->toNormalisedBuiltInType());
        $this->assertEquals('date:H:i:s', $obj->toProjection());
    }

    #[Test]
    public function can_create_property_type_from_nullable_timestamps()
    {
        $obj = new MigrationCreatePropertyType('nullableTimestamps');
        $this->assertEquals('nullableTimestamps', $obj->type);
        $this->assertTrue($obj->nullable);
        $this->assertEquals('Carbon', $obj->toBuiltInType());
        $this->assertEquals('Carbon', $obj->toNormalisedBuiltInType());
        $this->assertEquals('date:Y-m-d H:i:s', $obj->toProjection());
    }
}

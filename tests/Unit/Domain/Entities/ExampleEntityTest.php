<?php

namespace AIAgent\Tests\Unit\Domain\Entities;

use AIAgent\Domain\Entities\ExampleEntity;
use PHPUnit\Framework\TestCase;

class ExampleEntityTest extends TestCase
{
    public function testEntityCanBeInstantiated(): void
    {
        $entity = new ExampleEntity();
        $this->assertInstanceOf(ExampleEntity::class, $entity);
    }

    public function testEntityHasExpectedProperties(): void
    {
        $entity = new ExampleEntity();
        
        // Check if the entity has the expected properties
        $this->assertTrue(property_exists($entity, 'id'));
    }

    public function testEntityPropertiesHaveExpectedTypes(): void
    {
        $entity = new ExampleEntity();
        $entity->id = 123; // Initialize the typed property
        
        // Test property types
        $this->assertIsInt($entity->id);
        $this->assertEquals(123, $entity->id);
    }

    public function testEntityCanBeSerialized(): void
    {
        $entity = new ExampleEntity();
        
        // Test that the entity can be serialized
        $serialized = serialize($entity);
        $this->assertIsString($serialized);
        
        $unserialized = unserialize($serialized);
        $this->assertInstanceOf(ExampleEntity::class, $unserialized);
    }
}
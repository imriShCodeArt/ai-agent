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

    public function testEntityHasExpectedMethods(): void
    {
        $entity = new ExampleEntity();
        
        // Check if the entity has the expected methods
        $this->assertTrue(method_exists($entity, 'getId'));
        $this->assertTrue(method_exists($entity, 'getName'));
    }

    public function testEntityMethodsReturnExpectedTypes(): void
    {
        $entity = new ExampleEntity();
        
        // Test method return types
        $this->assertIsInt($entity->getId());
        $this->assertIsString($entity->getName());
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
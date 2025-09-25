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

    public function testEntityHasIdProperty(): void
    {
        $entity = new ExampleEntity();
        $this->assertObjectHasProperty('id', $entity);
    }

    public function testIdPropertyIsInteger(): void
    {
        $entity = new ExampleEntity();
        $entity->id = 123;
        
        $this->assertIsInt($entity->id);
        $this->assertEquals(123, $entity->id);
    }
}

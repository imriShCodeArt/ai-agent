<?php

namespace AIAgent\Tests\Unit\Domain\Contracts;

use AIAgent\Domain\Contracts\RepositoryInterface;
use PHPUnit\Framework\TestCase;

class RepositoryInterfaceTest extends TestCase
{
    public function testInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(RepositoryInterface::class));
    }

    public function testInterfaceHasFindByIdMethod(): void
    {
        $reflection = new \ReflectionClass(RepositoryInterface::class);
        $this->assertTrue($reflection->hasMethod('findById'));
    }

    public function testFindByIdMethodSignature(): void
    {
        $reflection = new \ReflectionClass(RepositoryInterface::class);
        $method = $reflection->getMethod('findById');
        
        $this->assertEquals(1, $method->getNumberOfParameters());
        $this->assertEquals('id', $method->getParameters()[0]->getName());
        $this->assertEquals('int', $method->getParameters()[0]->getType()->getName());
    }
}

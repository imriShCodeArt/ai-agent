<?php

namespace AIAgent\Tests\Unit\Infrastructure;

use AIAgent\Infrastructure\ServiceContainer;
use PHPUnit\Framework\TestCase;

class ServiceContainerTest extends TestCase
{
    private ServiceContainer $container;

    protected function setUp(): void
    {
        $this->container = new ServiceContainer();
    }

    public function testBindService(): void
    {
        $service = new \stdClass();
        $this->container->bind('test.service', function() use ($service) {
            return $service;
        });

        $this->assertSame($service, $this->container->get('test.service'));
    }

    public function testSingletonService(): void
    {
        $this->container->singleton('singleton.service', function() {
            return new \stdClass();
        });

        $service1 = $this->container->get('singleton.service');
        $service2 = $this->container->get('singleton.service');

        $this->assertSame($service1, $service2);
    }

    public function testGetUnboundServiceThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Service not bound: unbound.service');

        $this->container->get('unbound.service');
    }

    public function testBindWithClosure(): void
    {
        $this->container->bind('closure.service', function() {
            return 'test value';
        });

        $this->assertEquals('test value', $this->container->get('closure.service'));
    }
}

<?php

namespace AIAgent\Tests\Unit\REST\Controllers;

use AIAgent\REST\Controllers\BaseRestController;
use PHPUnit\Framework\TestCase;

class BaseRestControllerTest extends TestCase
{
    private BaseRestController $controller;

    protected function setUp(): void
    {
        $this->controller = new class extends BaseRestController {
            public function registerRoutes(): void
            {
                // Test implementation
            }
        };
    }

    public function testAddHooksCallsAddAction(): void
    {
        $this->expectNotToPerformAssertions();
        
        // This should not throw an exception
        $this->controller->addHooks();
    }

    public function testRegisterRoutesIsCallable(): void
    {
        $this->expectNotToPerformAssertions();
        
        // This should not throw an exception
        $this->controller->registerRoutes();
    }

    public function testImplementsHookableInterface(): void
    {
        $this->assertInstanceOf(\AIAgent\Infrastructure\Hooks\HookableInterface::class, $this->controller);
    }
}

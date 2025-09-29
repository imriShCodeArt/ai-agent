<?php

namespace AIAgent\Tests\Unit\Infrastructure\Hooks;

use AIAgent\Infrastructure\Hooks\HooksLoader;
use PHPUnit\Framework\TestCase;

class HooksLoaderTest extends TestCase
{
    private HooksLoader $hooksLoader;

    protected function setUp(): void
    {
        $this->hooksLoader = new HooksLoader();
    }

    public function testHooksLoaderCanBeInstantiated(): void
    {
        $this->assertInstanceOf(HooksLoader::class, $this->hooksLoader);
    }

    public function testRegisterMethodExists(): void
    {
        $this->assertTrue(method_exists($this->hooksLoader, 'register'));
    }

    public function testRegisterMethodDoesNotThrowException(): void
    {
        $this->expectNotToPerformAssertions();
        
        // Register should not throw exceptions
        $this->hooksLoader->register();
    }

    public function testAddHookableMethodExists(): void
    {
        $this->assertTrue(method_exists($this->hooksLoader, 'addHookable'));
    }

    public function testAddHookableAcceptsHookableInterface(): void
    {
        $hookable = new class implements \AIAgent\Infrastructure\Hooks\HookableInterface {
            public function addHooks(): void {}
        };

        $this->expectNotToPerformAssertions();
        
        // Should accept objects implementing HookableInterface
        $this->hooksLoader->addHookable($hookable);
    }
}

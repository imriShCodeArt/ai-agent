<?php

namespace AIAgent\Tests\Unit\Infrastructure\Hooks;

use AIAgent\Infrastructure\Hooks\HooksLoader;
use AIAgent\Infrastructure\Hooks\HookableInterface;
use PHPUnit\Framework\TestCase;

class HooksLoaderTest extends TestCase
{
    private HooksLoader $hooksLoader;

    protected function setUp(): void
    {
        $this->hooksLoader = new HooksLoader();
    }

    public function testAddHookable(): void
    {
        $hookable = $this->createMock(HookableInterface::class);
        
        $this->hooksLoader->add($hookable);
        
        // Since we can't access private property, we test indirectly
        // by ensuring no exception is thrown
        $this->assertTrue(true);
    }

    public function testRegisterCallsAddHooksOnAllHookables(): void
    {
        $hookable1 = $this->createMock(HookableInterface::class);
        $hookable2 = $this->createMock(HookableInterface::class);

        $hookable1->expects($this->once())
            ->method('addHooks');
        
        $hookable2->expects($this->once())
            ->method('addHooks');

        $this->hooksLoader->add($hookable1);
        $this->hooksLoader->add($hookable2);
        $this->hooksLoader->register();
    }

    public function testRegisterWithNoHookables(): void
    {
        // Should not throw exception when no hookables are added
        $this->hooksLoader->register();
        $this->assertTrue(true);
    }
}

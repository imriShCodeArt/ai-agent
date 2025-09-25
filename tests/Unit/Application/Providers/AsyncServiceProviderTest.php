<?php

namespace AIAgent\Tests\Unit\Application\Providers;

use PHPUnit\Framework\TestCase;
use AIAgent\Application\Providers\AsyncServiceProvider;
use AIAgent\Infrastructure\ServiceContainer;
use AIAgent\Infrastructure\Hooks\HooksLoader;
use AIAgent\Infrastructure\Queue\AsyncQueue;
use AIAgent\Support\Logger;

final class AsyncServiceProviderTest extends TestCase
{
    public function testRegistersQueueAndHooks(): void
    {
        $container = new ServiceContainer();
        $hooks = new HooksLoader();

        // Bind logger so provider can resolve it
        $container->singleton(Logger::class, static function () {
            return new Logger();
        });

        $provider = new AsyncServiceProvider($container, $hooks, __FILE__);
        $provider->register();
        $provider->addHooks();

        // Test that the AsyncQueue is registered in the container
        $this->assertTrue($container->has(AsyncQueue::class));
        
        // Test that the hook is registered
        $this->assertTrue(has_action('ai_agent_async_execute') >= 10);
    }
}



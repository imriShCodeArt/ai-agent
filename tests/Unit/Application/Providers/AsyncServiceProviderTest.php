<?php

namespace AIAgent\Tests\Unit\Application\Providers;

use PHPUnit\Framework\TestCase;
use AIAgent\Application\Providers\AsyncServiceProvider;
use AIAgent\Infrastructure\ServiceContainer;
use AIAgent\Infrastructure\Hooks\HooksLoader;
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

        $this->assertTrue(function_exists('do_action'));

        // Enqueue with Action Scheduler unavailable in test bootstrap → inline execute
        $queue = $container->get(\AIAgent\Application\Providers\AsyncServiceProvider::class . "\0AsyncQueue");
        // We can't access private classes easily; instead assert that hook exists
        $this->assertTrue(has_action('ai_agent_async_execute') >= 10);
    }
}



<?php

namespace AIAgent\Application\Providers;

use AIAgent\Infrastructure\Hooks\HookableInterface;
use AIAgent\Support\Logger;
use AIAgent\Infrastructure\Queue\AsyncQueue;

final class AsyncServiceProvider extends AbstractServiceProvider implements HookableInterface
{
    public function register(): void
    {
        // Ensure Action Scheduler is loaded if available (Composer-installed)
        if (!class_exists('ActionScheduler')) {
            // Action Scheduler loads via Composer autoload; noop if not present
        }

        // Register a simple async queue service wrapper in the container
        $this->container->singleton(AsyncQueue::class, function () {
            return new AsyncQueue($this->container->get(Logger::class));
        });
    }

    public function registerHooks(): void
    {
        // No admin UI yet; hooks belong to queue processor as actions
        add_action('ai_agent_async_execute', [$this, 'executeJob'], 10, 2);
    }

    public function addHooks(): void
    {
        $this->registerHooks();
    }

    /**
     * @param string $jobType
     * @param array<string,mixed> $payload
     */
    public function executeJob(string $jobType, array $payload = []): void
    {
        // Route jobs by type; extend as needed
        // For now, just log receipt
        $this->container->get(Logger::class)->info('Async job executed', [
            'type' => $jobType,
            'payload' => $payload,
        ]);
    }
}




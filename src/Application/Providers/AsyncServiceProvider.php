<?php

namespace AIAgent\Application\Providers;

use AIAgent\Infrastructure\Hooks\HookableInterface;
use AIAgent\Support\Logger;

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

final class AsyncQueue
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function enqueue(string $jobType, array $payload = [], int $delaySeconds = 0): int
    {
        if (!function_exists('as_enqueue_async_action')) {
            $this->logger->warning('Action Scheduler not available; running job inline');
            do_action('ai_agent_async_execute', $jobType, $payload);
            return 0;
        }

        if ($delaySeconds > 0) {
            return (int) as_schedule_single_action(time() + $delaySeconds, 'ai_agent_async_execute', [$jobType, $payload], 'ai-agent');
        }

        return (int) as_enqueue_async_action('ai_agent_async_execute', [$jobType, $payload], 'ai-agent');
    }
}



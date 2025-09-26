<?php

namespace AIAgent\Infrastructure\Queue;

use AIAgent\Support\Logger;

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

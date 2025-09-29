<?php
namespace AIAgent\Infrastructure\LLM;

use AIAgent\Support\Logger;

final class ClaudeProvider implements LLMProviderInterface
{
    private Logger $logger;
    private string $apiKey;

    public function __construct(Logger $logger, string $apiKey)
    {
        $this->logger = $logger;
        $this->apiKey = $apiKey;
    }

    /** @inheritDoc */
    public function complete(string $prompt, array $options = []): string
    {
        $this->logger->info('Claude complete called');
        return '[claude:stub] ' . substr($prompt, 0, 64);
    }
}



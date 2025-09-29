<?php
namespace AIAgent\Infrastructure\LLM;

use AIAgent\Support\Logger;

final class OpenAIProvider implements LLMProviderInterface
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
        // Placeholder implementation for Phase 2 scaffolding.
        // In Phase 2 we will add real HTTP client calls.
        $this->logger->info('OpenAI complete called');
        return '[openai:stub] ' . substr($prompt, 0, 64);
    }
}



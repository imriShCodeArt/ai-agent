<?php
namespace AIAgent\Infrastructure\LLM;

/**
 * Interface for Large Language Model providers.
 */
interface LLMProviderInterface
{
    /**
     * Generate a completion for a given prompt.
     *
     * @param string $prompt
     * @param array<string, mixed> $options
     * @return string
     */
    public function complete(string $prompt, array $options = []): string;
}



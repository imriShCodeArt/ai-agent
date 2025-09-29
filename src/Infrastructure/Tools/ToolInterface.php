<?php
namespace AIAgent\Infrastructure\Tools;

/**
 * Interface for executable tools available to the AI agent.
 */
interface ToolInterface
{
    /**
     * Unique tool name.
     */
    public function getName(): string;

    /**
     * JSON schema (as array) describing valid input.
     *
     * @return array<string, mixed>
     */
    public function getInputSchema(): array;

    /**
     * Execute the tool with validated input.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function execute(array $input): array;
}



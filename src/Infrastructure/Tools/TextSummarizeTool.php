<?php
namespace AIAgent\Infrastructure\Tools;

use AIAgent\Infrastructure\LLM\LLMProviderInterface;

final class TextSummarizeTool implements ToolInterface
{
    private LLMProviderInterface $llm;

    public function __construct(LLMProviderInterface $llm)
    {
        $this->llm = $llm;
    }

    public function getName(): string
    {
        return 'text.summarize';
    }

    public function getInputSchema(): array
    {
        return [
            'required' => ['content'],
            'properties' => [
                'content' => ['type' => 'string'],
                'length' => ['type' => 'string'],
            ],
        ];
    }

    public function execute(array $input): array
    {
        if (!Validator::validate($this->getInputSchema(), $input)) {
            return ['error' => 'invalid_input'];
        }
        $length = isset($input['length']) && is_string($input['length']) ? $input['length'] : 'short';
        $prompt = 'Summarize this text in a ' . $length . ' way: ' . (string) $input['content'];
        $output = $this->llm->complete($prompt);
        return ['summary' => $output];
    }
}



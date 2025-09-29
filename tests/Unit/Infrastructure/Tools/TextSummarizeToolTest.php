<?php
namespace AIAgent\Tests\Unit\Infrastructure\Tools;

use PHPUnit\Framework\TestCase;
use AIAgent\Infrastructure\Tools\TextSummarizeTool;
use AIAgent\Infrastructure\LLM\LLMProviderInterface;

final class TextSummarizeToolTest extends TestCase
{
    public function testExecuteReturnsSummary(): void
    {
        $llm = new class implements LLMProviderInterface {
            public function complete(string $prompt, array $options = []): string { return 'summary'; }
        };

        $tool = new TextSummarizeTool($llm);
        $result = $tool->execute(['content' => 'Long text']);
        $this->assertIsArray($result);
        $this->assertSame('summary', $result['summary']);
    }
}



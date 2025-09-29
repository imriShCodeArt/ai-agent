<?php
namespace AIAgent\Tests\Unit\Infrastructure\LLM;

use PHPUnit\Framework\TestCase;
use AIAgent\Infrastructure\LLM\OpenAIProvider;
use AIAgent\Support\Logger;

final class OpenAIProviderTest extends TestCase
{
    public function testCompleteReturnsString(): void
    {
        $provider = new OpenAIProvider(new Logger(), 'test');
        $result = $provider->complete('Hello world');
        $this->assertIsString($result);
        $this->assertStringContainsString('[openai:stub]', $result);
    }
}



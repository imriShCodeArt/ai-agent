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
        // Run in non-WP context; provider should fallback gracefully
        $result = $provider->complete('Hello world', ['model' => 'test-model']);
        $this->assertIsString($result);
        $this->assertNotSame('', $result);
    }
}



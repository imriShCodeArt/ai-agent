<?php
namespace AIAgent\Tests\Unit\Infrastructure\LLM;

use PHPUnit\Framework\TestCase;
use AIAgent\Infrastructure\LLM\OpenAIProvider;
use AIAgent\Support\Logger;

final class OpenAIProviderErrorTest extends TestCase
{
    public function testCompleteReturnsStringWithoutWP(): void
    {
        $provider = new OpenAIProvider(new Logger(), 'noop');
        $result = $provider->complete('anything');
        $this->assertIsString($result);
    }
}



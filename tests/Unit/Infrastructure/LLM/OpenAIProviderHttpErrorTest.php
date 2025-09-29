<?php
namespace AIAgent\Tests\Unit\Infrastructure\LLM;

use PHPUnit\Framework\TestCase;
use AIAgent\Infrastructure\LLM\OpenAIProvider;
use AIAgent\Support\Logger;

final class OpenAIProviderHttpErrorTest extends TestCase
{
    public function testGracefulWhenWpUnavailable(): void
    {
        $provider = new OpenAIProvider(new Logger(), 'noop');
        // When WP HTTP API functions are missing, provider should still return a string
        $out = $provider->complete('simulate http error', ['temperature' => 0.1]);
        $this->assertIsString($out);
    }
}



<?php
namespace AIAgent\Tests\Unit\REST\Controllers;

use PHPUnit\Framework\TestCase;
use AIAgent\REST\Controllers\ChatController;
use AIAgent\Infrastructure\Security\Policy;
use AIAgent\Infrastructure\Audit\AuditLogger;
use AIAgent\Support\Logger;
use AIAgent\Infrastructure\LLM\LLMProviderInterface;
use AIAgent\Infrastructure\Tools\ToolExecutionEngine;

final class ChatControllerWiringTest extends TestCase
{
    public function testProcessPromptReturnsStructure(): void
    {
        $policy = new Policy(new Logger());
        $audit = new AuditLogger(new Logger());
        $logger = new Logger();
        $llm = new class implements LLMProviderInterface {
            public function complete(string $prompt, array $options = []): string { return 'summary'; }
        };
        $engine = $this->createMock(ToolExecutionEngine::class);
        // Do not actually run tools in unit test
        $engine->method('run')->willReturn(['summary' => 'ok']);

        $controller = new ChatController($policy, $audit, $logger, $llm, $engine);

        $ref = new \ReflectionClass($controller);
        $method = $ref->getMethod('processPrompt');
        $method->setAccessible(true);
        $result = $method->invoke($controller, 'please summarize this text', 'suggest', 'sid', 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('suggested_actions', $result);
    }
}



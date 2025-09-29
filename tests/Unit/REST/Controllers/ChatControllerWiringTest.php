<?php
namespace AIAgent\Tests\Unit\REST\Controllers;

use PHPUnit\Framework\TestCase;
use AIAgent\REST\Controllers\ChatController;
use AIAgent\Infrastructure\Security\Policy;
use AIAgent\Infrastructure\Audit\AuditLogger;
use AIAgent\Support\Logger;
use AIAgent\Infrastructure\LLM\LLMProviderInterface;
use AIAgent\Infrastructure\Tools\ToolExecutionEngine;
use AIAgent\Infrastructure\Tools\ToolRegistry;

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
        // Use a minimal real engine with empty registry for wiring test
        $engine = new ToolExecutionEngine(new ToolRegistry(), $policy, new \AIAgent\Infrastructure\Security\Capabilities(), $audit);

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



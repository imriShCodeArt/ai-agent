<?php
namespace AIAgent\Tests\Unit\Infrastructure\Tools;

use PHPUnit\Framework\TestCase;
use AIAgent\Infrastructure\Tools\ToolExecutionEngine;
use AIAgent\Infrastructure\Tools\ToolRegistry;
use AIAgent\Infrastructure\Tools\ToolInterface;
use AIAgent\Infrastructure\Security\Policy;
use AIAgent\Infrastructure\Security\Capabilities;
use AIAgent\Infrastructure\Audit\AuditLogger;
use AIAgent\Support\Logger;

final class ToolExecutionEngineAllowedTest extends TestCase
{
    public function testRunReturnsResultWhenPolicyAllows(): void
    {
        $registry = new ToolRegistry();
        // Register tool matching Policy default key 'posts.create'
        $tool = new class implements ToolInterface {
            public function getName(): string { return 'posts.create'; }
            public function getInputSchema(): array { return []; }
            public function execute(array $input): array { return ['created' => true]; }
        };
        $registry->register($tool);

        $policy = new Policy(new Logger());
        $policy->disableTimeWindowCheck();
        $cap = new Capabilities();
        $audit = new AuditLogger(new Logger());

        $engine = new ToolExecutionEngine($registry, $policy, $cap, $audit);
        $result = $engine->run('posts.create', ['fields' => ['post_title' => 'Hello']]);

        $this->assertIsArray($result);
        $this->assertSame(true, $result['created'] ?? null);
    }
}



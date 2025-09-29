<?php
namespace AIAgent\Tests\Unit\Infrastructure\Tools;

use PHPUnit\Framework\TestCase;
use AIAgent\Infrastructure\Tools\ToolExecutionEngine;
use AIAgent\Infrastructure\Tools\ToolRegistry;
use AIAgent\Infrastructure\Tools\ToolInterface;
use AIAgent\Infrastructure\Security\Policy;
use AIAgent\Infrastructure\Security\Capabilities;
use AIAgent\Infrastructure\Audit\AuditLogger;

final class ToolExecutionEngineTest extends TestCase
{
    public function testPolicyDenied(): void
    {
        $registry = new ToolRegistry();
        $tool = new class implements ToolInterface {
            public function getName(): string { return 'noop'; }
            public function getInputSchema(): array { return []; }
            public function execute(array $input): array { return ['ok' => true]; }
        };
        $registry->register($tool);

        $policy = $this->createMock(Policy::class);
        $policy->method('isAllowed')->willReturn(false);
        $cap = $this->createMock(Capabilities::class);
        $audit = $this->createMock(AuditLogger::class);

        $engine = new ToolExecutionEngine($registry, $policy, $cap, $audit);
        $result = $engine->run('noop', ['fields' => []]);
        $this->assertSame('policy_denied', $result['error'] ?? null);
    }
}



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

        // Use real instances for final classes
        $policy = new Policy(new Logger());
        $cap = new Capabilities();
        $audit = new AuditLogger(new Logger());

        $engine = new ToolExecutionEngine($registry, $policy, $cap, $audit);
        $result = $engine->run('noop', ['fields' => []]);
        $this->assertSame('policy_denied', $result['error'] ?? null);
    }
}



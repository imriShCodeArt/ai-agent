<?php
namespace AIAgent\Tests\Unit\Infrastructure\Tools;

use PHPUnit\Framework\TestCase;
use AIAgent\Infrastructure\Tools\ToolRegistry;
use AIAgent\Infrastructure\Tools\ToolInterface;

final class ToolRegistryTest extends TestCase
{
    public function testRegisterAndGet(): void
    {
        $registry = new ToolRegistry();
        $tool = new class implements ToolInterface {
            public function getName(): string { return 'example'; }
            public function getInputSchema(): array { return []; }
            public function execute(array $input): array { return ['ok' => true]; }
        };

        $registry->register($tool);
        $this->assertTrue($registry->has('example'));
        $this->assertSame($tool, $registry->get('example'));
    }
}



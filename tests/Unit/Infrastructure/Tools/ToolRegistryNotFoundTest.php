<?php
namespace AIAgent\Tests\Unit\Infrastructure\Tools;

use PHPUnit\Framework\TestCase;
use AIAgent\Infrastructure\Tools\ToolRegistry;

final class ToolRegistryNotFoundTest extends TestCase
{
    public function testGetUnknownToolThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $registry = new ToolRegistry();
        $registry->get('unknown.tool');
    }
}



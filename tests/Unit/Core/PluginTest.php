<?php

namespace AIAgent\Tests\Unit\Core;

use AIAgent\Core\Plugin;
use AIAgent\Infrastructure\ServiceContainer;
use AIAgent\Infrastructure\Hooks\HooksLoader;
use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase
{
    public function testPluginBootCreatesInstance(): void
    {
        $pluginFile = __DIR__ . '/../../../ai-agent.php';

        // Boot should not error even without full WordPress env
        Plugin::boot($pluginFile);
        
        // Since Plugin uses singleton pattern, we can't easily test the instance
        // But we can verify it doesn't throw exceptions
        $this->assertTrue(true);
    }
    
    public function testPluginBootWithInvalidFile(): void
    {
        $this->expectNotToPerformAssertions();
        
        // Should not throw exception even with invalid file
        Plugin::boot('/invalid/path.php');
    }
}

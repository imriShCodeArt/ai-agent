<?php

namespace AIAgent\Tests\Integration;

use AIAgent\Core\Plugin;
use AIAgent\Infrastructure\Database\MigrationManager;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for plugin activation and deactivation
 */
class PluginActivationTest extends TestCase
{
    private string $pluginFile;

    protected function setUp(): void
    {
        $this->pluginFile = __DIR__ . '/../../ai-agent.php';
    }

    public function testPluginBootDoesNotThrowException(): void
    {
        $this->expectNotToPerformAssertions();
        
        // Plugin boot should not throw exceptions
        Plugin::boot($this->pluginFile);
    }

    public function testPluginBootWithInvalidFile(): void
    {
        $this->expectNotToPerformAssertions();
        
        // Should handle invalid file gracefully
        Plugin::boot('/invalid/path.php');
    }

    public function testMigrationManagerCanBeInstantiated(): void
    {
        // Create a mock logger for testing
        $logger = new class {
            public function info(string $message, array $context = []): void {}
            public function error(string $message, array $context = []): void {}
            public function warning(string $message, array $context = []): void {}
        };
        
        $migrationManager = new MigrationManager($logger);
        $this->assertInstanceOf(MigrationManager::class, $migrationManager);
    }

    public function testPluginFileExists(): void
    {
        $this->assertFileExists($this->pluginFile);
    }

    public function testPluginFileHasCorrectHeader(): void
    {
        $content = file_get_contents($this->pluginFile);
        $this->assertStringContainsString('Plugin Name:', $content);
        $this->assertStringContainsString('Version:', $content);
        $this->assertStringContainsString('Description:', $content);
    }
}

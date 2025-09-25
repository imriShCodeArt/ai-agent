<?php

namespace AIAgent\Tests\Unit\Application\Providers;

use AIAgent\Application\Providers\AbstractServiceProvider;
use AIAgent\Infrastructure\ServiceContainer;
use AIAgent\Infrastructure\Hooks\HooksLoader;
use PHPUnit\Framework\TestCase;

class AbstractServiceProviderTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        // Use a real container instead of mocking final class
        $container = new ServiceContainer();
        $hooks = new HooksLoader();
        $pluginFile = '/test/path.php';

        $provider = new class($container, $hooks, $pluginFile) extends AbstractServiceProvider {
            public function register(): void
            {
                // Test implementation
            }
        };

        // Test that properties are accessible (using reflection)
        $reflection = new \ReflectionClass($provider);
        
        $containerProperty = $reflection->getProperty('container');
        $containerProperty->setAccessible(true);
        $this->assertSame($container, $containerProperty->getValue($provider));

        $hooksProperty = $reflection->getProperty('hooks');
        $hooksProperty->setAccessible(true);
        $this->assertSame($hooks, $hooksProperty->getValue($provider));

        $pluginFileProperty = $reflection->getProperty('pluginFile');
        $pluginFileProperty->setAccessible(true);
        $this->assertSame($pluginFile, $pluginFileProperty->getValue($provider));
    }
}

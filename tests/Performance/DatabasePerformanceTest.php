<?php

namespace AIAgent\Tests\Performance;

use AIAgent\Infrastructure\ServiceContainer;
use AIAgent\Infrastructure\Database\MigrationManager;
use PHPUnit\Framework\TestCase;

/**
 * Performance tests for database operations
 * 
 * @group performance
 */
class DatabasePerformanceTest extends TestCase
{
    private ServiceContainer $container;
    private MigrationManager $migrationManager;

    protected function setUp(): void
    {
        $this->container = new ServiceContainer();
        $this->migrationManager = new MigrationManager();
    }

    public function testServiceContainerPerformance(): void
    {
        $startTime = microtime(true);
        
        // Test service binding performance
        for ($i = 0; $i < 1000; $i++) {
            $this->container->bind("service_{$i}", function() {
                return new \stdClass();
            });
        }
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        // Should complete within 100ms
        $this->assertLessThan(100, $executionTime, 
            "Service binding took {$executionTime}ms, expected < 100ms");
    }

    public function testServiceResolutionPerformance(): void
    {
        // Bind a service
        $this->container->bind('test.service', function() {
            return new \stdClass();
        });
        
        $startTime = microtime(true);
        
        // Test service resolution performance
        for ($i = 0; $i < 1000; $i++) {
            $this->container->get('test.service');
        }
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        // Should complete within 50ms
        $this->assertLessThan(50, $executionTime, 
            "Service resolution took {$executionTime}ms, expected < 50ms");
    }

    public function testMemoryUsage(): void
    {
        $initialMemory = memory_get_usage();
        
        // Create many objects to test memory usage
        $objects = [];
        for ($i = 0; $i < 1000; $i++) {
            $objects[] = new \stdClass();
        }
        
        $peakMemory = memory_get_peak_usage();
        $memoryUsed = $peakMemory - $initialMemory;
        
        // Should use less than 1MB
        $this->assertLessThan(1024 * 1024, $memoryUsed, 
            "Memory usage: " . number_format($memoryUsed / 1024, 2) . "KB, expected < 1MB");
    }

    public function testMigrationManagerInstantiationPerformance(): void
    {
        $startTime = microtime(true);
        
        // Test multiple instantiations
        for ($i = 0; $i < 100; $i++) {
            new MigrationManager();
        }
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        // Should complete within 10ms
        $this->assertLessThan(10, $executionTime, 
            "MigrationManager instantiation took {$executionTime}ms, expected < 10ms");
    }
}

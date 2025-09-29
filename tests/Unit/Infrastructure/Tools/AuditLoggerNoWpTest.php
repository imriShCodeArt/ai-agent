<?php
namespace AIAgent\Tests\Unit\Infrastructure\Tools;

use PHPUnit\Framework\TestCase;
use AIAgent\Infrastructure\Audit\AuditLogger;
use AIAgent\Support\Logger;

final class AuditLoggerNoWpTest extends TestCase
{
    public function testClassInstantiatesWithoutWpDbUsage(): void
    {
        // Ensure class can be created in test environment
        $logger = new AuditLogger(new Logger());
        $this->assertInstanceOf(AuditLogger::class, $logger);
    }
}



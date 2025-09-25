<?php

namespace AIAgent\Tests\Unit\Support;

use AIAgent\Support\Logger;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    private Logger $logger;

    protected function setUp(): void
    {
        $this->logger = new Logger();
    }

    public function testInfoMethodExists(): void
    {
        $this->assertTrue(method_exists($this->logger, 'info'));
    }

    public function testErrorMethodExists(): void
    {
        $this->assertTrue(method_exists($this->logger, 'error'));
    }

    public function testInfoAcceptsStringMessage(): void
    {
        // Should not throw exception
        $this->logger->info('Test message');
        $this->assertTrue(true);
    }

    public function testInfoAcceptsContextArray(): void
    {
        // Should not throw exception
        $this->logger->info('Test message', ['key' => 'value']);
        $this->assertTrue(true);
    }

    public function testErrorAcceptsStringMessage(): void
    {
        // Should not throw exception
        $this->logger->error('Test error');
        $this->assertTrue(true);
    }

    public function testErrorAcceptsContextArray(): void
    {
        // Should not throw exception
        $this->logger->error('Test error', ['error_code' => 500]);
        $this->assertTrue(true);
    }
}

<?php

namespace AIAgent\Tests\Unit\Infrastructure\Tools;

use AIAgent\Infrastructure\Tools\WCProductsBulkUpdateTool;
use PHPUnit\Framework\TestCase;

final class WCProductsBulkUpdateToolTest extends TestCase
{
    public function testValidationFailsWithoutItems(): void
    {
        $tool = new WCProductsBulkUpdateTool();
        $result = $tool->execute([]);
        $this->assertIsArray($result);
        $this->assertFalse($result['ok']);
        $this->assertSame('validation_failed', $result['error']);
    }

    public function testDisabledWhenFeatureOff(): void
    {
        update_option('ai_agent_woocommerce_enabled', false);
        $tool = new WCProductsBulkUpdateTool();
        $result = $tool->execute(['items' => [['id' => 1]]]);
        $this->assertFalse($result['ok']);
        $this->assertSame('WooCommerce disabled', $result['error']);
    }
}



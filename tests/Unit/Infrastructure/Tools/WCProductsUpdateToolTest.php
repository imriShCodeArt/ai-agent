<?php

namespace AIAgent\Tests\Unit\Infrastructure\Tools;

use AIAgent\Infrastructure\Tools\WCProductsUpdateTool;
use PHPUnit\Framework\TestCase;

final class WCProductsUpdateToolTest extends TestCase
{
    public function testValidationFailsWithoutId(): void
    {
        $tool = new WCProductsUpdateTool();
        $result = $tool->execute([]);
        $this->assertIsArray($result);
        $this->assertFalse($result['ok']);
        $this->assertSame('validation_failed', $result['error']);
    }

    public function testDisabledWhenFeatureOff(): void
    {
        update_option('ai_agent_woocommerce_enabled', false);
        $tool = new WCProductsUpdateTool();
        $result = $tool->execute(['id' => 1]);
        $this->assertFalse($result['ok']);
        $this->assertSame('WooCommerce disabled', $result['error']);
    }
}



<?php

namespace AIAgent\Tests\Unit\Infrastructure\Tools;

use AIAgent\Infrastructure\Tools\WCProductsSearchTool;
use PHPUnit\Framework\TestCase;

final class WCProductsSearchToolTest extends TestCase
{
    public function testValidationFailsOnInvalidRange(): void
    {
        $tool = new WCProductsSearchTool();
        $result = $tool->execute(['price_min' => -1]);
        $this->assertIsArray($result);
        $this->assertFalse($result['ok']);
        $this->assertSame('validation_failed', $result['error']);
    }

    public function testDisabledWhenFeatureOff(): void
    {
        update_option('ai_agent_woocommerce_enabled', false);
        $tool = new WCProductsSearchTool();
        $result = $tool->execute(['q' => 'shoes']);
        $this->assertFalse($result['ok']);
        $this->assertSame('WooCommerce disabled', $result['error']);
    }
}



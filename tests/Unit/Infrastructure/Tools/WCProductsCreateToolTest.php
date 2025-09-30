<?php

namespace AIAgent\Tests\Unit\Infrastructure\Tools;

use AIAgent\Infrastructure\Tools\WCProductsCreateTool;
use PHPUnit\Framework\TestCase;

final class WCProductsCreateToolTest extends TestCase
{
    public function testReturnsValidationFailedWhenMissingRequiredFields(): void
    {
        $tool = new WCProductsCreateTool();
        $result = $tool->execute([]);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('ok', $result);
        $this->assertFalse($result['ok']);
        $this->assertSame('validation_failed', $result['error']);
    }

    public function testReturnsDisabledWhenWooFeatureFlagOff(): void
    {
        // Ensure option false
        update_option('ai_agent_woocommerce_enabled', false);
        $tool = new WCProductsCreateTool();
        $result = $tool->execute(['title' => 'Test', 'price' => 1.0]);
        $this->assertFalse($result['ok']);
        $this->assertSame('WooCommerce disabled', $result['error']);
    }

    public function testReturnsNotAvailableWhenWooNotLoaded(): void
    {
        update_option('ai_agent_woocommerce_enabled', true);
        // If WC not loaded in test env, tool should gracefully error
        $tool = new WCProductsCreateTool();
        $result = $tool->execute(['title' => 'Test', 'price' => 1.0]);
        if (!class_exists('WC_Product')) {
            $this->assertFalse($result['ok']);
            $this->assertSame('WooCommerce not available', $result['error']);
        } else {
            $this->assertTrue(true); // Environment has WC, skip assertion
        }
    }
}



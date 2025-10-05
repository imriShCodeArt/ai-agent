<?php

namespace AIAgent\Tests\Unit\REST\Controllers;

use AIAgent\REST\Controllers\WooCommerceController;
use AIAgent\Support\Logger;
use PHPUnit\Framework\TestCase;

final class WooCommerceControllerTest extends TestCase
{
    public function testProductsSearchDisabledWhenFeatureOff(): void
    {
        update_option('ai_agent_woocommerce_enabled', false);
        $logger = new Logger();
        $controller = new WooCommerceController($logger);
        $request = new \WP_REST_Request();
        $result = $controller->productsSearch($request);
        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('wc_disabled', $result->get_error_code());
    }

    public function testProductsSearchMissingWooReturnsError(): void
    {
        update_option('ai_agent_woocommerce_enabled', true);
        $logger = new Logger();
        $controller = new WooCommerceController($logger);
        $request = new \WP_REST_Request();
        if (!class_exists('WC_Product')) {
            $result = $controller->productsSearch($request);
            $this->assertInstanceOf(\WP_Error::class, $result);
            $this->assertSame('wc_missing', $result->get_error_code());
        } else {
            $this->assertTrue(true);
        }
    }
}



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
}



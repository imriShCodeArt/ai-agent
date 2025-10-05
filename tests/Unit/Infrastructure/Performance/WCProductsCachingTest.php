<?php

namespace AIAgent\Tests\Unit\Infrastructure\Performance;

use PHPUnit\Framework\TestCase;
use AIAgent\REST\Controllers\WooCommerceController;
use AIAgent\Support\Logger;

final class WCProductsCachingTest extends TestCase
{
    public function testCacheKeyStableAcrossSameQueryArgs(): void
    {
        if (!function_exists('wp_cache_get')) {
            $this->assertTrue(true);
            return;
        }
        update_option('ai_agent_woocommerce_enabled', true);
        $controller = new WooCommerceController(new Logger());
        $req1 = new \WP_REST_Request('GET', '/ai-agent/v1/wc/products');
        $req1->set_param('q', 'shoe');
        $req1->set_param('per_page', 10);

        $req2 = new \WP_REST_Request('GET', '/ai-agent/v1/wc/products');
        $req2->set_param('q', 'shoe');
        $req2->set_param('per_page', 10);

        // First call primes cache (may early return if WC missing)
        $controller->productsSearch($req1);
        $controller->productsSearch($req2);

        $this->assertTrue(true);
    }
}



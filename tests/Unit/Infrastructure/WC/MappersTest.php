<?php

namespace AIAgent\Tests\Unit\Infrastructure\WC;

use AIAgent\Infrastructure\WC\Mappers;
use PHPUnit\Framework\TestCase;

final class MappersTest extends TestCase
{
    public function testMapProductWithAnonymousObject(): void
    {
        $product = new class {
            public function get_id() { return 123; }
            public function get_name() { return 'Prod'; }
            public function get_sku() { return 'SKU-1'; }
            public function get_price() { return 9.99; }
            public function get_stock_status() { return 'instock'; }
        };

        // Mock wp functions where used
        if (!function_exists('get_woocommerce_currency')) {
            function get_woocommerce_currency() { return 'USD'; }
        }
        if (!function_exists('wp_get_post_terms')) {
            function wp_get_post_terms($id, $tax, $args) { return ['Category A']; }
        }

        $dto = Mappers::mapProduct($product);
        $this->assertSame(123, $dto['id']);
        $this->assertSame('Prod', $dto['name']);
        $this->assertSame('SKU-1', $dto['sku']);
        $this->assertSame('USD', $dto['currency']);
        $this->assertSame('instock', $dto['stock_status']);
        $this->assertSame(['Category A'], $dto['categories']);
    }

    public function testMapOrderWithAnonymousObject(): void
    {
        $item = new class {
            public function get_product_id() { return 5; }
            public function get_name() { return 'Line Item'; }
            public function get_quantity() { return 2; }
            public function get_total() { return 19.98; }
        };
        $order = new class($item) {
            private $it;
            public function __construct($it) { $this->it = $it; }
            public function get_items() { return [$this->it]; }
            public function get_id() { return 77; }
            public function get_order_number() { return '77'; }
            public function get_user_id() { return 9; }
            public function get_total() { return 19.98; }
            public function get_currency() { return 'USD'; }
            public function get_date_created() { return new class { public function date($f){ return '2024-01-01T00:00:00+00:00'; } }; }
            public function get_payment_method() { return 'cod'; }
            public function get_status() { return 'processing'; }
        };

        $dto = Mappers::mapOrder($order);
        $this->assertSame(77, $dto['id']);
        $this->assertSame('USD', $dto['currency']);
        $this->assertCount(1, $dto['items']);
        $this->assertSame(2, $dto['items'][0]['quantity']);
    }

    public function testMapCustomerWithAnonymousObject(): void
    {
        $customer = new class {
            public function get_id() { return 321; }
            public function get_email() { return 'c@example.com'; }
            public function get_first_name() { return 'First'; }
            public function get_last_name() { return 'Last'; }
            public function get_order_count() { return 3; }
            public function get_total_spent() { return 45.00; }
        };

        $dto = Mappers::mapCustomer($customer);
        $this->assertSame(321, $dto['id']);
        $this->assertSame('c@example.com', $dto['email']);
        $this->assertSame(3, $dto['orders_count']);
        $this->assertSame(45.00, $dto['total_spent']);
    }
}



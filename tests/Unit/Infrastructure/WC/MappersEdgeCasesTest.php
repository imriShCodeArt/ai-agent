<?php

namespace AIAgent\Tests\Unit\Infrastructure\WC;

use PHPUnit\Framework\TestCase;
use AIAgent\Infrastructure\WC\Mappers;

final class MappersEdgeCasesTest extends TestCase
{
    public function testMapOrderWithNoItems(): void
    {
        $order = new class {
            public function get_items() { return []; }
            public function get_id() { return 10; }
            public function get_order_number() { return '10'; }
            public function get_user_id() { return 0; }
            public function get_total() { return 0; }
            public function get_currency() { return 'USD'; }
            public function get_date_created() { return null; }
            public function get_payment_method() { return 'cod'; }
            public function get_status() { return 'pending'; }
        };

        $dto = Mappers::mapOrder($order);
        $this->assertSame(10, $dto['id']);
        $this->assertIsArray($dto['items']);
        $this->assertCount(0, $dto['items']);
    }

    public function testMapCustomerWithMinimalData(): void
    {
        $customer = new class {
            public function get_id() { return 0; }
            public function get_email() { return ''; }
            public function get_first_name() { return ''; }
            public function get_last_name() { return ''; }
            public function get_order_count() { return 0; }
            public function get_total_spent() { return 0; }
        };

        $dto = Mappers::mapCustomer($customer);
        $this->assertSame(0, $dto['id']);
        $this->assertSame('', $dto['email']);
        $this->assertSame(0, $dto['orders_count']);
        $this->assertSame(0.0, $dto['total_spent']);
    }
}



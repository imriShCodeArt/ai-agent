<?php

namespace AIAgent\Tests\Unit\Infrastructure\WC;

use AIAgent\Infrastructure\WC\Summarizers;
use PHPUnit\Framework\TestCase;

final class SummarizersTest extends TestCase
{
    public function testSummarizeOrder(): void
    {
        $item = new class {
            public function get_name() { return 'Item'; }
            public function get_quantity() { return 2; }
        };
        $order = new class($item) {
            private $it; public function __construct($it){ $this->it=$it; }
            public function get_status() { return 'processing'; }
            public function get_total() { return 19.98; }
            public function get_items() { return [$this->it]; }
        };
        $summary = Summarizers::summarizeOrder($order);
        $this->assertStringContainsString('Status: processing', $summary);
        $this->assertStringContainsString('Total: 19.98', $summary);
        $this->assertStringContainsString('Item x2', $summary);
    }

    public function testSummarizeCustomer(): void
    {
        $customer = new class {
            public function get_first_name() { return 'First'; }
            public function get_last_name() { return 'Last'; }
            public function get_order_count() { return 3; }
            public function get_total_spent() { return 45.00; }
        };
        $summary = Summarizers::summarizeCustomer($customer);
        $this->assertStringContainsString('Customer: First Last', $summary);
        $this->assertStringContainsString('Orders: 3', $summary);
        $this->assertStringContainsString('Spent: 45.00', $summary);
    }
}



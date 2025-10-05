<?php

namespace AIAgent\Tests\Unit\Infrastructure\Audit;

use PHPUnit\Framework\TestCase;
use AIAgent\Infrastructure\Audit\EnhancedAuditLogger;
use AIAgent\Support\Logger;

final class EnhancedAuditLoggerTest extends TestCase
{
    public function testCommerceMetadataIsPersisted(): void
    {
        $logger = new Logger();
        $audit = new EnhancedAuditLogger($logger);

        // Stub $wpdb with minimal insert that captures the data
        $captured = [];
        $GLOBALS['wpdb'] = new class($captured) {
            public string $prefix = 'wp_';
            public array $last = [];
            public function __construct(&$_c) {}
            public function insert($table, $data) { $this->last = $data; return 1; }
            public function update($table, $data, $where = []) { $this->last = $data; return 1; }
            public function get_charset_collate() { return ''; }
            public function get_var($q) { return null; }
            public function query($q) { return true; }
            public function prepare($q) { return $q; }
            public function get_results($q) { return []; }
            public function get_row($q, $type = ARRAY_A) { return null; }
            public function __get($k) { return null; }
        };

        $audit->logAction(
            'products.update',
            1,
            'product',
            123,
            'suggest',
            ['id' => 123, 'sku' => 'ABC', 'price_change' => 10.5],
            'approved',
            [
                'policy_version' => '1.0.0',
                'policy_verdict' => 'allow',
                'policy_reason' => 'threshold_ok',
                'product_id' => 123,
                'sku' => 'ABC',
                'price_change' => 10.5,
            ]
        );

        $this->assertArrayHasKey('product_id', $GLOBALS['wpdb']->last);
        $this->assertArrayHasKey('sku', $GLOBALS['wpdb']->last);
        $this->assertArrayHasKey('price_change', $GLOBALS['wpdb']->last);
        $this->assertSame(123, $GLOBALS['wpdb']->last['product_id']);
        $this->assertSame('ABC', $GLOBALS['wpdb']->last['sku']);
    }
}



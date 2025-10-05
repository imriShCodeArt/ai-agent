<?php

namespace AIAgent\Tests\Unit\E2E;

use AIAgent\REST\Controllers\ReviewController;
use AIAgent\Support\Logger;
use PHPUnit\Framework\TestCase;

final class BatchApprovalWorkflowTest extends TestCase
{
    private ReviewController $controller;
    private array $testData = [];

    protected function setUp(): void
    {
        $this->controller = new ReviewController(new Logger());
        
        // Mock wpdb with realistic test data
        $this->testData = [
            ['id' => 1, 'status' => 'pending', 'tool' => 'products.update', 'entity_type' => 'product', 'entity_id' => 101],
            ['id' => 2, 'status' => 'pending', 'tool' => 'products.update', 'entity_type' => 'product', 'entity_id' => 102],
            ['id' => 3, 'status' => 'pending', 'tool' => 'products.create', 'entity_type' => 'product', 'entity_id' => 103],
            ['id' => 4, 'status' => 'approved', 'tool' => 'products.update', 'entity_type' => 'product', 'entity_id' => 104],
            ['id' => 5, 'status' => 'rejected', 'tool' => 'products.update', 'entity_type' => 'product', 'entity_id' => 105],
        ];

        $GLOBALS['wpdb'] = new class($this->testData) {
            public string $prefix = 'wp_';
            public int $insert_id = 1;
            public string $last_error = '';
            public array $testData = [];
            public array $operations = [];
            
            public function __construct($testData) {
                $this->testData = $testData;
            }
            
            public function get_results($q, $type = ARRAY_A) { 
                return $this->testData; 
            }
            
            public function get_row($q, $type = ARRAY_A) { 
                $id = $this->extractIdFromQuery($q);
                foreach ($this->testData as $row) {
                    if ($row['id'] == $id) {
                        return $row;
                    }
                }
                return null;
            }
            
            public function update($table, $data, $where = []) { 
                $this->operations[] = ['action' => 'update', 'table' => $table, 'data' => $data, 'where' => $where];
                
                // Update test data
                foreach ($this->testData as &$row) {
                    if (isset($where['id']) && $row['id'] == $where['id']) {
                        $row = array_merge($row, $data);
                        break;
                    }
                }
                
                return 1; 
            }
            
            private function extractIdFromQuery($q) {
                if (preg_match('/id = (\d+)/', $q, $matches)) {
                    return (int) $matches[1];
                }
                return null;
            }
            
            public function get_var($q){ return null; }
            public function query($q){ return true; }
            public function prepare($q){ return $q; }
            public function get_charset_collate(){ return ''; }
            public function insert($table, $data) { 
                $this->operations[] = ['action' => 'insert', 'table' => $table, 'data' => $data];
                return 1; 
            }
        };

        // WordPress functions are now provided by bootstrap.php
    }

    public function testBatchApproveHappyPath(): void
    {
        // Test successful batch approval
        $request = new \WP_REST_Request(['ids' => [1, 2, 3]]);
        $response = $this->controller->batchApprove($request);
        
        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertTrue(isset($response->data['approved']));
        $this->assertTrue(isset($response->data['errors']));
        $this->assertTrue(isset($response->data['total']));
        
        $this->assertCount(3, $response->data['approved']);
        $this->assertEmpty($response->data['errors']);
        $this->assertEquals(3, $response->data['total']);
        
        // Verify all items were updated (3 updates + 3 audit inserts = 6 operations)
        $this->assertCount(6, $GLOBALS['wpdb']->operations);
        
        // Check that we have 3 update operations for actions
        $updateOps = array_filter($GLOBALS['wpdb']->operations, fn($op) => $op['action'] === 'update');
        $this->assertCount(3, $updateOps);
        
        foreach ($updateOps as $op) {
            $this->assertEquals('wp_ai_agent_actions', $op['table']);
            $this->assertEquals('approved', $op['data']['status']);
        }
        
        // Check that we have 3 insert operations for audit logs
        $insertOps = array_filter($GLOBALS['wpdb']->operations, fn($op) => $op['action'] === 'insert');
        $this->assertCount(3, $insertOps);
    }

    public function testBatchRejectHappyPath(): void
    {
        // Test successful batch rejection
        $request = new \WP_REST_Request(['ids' => [1, 2, 3], 'reason' => 'Quality issues']);
        $response = $this->controller->batchReject($request);
        
        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertTrue(isset($response->data['rejected']));
        $this->assertTrue(isset($response->data['errors']));
        $this->assertTrue(isset($response->data['total']));
        
        $this->assertCount(3, $response->data['rejected']);
        $this->assertEmpty($response->data['errors']);
        $this->assertEquals(3, $response->data['total']);
        
        // Verify all items were updated (3 updates + 3 audit inserts = 6 operations)
        $this->assertCount(6, $GLOBALS['wpdb']->operations);
        
        // Check that we have 3 update operations for actions
        $updateOps = array_filter($GLOBALS['wpdb']->operations, fn($op) => $op['action'] === 'update');
        $this->assertCount(3, $updateOps);
        
        foreach ($updateOps as $op) {
            $this->assertEquals('wp_ai_agent_actions', $op['table']);
            $this->assertEquals('rejected', $op['data']['status']);
            $this->assertEquals('Quality issues', $op['data']['error']);
        }
        
        // Check that we have 3 insert operations for audit logs
        $insertOps = array_filter($GLOBALS['wpdb']->operations, fn($op) => $op['action'] === 'insert');
        $this->assertCount(3, $insertOps);
    }

    public function testBatchApproveWithMixedStatuses(): void
    {
        // Test batch approval with items that have different statuses
        $request = new \WP_REST_Request(['ids' => [1, 4, 5]]); // pending, approved, rejected
        $response = $this->controller->batchApprove($request);
        
        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        
        // Should still process all items
        $this->assertCount(3, $response->data['approved']);
        $this->assertEmpty($response->data['errors']);
    }

    public function testBatchOperationsWithEmptyIds(): void
    {
        // Test error handling for empty IDs
        $request = new \WP_REST_Request(['ids' => []]);
        $response = $this->controller->batchApprove($request);
        
        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertEquals(400, $response->status);
        $this->assertEquals('No IDs provided', $response->data['error']);
    }

    public function testBatchOperationsWithInvalidIds(): void
    {
        // Test error handling for invalid IDs
        $request = new \WP_REST_Request(['ids' => ['invalid', 'not_a_number']]);
        $response = $this->controller->batchApprove($request);
        
        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        
        // Should process the IDs (they get converted to 0)
        $this->assertTrue(isset($response->data['approved']));
        $this->assertTrue(isset($response->data['errors']));
    }

    public function testBatchApprovalWorkflowIntegration(): void
    {
        // Test complete workflow: list -> batch approve -> verify status
        $listRequest = new \WP_REST_Request(['page' => 1, 'per_page' => 10]);
        $listResponse = $this->controller->list($listRequest);
        
        $this->assertInstanceOf(\WP_REST_Response::class, $listResponse);
        $this->assertTrue(isset($listResponse->data['items']));
        
        // Get pending items
        $pendingItems = array_filter($this->testData, function($item) {
            return $item['status'] === 'pending';
        });
        
        $pendingIds = array_column($pendingItems, 'id');
        
        // Batch approve all pending items
        $approveRequest = new \WP_REST_Request(['ids' => $pendingIds]);
        $approveResponse = $this->controller->batchApprove($approveRequest);
        
        $this->assertInstanceOf(\WP_REST_Response::class, $approveResponse);
        $this->assertCount(count($pendingIds), $approveResponse->data['approved']);
        
        // Verify all items are now approved
        foreach ($pendingIds as $id) {
            $item = $GLOBALS['wpdb']->get_row("SELECT * FROM wp_ai_agent_actions WHERE id = $id", ARRAY_A);
            $this->assertEquals('approved', $item['status']);
        }
    }

    public function testBatchRejectWithCustomReason(): void
    {
        // Test batch rejection with custom reason
        $customReason = 'Does not meet quality standards';
        $request = new \WP_REST_Request(['ids' => [1, 2], 'reason' => $customReason]);
        $response = $this->controller->batchReject($request);
        
        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertCount(2, $response->data['rejected']);
        
        // Verify custom reason was applied
        foreach ($GLOBALS['wpdb']->operations as $op) {
            if ($op['action'] === 'update') {
                $this->assertEquals($customReason, $op['data']['error']);
            }
        }
    }

    public function testBatchOperationsAuditTrail(): void
    {
        // Test that batch operations create proper audit trail
        $request = new \WP_REST_Request(['ids' => [1, 2, 3]]);
        $response = $this->controller->batchApprove($request);
        
        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        
        // Verify audit operations were recorded
        $auditOperations = array_filter($GLOBALS['wpdb']->operations, function($op) {
            return $op['action'] === 'insert' && $op['table'] === 'wp_ai_agent_audit_log';
        });
        
        // Should have audit entries for each approval
        $this->assertCount(3, $auditOperations);
    }
}

<?php

namespace AIAgent\Tests\Unit\REST\Controllers;

use AIAgent\REST\Controllers\ReviewController;
use AIAgent\Support\Logger;
use PHPUnit\Framework\TestCase;

final class ReviewControllerIntegrationTest extends TestCase
{
    private ReviewController $controller;
    private array $capturedAuditEvents = [];

    protected function setUp(): void
    {
        $this->controller = new ReviewController(new Logger());
        
        // Mock wpdb to capture audit events
        $GLOBALS['wpdb'] = new class($this->capturedAuditEvents) {
            public string $prefix = 'wp_';
            public int $insert_id = 1;
            public string $last_error = '';
            public array $capturedEvents = [];
            
            public function __construct(&$captured) {
                $this->capturedEvents = &$captured;
            }
            
            public function insert($table, $data) { 
                $this->capturedEvents[] = ['action' => 'insert', 'table' => $table, 'data' => $data];
                return 1; 
            }
            
            public function update($table, $data, $where = []) { 
                $this->capturedEvents[] = ['action' => 'update', 'table' => $table, 'data' => $data, 'where' => $where];
                return 1; 
            }
            
            public function get_var($q){ return null; }
            public function get_results($q,$type=ARRAY_A){ return []; }
            public function query($q){ return true; }
            public function prepare($q){ return $q; }
            public function get_charset_collate(){ return ''; }
            public function get_row($q, $type = ARRAY_A) { 
                return ['id' => 1, 'status' => 'pending', 'entity_type' => 'product', 'entity_id' => 123];
            }
        };

        // WordPress functions are now provided by bootstrap.php
    }

    public function testApproveCreatesAuditEntry(): void
    {
        $request = new \WP_REST_Request(['id' => 1]);
        $response = $this->controller->approve($request);
        
        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertTrue($response->data['ok']);
        
        // Check that audit event was captured
        $this->assertNotEmpty($this->capturedAuditEvents);
        
        $auditEvent = end($this->capturedAuditEvents);
        $this->assertEquals('insert', $auditEvent['action']);
        $this->assertEquals('wp_ai_agent_audit_log', $auditEvent['table']);
        $this->assertEquals('review_approved', $auditEvent['data']['action']);
    }

    public function testRejectCreatesAuditEntry(): void
    {
        $request = new \WP_REST_Request(['id' => 1, 'reason' => 'Quality issues']);
        $response = $this->controller->reject($request);
        
        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertTrue($response->data['ok']);
        
        // Check that audit event was captured
        $this->assertNotEmpty($this->capturedAuditEvents);
        
        $auditEvent = end($this->capturedAuditEvents);
        $this->assertEquals('insert', $auditEvent['action']);
        $this->assertEquals('wp_ai_agent_audit_log', $auditEvent['table']);
        $this->assertEquals('review_rejected', $auditEvent['data']['action']);
        // The error message is stored in the JSON data field
        $dataJson = json_decode($auditEvent['data']['data'], true);
        $this->assertEquals('Quality issues', $dataJson['reason'] ?? $dataJson['error'] ?? null);
    }

    public function testRollbackCreatesAuditEntry(): void
    {
        // Mock approved action
        $GLOBALS['wpdb'] = new class($this->capturedAuditEvents) {
            public string $prefix = 'wp_';
            public int $insert_id = 1;
            public string $last_error = '';
            public array $capturedEvents = [];
            
            public function __construct(&$captured) {
                $this->capturedEvents = &$captured;
            }
            
            public function get_row($q, $type = ARRAY_A) { 
                return ['id' => 1, 'status' => 'approved', 'entity_type' => 'product', 'entity_id' => 123];
            }
            
            public function insert($table, $data) { 
                $this->capturedEvents[] = ['action' => 'insert', 'table' => $table, 'data' => $data];
                return 1; 
            }
            
            public function update($table, $data, $where = []) { 
                $this->capturedEvents[] = ['action' => 'update', 'table' => $table, 'data' => $data, 'where' => $where];
                return 1; 
            }
            
            public function get_var($q){ return null; }
            public function get_results($q,$type=ARRAY_A){ return []; }
            public function query($q){ return true; }
            public function prepare($q){ return $q; }
            public function get_charset_collate(){ return ''; }
        };

        $request = new \WP_REST_Request(['id' => 1, 'reason' => 'Rollback requested']);
        $response = $this->controller->rollback($request);
        
        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertTrue($response->data['ok']);
        $this->assertEquals('rolled_back', $response->data['status']);
        
        // Check that audit event was captured
        $this->assertNotEmpty($this->capturedAuditEvents);
        
        // Check that we have both update and insert operations
        $updateEvents = array_filter($this->capturedAuditEvents, fn($e) => $e['action'] === 'update');
        $insertEvents = array_filter($this->capturedAuditEvents, fn($e) => $e['action'] === 'insert');
        
        // Should have 1 update for the action status change
        $this->assertCount(1, $updateEvents);
        $updateEvent = reset($updateEvents);
        $this->assertEquals('wp_ai_agent_actions', $updateEvent['table']);
        $this->assertEquals('rolled_back', $updateEvent['data']['status']);
        
        // Should have 1 insert for the audit log
        $this->assertCount(1, $insertEvents);
        $insertEvent = reset($insertEvents);
        $this->assertEquals('wp_ai_agent_audit_log', $insertEvent['table']);
    }

    public function testBatchApproveCreatesMultipleAuditEntries(): void
    {
        $request = new \WP_REST_Request(['ids' => [1, 2, 3]]);
        $response = $this->controller->batchApprove($request);
        
        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertCount(3, $response->data['approved']);
        $this->assertEmpty($response->data['errors']);
        
        // Check that multiple audit events were captured
        // Each batch operation creates 2 audit entries (action update + audit log)
        $this->assertCount(6, $this->capturedAuditEvents);
        
        // Check that we have both update and insert operations
        $updateEvents = array_filter($this->capturedAuditEvents, fn($e) => $e['action'] === 'update');
        $insertEvents = array_filter($this->capturedAuditEvents, fn($e) => $e['action'] === 'insert');
        
        $this->assertCount(3, $updateEvents);
        $this->assertCount(3, $insertEvents);
    }

    public function testBatchRejectCreatesMultipleAuditEntries(): void
    {
        $request = new \WP_REST_Request(['ids' => [1, 2, 3], 'reason' => 'Batch rejection']);
        $response = $this->controller->batchReject($request);
        
        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertCount(3, $response->data['rejected']);
        $this->assertEmpty($response->data['errors']);
        
        // Check that multiple audit events were captured
        // Each batch operation creates 2 audit entries (action update + audit log)
        $this->assertCount(6, $this->capturedAuditEvents);
        
        // Check that we have both update and insert operations
        $updateEvents = array_filter($this->capturedAuditEvents, fn($e) => $e['action'] === 'update');
        $insertEvents = array_filter($this->capturedAuditEvents, fn($e) => $e['action'] === 'insert');
        
        $this->assertCount(3, $updateEvents);
        $this->assertCount(3, $insertEvents);
    }

    public function testCommentCreatesAuditEntry(): void
    {
        $request = new \WP_REST_Request(['id' => 1, 'comment' => 'This looks good']);
        $response = $this->controller->comment($request);
        
        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertTrue($response->data['ok']);
        
        // Check that audit event was captured
        $this->assertNotEmpty($this->capturedAuditEvents);
        
        $auditEvent = end($this->capturedAuditEvents);
        $this->assertEquals('insert', $auditEvent['action']);
        $this->assertEquals('wp_ai_agent_audit_log', $auditEvent['table']);
        $this->assertEquals('review_comment', $auditEvent['data']['action']);
    }
}

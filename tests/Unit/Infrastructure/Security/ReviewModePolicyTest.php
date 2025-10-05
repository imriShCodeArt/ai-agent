<?php

namespace AIAgent\Tests\Unit\Infrastructure\Security;

use AIAgent\Infrastructure\Security\EnhancedPolicy;
use AIAgent\Support\Logger;
use PHPUnit\Framework\TestCase;

final class ReviewModePolicyTest extends TestCase
{
    private EnhancedPolicy $policy;
    private Logger $logger;

    protected function setUp(): void
    {
        $this->logger = new Logger();
        $this->policy = new EnhancedPolicy($this->logger);
        
        // Mock WordPress functions
        if (!function_exists('get_current_user_id')) {
            function get_current_user_id() { return 1; }
        }
        if (!function_exists('current_user_can')) {
            function current_user_can($capability) { 
                return $capability === 'manage_options'; 
            }
        }
    }

    public function testReviewModeRequiresApprovalForNonAdmin(): void
    {
        // Mock non-admin user
        $GLOBALS['current_user_can_override'] = false;
        
        // Override current_user_can to return false for non-admin
        if (!function_exists('current_user_can')) {
            function current_user_can($capability) { 
                global $current_user_can_override;
                return $current_user_can_override ?? false; 
            }
        }

        $result = $this->policy->isAllowed('products.update', 123, ['name' => 'Test Product'], 'review');
        
        $this->assertFalse($result['allowed']);
        $this->assertEquals('review_mode_approval_required', $result['reason']);
        $this->assertStringContains('Review mode requires approval', $result['details']);
    }

    public function testReviewModeAllowsAdminUsers(): void
    {
        // Mock admin user
        $GLOBALS['current_user_can_override'] = true;
        
        $result = $this->policy->isAllowed('products.update', 123, ['name' => 'Test Product'], 'review');
        
        // Should be allowed for admin users
        $this->assertTrue($result['allowed']);
    }

    public function testSuggestModeDoesNotRequireReviewApproval(): void
    {
        // Mock non-admin user
        $GLOBALS['current_user_can_override'] = false;
        
        $result = $this->policy->isAllowed('products.update', 123, ['name' => 'Test Product'], 'suggest');
        
        // Should be allowed in suggest mode (normal policy rules apply)
        $this->assertTrue($result['allowed']);
    }

    public function testApprovalStateTransitions(): void
    {
        // Test that approval states are properly tracked
        $tool = 'products.update';
        $entityId = 123;
        $userId = 1;
        
        // Initially no approval
        $approvalKey = "review_approval:{$tool}:{$entityId}:{$userId}";
        $hasApproval = $this->policy->getApprovalStatus($approvalKey);
        $this->assertFalse($hasApproval);
        
        // After approval, should have approval
        $this->policy->setApprovalStatus($approvalKey, true);
        $hasApproval = $this->policy->getApprovalStatus($approvalKey);
        $this->assertTrue($hasApproval);
    }

    public function testApprovalWorkflowConditions(): void
    {
        // Test that workflow conditions are properly evaluated
        $conditions = [
            'entity_type' => 'product',
            'price_change_threshold' => 100
        ];
        
        $fields = ['price' => 150];
        $result = $this->policy->evaluateWorkflowConditions($conditions, 'products.update', 123, $fields);
        
        // Should require approval for price changes over threshold
        $this->assertTrue($result);
    }

    public function testApprovalWorkflowBypass(): void
    {
        // Test that admin users can bypass approval workflows
        $GLOBALS['current_user_can_override'] = true;
        
        $result = $this->policy->checkApprovalWorkflow('products.update', 123, ['name' => 'Test'], [
            'approval_workflows' => [
                [
                    'name' => 'High Value Products',
                    'conditions' => ['price' => ['>', 1000]]
                ]
            ]
        ]);
        
        $this->assertTrue($result['allowed']);
        $this->assertEquals('admin_bypass', $result['reason']);
    }
}

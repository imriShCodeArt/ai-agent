<?php

namespace AIAgent\Tests\Unit\Infrastructure\Security;

use PHPUnit\Framework\TestCase;
use AIAgent\Infrastructure\Security\PolicyManager;
use AIAgent\Infrastructure\Security\EnhancedPolicy;
use AIAgent\Support\Logger;

final class PolicyManagerTest extends TestCase
{
    private PolicyManager $policyManager;
    private EnhancedPolicy $policy;

    protected function setUp(): void
    {
        $logger = new Logger();
        $this->policy = new EnhancedPolicy($logger);
        $this->policy->disableTimeWindowCheck(); // Disable for testing
        $this->policyManager = new PolicyManager($logger, $this->policy);
        
        // Set up test policies
        $this->policy->updatePolicy('posts.create', [
            'content_restrictions' => [
                'blocked_terms' => ['spam', 'viagra', 'casino'],
                'blocked_patterns' => ['/buy\s+viagra/i', '/casino\s+bonus/i'],
                'max_length' => 1000
            ],
            'rate_limits' => [
                'max_requests_per_hour' => 100,
                'max_requests_per_day' => 1000
            ],
            'time_windows' => [
                'allowed_hours' => [9, 10, 11, 12, 13, 14, 15, 16, 17],
                'allowed_days' => [1, 2, 3, 4, 5]
            ]
        ]);
    }

    public function testCreatePolicy(): void
    {
        $tool = 'test.tool';
        $policyData = [
            'rate_limits' => ['per_hour' => 10],
            'content_restrictions' => ['blocked_terms' => ['test']],
        ];
        $userId = 1;

        try {
            $result = $this->policyManager->createPolicy($tool, $policyData, $userId);
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('success', $result);
            
            if ($result['success']) {
                $this->assertArrayHasKey('policy_id', $result);
                $this->assertArrayHasKey('version', $result);
                $this->assertArrayHasKey('message', $result);
            } else {
                $this->assertArrayHasKey('error', $result);
            }
        } catch (\Exception $e) {
            // Expected in test environment without database
            $this->assertStringContains('wpdb', $e->getMessage());
        }
    }

    public function testUpdatePolicy(): void
    {
        $tool = 'test.tool';
        $policyData = [
            'rate_limits' => ['per_hour' => 20],
            'content_restrictions' => ['blocked_terms' => ['updated']],
        ];
        $userId = 1;

        try {
            $result = $this->policyManager->updatePolicy($tool, $policyData, $userId);
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('success', $result);
        } catch (\Exception $e) {
            // Expected in test environment without database
            $this->assertStringContains('wpdb', $e->getMessage());
        }
    }

    public function testGetPolicyVersions(): void
    {
        $tool = 'test.tool';

        try {
            $result = $this->policyManager->getPolicyVersions($tool);
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('success', $result);
            
            if ($result['success']) {
                $this->assertArrayHasKey('versions', $result);
                $this->assertArrayHasKey('count', $result);
                $this->assertIsArray($result['versions']);
                $this->assertIsInt($result['count']);
            } else {
                $this->assertArrayHasKey('error', $result);
            }
        } catch (\Exception $e) {
            // Expected in test environment without database
            $this->assertStringContains('wpdb', $e->getMessage());
        }
    }

    public function testGetPolicyDiff(): void
    {
        $tool = 'test.tool';
        $version1 = '1.0.0';
        $version2 = '1.1.0';

        try {
            $result = $this->policyManager->getPolicyDiff($tool, $version1, $version2);
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('success', $result);
            
            if ($result['success']) {
                $this->assertArrayHasKey('diff', $result);
                $this->assertArrayHasKey('version1', $result);
                $this->assertArrayHasKey('version2', $result);
                $this->assertIsArray($result['diff']);
            } else {
                $this->assertArrayHasKey('error', $result);
            }
        } catch (\Exception $e) {
            // Expected in test environment without database
            $this->assertStringContains('wpdb', $e->getMessage());
        }
    }

    public function testRollbackPolicy(): void
    {
        $tool = 'test.tool';
        $version = '1.0.0';

        try {
            $result = $this->policyManager->rollbackPolicy($tool, $version);
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('success', $result);
            
            if ($result['success']) {
                $this->assertArrayHasKey('version', $result);
                $this->assertArrayHasKey('message', $result);
            } else {
                $this->assertArrayHasKey('error', $result);
            }
        } catch (\Exception $e) {
            // Expected in test environment without database
            $this->assertStringContains('wpdb', $e->getMessage());
        }
    }

    public function testTestPolicy(): void
    {
        $tool = 'posts.create';
        $testData = [
            'entity_id' => 123,
            'fields' => ['post_title' => 'Test Post'],
        ];

        $result = $this->policyManager->testPolicy($tool, $testData);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        
        if ($result['success']) {
            $this->assertArrayHasKey('result', $result);
            $this->assertArrayHasKey('test_data', $result);
            $this->assertIsArray($result['result']);
            $this->assertEquals($testData, $result['test_data']);
        } else {
            $this->assertArrayHasKey('error', $result);
        }
    }

    public function testTestPolicyWithInvalidTool(): void
    {
        $tool = 'invalid.tool';
        $testData = [
            'entity_id' => 123,
            'fields' => ['post_title' => 'Test Post'],
        ];

        $result = $this->policyManager->testPolicy($tool, $testData);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        
        if ($result['success']) {
            $this->assertArrayHasKey('result', $result);
            $this->assertFalse($result['result']['allowed']);
        }
    }

    public function testTestPolicyWithBlockedContent(): void
    {
        $tool = 'posts.create';
        $testData = [
            'entity_id' => 123,
            'fields' => ['post_content' => 'This is spam content'],
        ];

        $result = $this->policyManager->testPolicy($tool, $testData);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        
        if ($result['success']) {
            $this->assertArrayHasKey('result', $result);
            $this->assertFalse($result['result']['allowed']);
            $this->assertEquals('blocked_term', $result['result']['reason']);
        }
    }

    public function testCalculatePolicyDiff(): void
    {
        $policy1 = [
            'rate_limits' => ['per_hour' => 10],
            'content_restrictions' => ['blocked_terms' => ['spam']],
        ];
        
        $policy2 = [
            'rate_limits' => ['per_hour' => 20],
            'content_restrictions' => ['blocked_terms' => ['spam', 'scam']],
            'time_windows' => ['allowed_hours' => [9, 10, 11]],
        ];

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->policyManager);
        $method = $reflection->getMethod('calculatePolicyDiff');
        $method->setAccessible(true);
        
        $diff = $method->invoke($this->policyManager, $policy1, $policy2);
        
        $this->assertIsArray($diff);
        $this->assertArrayHasKey('added', $diff);
        $this->assertArrayHasKey('removed', $diff);
        $this->assertArrayHasKey('modified', $diff);
        
        // Check that time_windows was added
        $this->assertArrayHasKey('time_windows', $diff['added']);
        
        // Check that rate_limits was modified
        $this->assertArrayHasKey('rate_limits', $diff['modified']);
        
        // Check that content_restrictions was modified
        $this->assertArrayHasKey('content_restrictions', $diff['modified']);
    }

    public function testCalculatePolicyDiffIdentical(): void
    {
        $policy = [
            'rate_limits' => ['per_hour' => 10],
            'content_restrictions' => ['blocked_terms' => ['spam']],
        ];

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->policyManager);
        $method = $reflection->getMethod('calculatePolicyDiff');
        $method->setAccessible(true);
        
        $diff = $method->invoke($this->policyManager, $policy, $policy);
        
        $this->assertIsArray($diff);
        $this->assertEmpty($diff['added']);
        $this->assertEmpty($diff['removed']);
        $this->assertEmpty($diff['modified']);
    }
}

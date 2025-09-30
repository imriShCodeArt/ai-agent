<?php

namespace AIAgent\REST\Controllers;

use AIAgent\Infrastructure\Security\PolicyManager;
use AIAgent\Infrastructure\Security\EnhancedPolicy;
use AIAgent\Infrastructure\Audit\EnhancedAuditLogger;
use AIAgent\Support\Logger;

final class PolicyController extends BaseRestController
{
    private PolicyManager $policyManager;
    private EnhancedAuditLogger $auditLogger;
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        
        $enhancedPolicy = new EnhancedPolicy($logger);
        $this->policyManager = new PolicyManager($logger, $enhancedPolicy);
        $this->auditLogger = new EnhancedAuditLogger($logger);
    }

    public function register_routes(): void
    {
        register_rest_route('ai-agent/v1', '/policies', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_policies'],
                'permission_callback' => [$this, 'check_policy_read_permission'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_policy'],
                'permission_callback' => [$this, 'check_policy_write_permission'],
                'args' => $this->get_policy_args(),
            ],
        ]);

        register_rest_route('ai-agent/v1', '/policies/(?P<tool>[a-zA-Z0-9_-]+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_policy'],
                'permission_callback' => [$this, 'check_policy_read_permission'],
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_policy'],
                'permission_callback' => [$this, 'check_policy_write_permission'],
                'args' => $this->get_policy_args(),
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_policy'],
                'permission_callback' => [$this, 'check_policy_write_permission'],
            ],
        ]);

        register_rest_route('ai-agent/v1', '/policies/(?P<tool>[a-zA-Z0-9_-]+)/versions', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_policy_versions'],
                'permission_callback' => [$this, 'check_policy_read_permission'],
            ],
        ]);

        register_rest_route('ai-agent/v1', '/policies/(?P<tool>[a-zA-Z0-9_-]+)/diff', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_policy_diff'],
                'permission_callback' => [$this, 'check_policy_read_permission'],
                'args' => [
                    'version1' => [
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'version2' => [
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ],
        ]);

        register_rest_route('ai-agent/v1', '/policies/(?P<tool>[a-zA-Z0-9_-]+)/rollback', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'rollback_policy'],
                'permission_callback' => [$this, 'check_policy_write_permission'],
                'args' => [
                    'version' => [
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ],
        ]);

        register_rest_route('ai-agent/v1', '/policies/(?P<tool>[a-zA-Z0-9_-]+)/test', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'test_policy'],
                'permission_callback' => [$this, 'check_policy_read_permission'],
                'args' => [
                    'entity_id' => [
                        'type' => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                    'fields' => [
                        'type' => 'object',
                        'default' => [],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param mixed $request
     */
    public function get_policies($request): \WP_REST_Response
    {
        try {
            $tool = $request->get_param('tool');
            
            if ($tool) {
                $versions = $this->policyManager->getPolicyVersions($tool);
                return new \WP_REST_Response($versions, 200);
            }
            
            // Return all available policies (this would need to be implemented)
            $policies = [
                'posts.create' => $this->policyManager->getPolicyVersions('posts.create'),
                'posts.update' => $this->policyManager->getPolicyVersions('posts.update'),
            ];
            
            return new \WP_REST_Response([
                'success' => true,
                'policies' => $policies,
            ], 200);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get policies', ['error' => $e->getMessage()]);
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Failed to retrieve policies',
            ], 500);
        }
    }

    /**
     * @param mixed $request
     */
    public function get_policy($request): \WP_REST_Response
    {
        try {
            $tool = $request->get_param('tool');
            $versions = $this->policyManager->getPolicyVersions($tool);
            
            if (empty($versions)) {
                return new \WP_REST_Response([
                    'success' => false,
                    'error' => 'Policy not found',
                ], 404);
            }
            
            return new \WP_REST_Response([
                'success' => true,
                'policy' => $versions[0], // Return latest version
            ], 200);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get policy', ['tool' => $request->get_param('tool'), 'error' => $e->getMessage()]);
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Failed to retrieve policy',
            ], 500);
        }
    }

    /**
     * @param mixed $request
     */
    public function create_policy($request): \WP_REST_Response
    {
        try {
            $tool = $request->get_param('tool');
            $policyData = $request->get_param('policy');
            $userId = get_current_user_id();
            
            $result = $this->policyManager->createPolicy($tool, $policyData, $userId);
            
            if ($result['success']) {
                $this->auditLogger->logSecurityEvent('policy_created', $userId, [
                    'tool' => $tool,
                    'policy_id' => $result['policy_id'],
                    'error_code' => 'POLICY_CREATED',
                    'error_category' => 'configuration',
                ]);
                
                return new \WP_REST_Response($result, 201);
            } else {
                return new \WP_REST_Response($result, 400);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to create policy', ['tool' => $request->get_param('tool'), 'error' => $e->getMessage()]);
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Failed to create policy',
            ], 500);
        }
    }

    /**
     * @param mixed $request
     */
    public function update_policy($request): \WP_REST_Response
    {
        try {
            $tool = $request->get_param('tool');
            $policyData = $request->get_param('policy');
            $userId = get_current_user_id();
            
            $result = $this->policyManager->updatePolicy($tool, $policyData, $userId);
            
            if ($result['success']) {
                $this->auditLogger->logSecurityEvent('policy_updated', $userId, [
                    'tool' => $tool,
                    'policy_id' => $result['policy_id'],
                    'error_code' => 'POLICY_UPDATED',
                    'error_category' => 'configuration',
                ]);
                
                return new \WP_REST_Response($result, 200);
            } else {
                return new \WP_REST_Response($result, 400);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to update policy', ['tool' => $request->get_param('tool'), 'error' => $e->getMessage()]);
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Failed to update policy',
            ], 500);
        }
    }

    /**
     * @param mixed $request
     */
    public function delete_policy($request): \WP_REST_Response
    {
        try {
            $tool = $request->get_param('tool');
            $userId = get_current_user_id();
            
            // In a real implementation, this would mark the policy as inactive
            // rather than actually deleting it for audit purposes
            
            $this->auditLogger->logSecurityEvent('policy_deleted', $userId, [
                'tool' => $tool,
                'error_code' => 'POLICY_DELETED',
                'error_category' => 'configuration',
            ]);
            
            return new \WP_REST_Response([
                'success' => true,
                'message' => 'Policy marked as inactive',
            ], 200);
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete policy', ['tool' => $request->get_param('tool'), 'error' => $e->getMessage()]);
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Failed to delete policy',
            ], 500);
        }
    }

    /**
     * @param mixed $request
     */
    public function get_policy_versions($request): \WP_REST_Response
    {
        try {
            $tool = $request->get_param('tool');
            $result = $this->policyManager->getPolicyVersions($tool);
            
            return new \WP_REST_Response($result, 200);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get policy versions', ['tool' => $request->get_param('tool'), 'error' => $e->getMessage()]);
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Failed to retrieve policy versions',
            ], 500);
        }
    }

    /**
     * @param mixed $request
     */
    public function get_policy_diff($request): \WP_REST_Response
    {
        try {
            $tool = $request->get_param('tool');
            $version1 = $request->get_param('version1');
            $version2 = $request->get_param('version2');
            
            $result = $this->policyManager->getPolicyDiff($tool, $version1, $version2);
            
            return new \WP_REST_Response($result, 200);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get policy diff', [
                'tool' => $request->get_param('tool'),
                'version1' => $request->get_param('version1'),
                'version2' => $request->get_param('version2'),
                'error' => $e->getMessage()
            ]);
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Failed to retrieve policy diff',
            ], 500);
        }
    }

    /**
     * @param mixed $request
     */
    public function rollback_policy($request): \WP_REST_Response
    {
        try {
            $tool = $request->get_param('tool');
            $version = $request->get_param('version');
            $userId = get_current_user_id();
            
            $result = $this->policyManager->rollbackPolicy($tool, $version);
            
            if ($result['success']) {
                $this->auditLogger->logSecurityEvent('policy_rollback', $userId, [
                    'tool' => $tool,
                    'version' => $version,
                    'error_code' => 'POLICY_ROLLBACK',
                    'error_category' => 'configuration',
                ]);
            }
            
            return new \WP_REST_Response($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            $this->logger->error('Failed to rollback policy', [
                'tool' => $request->get_param('tool'),
                'version' => $request->get_param('version'),
                'error' => $e->getMessage()
            ]);
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Failed to rollback policy',
            ], 500);
        }
    }

    /**
     * @param mixed $request
     */
    public function test_policy($request): \WP_REST_Response
    {
        try {
            $tool = $request->get_param('tool');
            $entityId = $request->get_param('entity_id');
            $fields = $request->get_param('fields');
            
            $testData = [
                'entity_id' => $entityId,
                'fields' => $fields,
            ];
            
            $result = $this->policyManager->testPolicy($tool, $testData);
            
            return new \WP_REST_Response($result, 200);
        } catch (\Exception $e) {
            $this->logger->error('Failed to test policy', [
                'tool' => $request->get_param('tool'),
                'error' => $e->getMessage()
            ]);
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Failed to test policy',
            ], 500);
        }
    }

    private function get_policy_args(): array
    {
        return [
            'tool' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_key',
            ],
            'policy' => [
                'required' => true,
                'type' => 'object',
                'validate_callback' => [$this, 'validate_policy_data'],
            ],
        ];
    }

    public function validate_policy_data(mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }
        
        // Basic validation - in a real implementation, this would be more comprehensive
        $allowedKeys = ['rate_limits', 'time_windows', 'content_restrictions', 'entity_rules', 'approval_workflows'];
        
        foreach (array_keys($value) as $key) {
            if (!in_array($key, $allowedKeys, true)) {
                return false;
            }
        }
        
        return true;
    }

    public function check_policy_read_permission(): bool
    {
        return current_user_can('manage_options') || current_user_can('ai_agent_read_policies');
    }

    public function check_policy_write_permission(): bool
    {
        return current_user_can('manage_options') || current_user_can('ai_agent_manage_policies');
    }
}

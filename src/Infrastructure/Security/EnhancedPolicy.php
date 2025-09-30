<?php

namespace AIAgent\Infrastructure\Security;

use AIAgent\Support\Logger;

final class EnhancedPolicy
{
    private Logger $logger;
    private array $policies = [];
    private bool $disableTimeWindowCheck = false;
    private array $rateLimitCache = [];
    private array $approvalWorkflows = [];

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->loadPolicies();
    }

    public function isAllowed(string $tool, ?int $entityId = null, array $fields = []): array
    {
        // Input validation and sanitization
        $tool = $this->sanitizeToolName($tool);
        $entityId = $this->sanitizeEntityId($entityId);
        $fields = $this->sanitizeFields($fields);
        
        if (empty($tool)) {
            $this->logger->warning('Invalid tool name provided', ['tool' => $tool]);
            return ['allowed' => false, 'reason' => 'invalid_tool', 'details' => 'Invalid tool name'];
        }
        
        $policy = $this->getPolicyForTool($tool);
        
        if (!$policy) {
            $this->logger->warning('No policy found for tool', ['tool' => $tool]);
            return ['allowed' => false, 'reason' => 'no_policy', 'details' => 'No policy found for tool'];
        }

        // Check rate limits
        $rateLimitResult = $this->checkRateLimit($tool, $policy);
        if (!$rateLimitResult['allowed']) {
            $this->logger->info('Rate limit exceeded', ['tool' => $tool, 'details' => $rateLimitResult['details']]);
            return $rateLimitResult;
        }

        // Check time windows
        $timeWindowResult = $this->checkTimeWindow($policy);
        if (!$timeWindowResult['allowed']) {
            $this->logger->info('Operation not allowed in current time window', ['tool' => $tool, 'details' => $timeWindowResult['details']]);
            return $timeWindowResult;
        }

        // Check content restrictions
        $contentResult = $this->checkContentRestrictions($fields, $policy);
        if (!$contentResult['allowed']) {
            $this->logger->info('Content restrictions violated', ['tool' => $tool, 'details' => $contentResult['details']]);
            return $contentResult;
        }

        // Check entity-specific rules
        $entityResult = $this->checkEntityRules($tool, $entityId, $policy);
        if (!$entityResult['allowed']) {
            $this->logger->info('Entity rules violated', ['tool' => $tool, 'entity_id' => $entityId, 'details' => $entityResult['details']]);
            return $entityResult;
        }

        // Check approval workflows
        $approvalResult = $this->checkApprovalWorkflow($tool, $entityId, $fields, $policy);
        if (!$approvalResult['allowed']) {
            $this->logger->info('Approval workflow required', ['tool' => $tool, 'details' => $approvalResult['details']]);
            return $approvalResult;
        }

        return ['allowed' => true, 'reason' => 'approved', 'details' => 'All policy checks passed'];
    }

    public function getPolicyForTool(string $tool): ?array
    {
        // Prefer explicit overrides stored using sanitized legacy keys (without dots)
        $legacyKey = str_replace('.', '', $tool);
        if (isset($this->policies[$legacyKey])) {
            return $this->policies[$legacyKey];
        }
        // Fallback to exact dotted key (default policies ship with dotted keys)
        return $this->policies[$tool] ?? null;
    }

    public function getAllPolicies(): array
    {
        return $this->policies;
    }

    public function loadPolicies(): void
    {
        // Load from database or default policies
        $this->policies = $this->getDefaultPolicies();
        $this->logger->info('Policies loaded', ['count' => count($this->policies)]);
    }

    public function updatePolicy(string $tool, array $policy): void
    {
        $this->policies[$tool] = $policy;
        $this->logger->info('Policy updated', ['tool' => $tool]);
    }

    public function disableTimeWindowCheck(): void
    {
        $this->disableTimeWindowCheck = true;
    }

    public function createPolicyVersion(string $tool, array $policy, int $userId = null): int
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ai_agent_policies';
        
        $result = $wpdb->insert(
            $table_name,
            [
                'version' => $this->generateVersionNumber($tool),
                'tool_name' => $tool,
                'policy_doc' => wp_json_encode($policy),
                'created_by' => $userId ?: (function_exists('get_current_user_id') ? get_current_user_id() : 0),
            ],
            ['%s', '%s', '%s', '%d']
        );
        
        if ($result === false) {
            $this->logger->error('Failed to create policy version', ['tool' => $tool, 'error' => $wpdb->last_error]);
            throw new \Exception('Failed to create policy version');
        }
        
        $this->logger->info('Policy version created', ['tool' => $tool, 'version' => $this->generateVersionNumber($tool)]);
        return $wpdb->insert_id;
    }

    public function getPolicyVersions(string $tool): array
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ai_agent_policies';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT id, version, policy_doc, created_at, created_by FROM $table_name 
             WHERE tool_name = %s ORDER BY created_at DESC",
            $tool
        ));
        
        return array_map(function($row) {
            return [
                'id' => (int) $row->id,
                'version' => $row->version,
                'policy' => json_decode($row->policy_doc, true),
                'created_at' => $row->created_at,
                'created_by' => (int) $row->created_by,
            ];
        }, $results);
    }

    private function checkRateLimit(string $tool, array $policy): array
    {
        if (!isset($policy['rate_limits'])) {
            return ['allowed' => true, 'reason' => 'no_rate_limit', 'details' => 'No rate limits configured'];
        }

        $limits = $policy['rate_limits'];
        $currentHour = date('Y-m-d H:00:00');
        $currentDay = date('Y-m-d');
        $userId = function_exists('get_current_user_id') ? get_current_user_id() : 0;
        $ip = $this->getClientIp();

        // Check hourly limit
        if (isset($limits['per_hour'])) {
            $hourlyKey = "rate_limit:{$tool}:hour:{$userId}:{$currentHour}";
            $hourlyCount = $this->getRateLimitCount($hourlyKey);
            
            if ($hourlyCount >= $limits['per_hour']) {
                return [
                    'allowed' => false,
                    'reason' => 'rate_limit_exceeded',
                    'details' => "Hourly limit exceeded: {$hourlyCount}/{$limits['per_hour']}"
                ];
            }
            
            $this->incrementRateLimitCount($hourlyKey, 3600); // 1 hour TTL
        }

        // Check daily limit
        if (isset($limits['per_day'])) {
            $dailyKey = "rate_limit:{$tool}:day:{$userId}:{$currentDay}";
            $dailyCount = $this->getRateLimitCount($dailyKey);
            
            if ($dailyCount >= $limits['per_day']) {
                return [
                    'allowed' => false,
                    'reason' => 'rate_limit_exceeded',
                    'details' => "Daily limit exceeded: {$dailyCount}/{$limits['per_day']}"
                ];
            }
            
            $this->incrementRateLimitCount($dailyKey, 86400); // 24 hours TTL
        }

        // Check IP-based limits
        if (isset($limits['per_ip_hour'])) {
            $ipHourlyKey = "rate_limit:{$tool}:ip_hour:{$ip}:{$currentHour}";
            $ipHourlyCount = $this->getRateLimitCount($ipHourlyKey);
            
            if ($ipHourlyCount >= $limits['per_ip_hour']) {
                return [
                    'allowed' => false,
                    'reason' => 'rate_limit_exceeded',
                    'details' => "IP hourly limit exceeded: {$ipHourlyCount}/{$limits['per_ip_hour']}"
                ];
            }
            
            $this->incrementRateLimitCount($ipHourlyKey, 3600);
        }

        return ['allowed' => true, 'reason' => 'rate_limit_ok', 'details' => 'Rate limits within bounds'];
    }

    private function checkTimeWindow(array $policy): array
    {
        if ($this->disableTimeWindowCheck) {
            return ['allowed' => true, 'reason' => 'time_window_disabled', 'details' => 'Time window check disabled'];
        }

        if (!isset($policy['time_windows'])) {
            return ['allowed' => true, 'reason' => 'no_time_restrictions', 'details' => 'No time restrictions configured'];
        }

        $currentHour = (int) date('H');
        $currentDay = (int) date('N'); // 1 = Monday, 7 = Sunday
        $allowedHours = $policy['time_windows']['allowed_hours'] ?? [];
        $allowedDays = $policy['time_windows']['allowed_days'] ?? [];
        $blackoutWindows = $policy['time_windows']['blackout_windows'] ?? [];

        // Check blackout windows first
        foreach ($blackoutWindows as $blackout) {
            $start = $blackout['start'] ?? '';
            $end = $blackout['end'] ?? '';
            $days = $blackout['days'] ?? [];
            
            if ($this->isInBlackoutWindow($currentHour, $currentDay, $start, $end, $days)) {
                return [
                    'allowed' => false,
                    'reason' => 'blackout_window',
                    'details' => "Operation blocked during blackout window: {$start}-{$end}"
                ];
            }
        }

        // Check allowed hours
        if (!empty($allowedHours) && !in_array($currentHour, $allowedHours, true)) {
            return [
                'allowed' => false,
                'reason' => 'time_restriction',
                'details' => "Operation not allowed at hour {$currentHour}. Allowed hours: " . implode(', ', $allowedHours)
            ];
        }

        // Check allowed days
        if (!empty($allowedDays) && !in_array($currentDay, $allowedDays, true)) {
            $dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            return [
                'allowed' => false,
                'reason' => 'day_restriction',
                'details' => "Operation not allowed on " . $dayNames[$currentDay - 1] . ". Allowed days: " . implode(', ', array_map(fn($d) => $dayNames[$d - 1], $allowedDays))
            ];
        }

        return ['allowed' => true, 'reason' => 'time_window_ok', 'details' => 'Time window check passed'];
    }

    private function checkContentRestrictions(array $fields, array $policy): array
    {
        if (!isset($policy['content_restrictions'])) {
            return ['allowed' => true, 'reason' => 'no_content_restrictions', 'details' => 'No content restrictions configured'];
        }

        $restrictions = $policy['content_restrictions'];
        $blockedTerms = $restrictions['blocked_terms'] ?? [];
        $blockedPatterns = $restrictions['blocked_patterns'] ?? [];
        $severityLevels = $restrictions['severity_levels'] ?? [];

        // Check blocked terms
        foreach ($blockedTerms as $term) {
            foreach ($fields as $field => $value) {
                if (is_string($value) && stripos($value, $term) !== false) {
                    $severity = $severityLevels[$term] ?? 'medium';
                    return [
                        'allowed' => false,
                        'reason' => 'blocked_term',
                        'details' => "Field '{$field}' contains blocked term '{$term}' (severity: {$severity})"
                    ];
                }
            }
        }

        // Check blocked patterns (regex)
        foreach ($blockedPatterns as $pattern) {
            foreach ($fields as $field => $value) {
                if (is_string($value) && preg_match($pattern, $value)) {
                    return [
                        'allowed' => false,
                        'reason' => 'blocked_pattern',
                        'details' => "Field '{$field}' matches blocked pattern: {$pattern}"
                    ];
                }
            }
        }

        return ['allowed' => true, 'reason' => 'content_ok', 'details' => 'Content restrictions check passed'];
    }

    private function checkEntityRules(string $tool, ?int $entityId, array $policy): array
    {
        if (!isset($policy['entity_rules'])) {
            return ['allowed' => true, 'reason' => 'no_entity_rules', 'details' => 'No entity rules configured'];
        }

        $rules = $policy['entity_rules'];
        
        // Check allowed post types
        if (isset($rules['allowed_post_types']) && $entityId) {
            if (!function_exists('get_post_type')) {
                return ['allowed' => true, 'reason' => 'no_entity_rules_env', 'details' => 'WP not available'];
            }
            $postType = get_post_type($entityId);
            if ($postType && !in_array($postType, $rules['allowed_post_types'], true)) {
                return [
                    'allowed' => false,
                    'reason' => 'post_type_restriction',
                    'details' => "Post type '{$postType}' not allowed. Allowed types: " . implode(', ', $rules['allowed_post_types'])
                ];
            }
        }

        // Check allowed statuses
        if (isset($rules['allowed_statuses']) && $entityId) {
            if (!function_exists('get_post_status')) {
                return ['allowed' => true, 'reason' => 'no_entity_rules_env', 'details' => 'WP not available'];
            }
            $status = get_post_status($entityId);
            if ($status && !in_array($status, $rules['allowed_statuses'], true)) {
                return [
                    'allowed' => false,
                    'reason' => 'status_restriction',
                    'details' => "Post status '{$status}' not allowed. Allowed statuses: " . implode(', ', $rules['allowed_statuses'])
                ];
            }
        }

        return ['allowed' => true, 'reason' => 'entity_rules_ok', 'details' => 'Entity rules check passed'];
    }

    private function checkApprovalWorkflow(string $tool, ?int $entityId, array $fields, array $policy): array
    {
        if (!isset($policy['approval_workflows'])) {
            return ['allowed' => true, 'reason' => 'no_approval_required', 'details' => 'No approval workflow configured'];
        }

        $workflows = $policy['approval_workflows'];
        $userId = function_exists('get_current_user_id') ? get_current_user_id() : 0;
        
        // Check if user has bypass permission
        if (function_exists('current_user_can') && current_user_can('manage_options')) {
            return ['allowed' => true, 'reason' => 'admin_bypass', 'details' => 'Admin user bypassed approval workflow'];
        }

        // Check if approval is required for this specific case
        foreach ($workflows as $workflow) {
            $conditions = $workflow['conditions'] ?? [];
            $requiresApproval = $this->evaluateWorkflowConditions($conditions, $tool, $entityId, $fields);
            
            if ($requiresApproval) {
                // Check if approval already exists
                $approvalKey = "approval:{$tool}:{$entityId}:{$userId}";
                $hasApproval = $this->getApprovalStatus($approvalKey);
                
                if (!$hasApproval) {
                    return [
                        'allowed' => false,
                        'reason' => 'approval_required',
                        'details' => "Approval required for workflow: {$workflow['name']}. Please contact an administrator."
                    ];
                }
            }
        }

        return ['allowed' => true, 'reason' => 'approval_ok', 'details' => 'Approval workflow check passed'];
    }

    private function evaluateWorkflowConditions(array $conditions, string $tool, ?int $entityId, array $fields): bool
    {
        foreach ($conditions as $condition) {
            $type = $condition['type'] ?? '';
            $field = $condition['field'] ?? '';
            $operator = $condition['operator'] ?? '';
            $value = $condition['value'] ?? '';

            switch ($type) {
                case 'field_equals':
                    if (($fields[$field] ?? '') === $value) {
                        return true;
                    }
                    break;
                case 'field_contains':
                    if (is_string($fields[$field] ?? '') && stripos($fields[$field], $value) !== false) {
                        return true;
                    }
                    break;
                case 'field_length_greater':
                    if (strlen($fields[$field] ?? '') > (int) $value) {
                        return true;
                    }
                    break;
                case 'field_numeric_greater':
                    if (isset($fields[$field]) && is_numeric($fields[$field]) && (float) $fields[$field] > (float) $value) {
                        return true;
                    }
                    break;
                case 'tool_equals':
                    if ($tool === $value) {
                        return true;
                    }
                    break;
            }
        }

        return false;
    }

    private function getApprovalStatus(string $approvalKey): bool
    {
        // In a real implementation, this would check a database table
        // For now, we'll use a simple cache
        return $this->approvalWorkflows[$approvalKey] ?? false;
    }

    private function isInBlackoutWindow(int $currentHour, int $currentDay, string $start, string $end, array $days): bool
    {
        if (!in_array($currentDay, $days, true)) {
            return false;
        }

        $startHour = (int) substr($start, 0, 2);
        $endHour = (int) substr($end, 0, 2);

        if ($startHour <= $endHour) {
            return $currentHour >= $startHour && $currentHour < $endHour;
        } else {
            // Crosses midnight
            return $currentHour >= $startHour || $currentHour < $endHour;
        }
    }

    private function getRateLimitCount(string $key): int
    {
        // In a real implementation, this would use Redis or similar
        // For now, we'll use a simple array cache
        return $this->rateLimitCache[$key] ?? 0;
    }

    private function incrementRateLimitCount(string $key, int $ttl): void
    {
        // In a real implementation, this would use Redis with TTL
        $this->rateLimitCache[$key] = ($this->rateLimitCache[$key] ?? 0) + 1;
    }

    private function getClientIp(): string
    {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function generateVersionNumber(string $tool): string
    {
        $versions = $this->getPolicyVersions($tool);
        $latestVersion = $versions[0]['version'] ?? '0.0.0';
        
        $parts = explode('.', $latestVersion);
        $parts[2] = (int) $parts[2] + 1;
        
        return implode('.', $parts);
    }

    private function sanitizeToolName(string $tool): string
    {
        // Preserve dots in tool names (e.g., products.update) while removing other unsafe chars
        $lower = strtolower($tool);
        $sanitized = preg_replace('/[^a-z0-9_.-]/', '', $lower);
        return $sanitized !== null ? $sanitized : '';
    }

    private function sanitizeEntityId(?int $entityId): ?int
    {
        return $entityId ? (int) $entityId : null;
    }

    private function sanitizeFields(array $fields): array
    {
        $sanitized = [];
        
        foreach ($fields as $key => $value) {
            $key = function_exists('sanitize_key') ? sanitize_key($key) : preg_replace('/[^a-z0-9_-]/', '', strtolower($key));
            
            if (is_string($value)) {
                $value = function_exists('sanitize_text_field') ? sanitize_text_field($value) : strip_tags($value);
            } elseif (is_array($value)) {
                $value = $this->sanitizeFields($value);
            }
            
            $sanitized[$key] = $value;
        }
        
        return $sanitized;
    }

    private function getDefaultPolicies(): array
    {
        return [
            'products.create' => [
                'rate_limits' => [
                    'per_hour' => 50,
                    'per_day' => 200,
                    'per_ip_hour' => 100,
                ],
                'entity_rules' => [
                    'requires_approval' => false,
                    'price_threshold_approval' => 500.0,
                ],
                'approval_workflows' => [
                    [
                        'name' => 'High Price Creation',
                        'conditions' => [
                            ['type' => 'field_length_greater', 'field' => 'title', 'value' => 120],
                        ],
                    ],
                ],
            ],
            'products.update' => [
                'rate_limits' => [
                    'per_hour' => 100,
                    'per_day' => 500,
                    'per_ip_hour' => 200,
                ],
                'approval_workflows' => [
                    [
                        'name' => 'Large Price Change',
                        'conditions' => [
                            ['type' => 'field_numeric_greater', 'field' => 'price', 'value' => 500],
                        ],
                    ],
                ],
            ],
            'products.bulkUpdate' => [
                'rate_limits' => [
                    'per_hour' => 200,
                    'per_day' => 1000,
                    'per_ip_hour' => 300,
                ],
                'approval_workflows' => [
                    [
                        'name' => 'Bulk Sensitive Change',
                        'conditions' => [
                            ['type' => 'tool_equals', 'field' => '', 'value' => 'products.bulkUpdate'],
                        ],
                    ],
                ],
            ],
            'posts.create' => [
                'rate_limits' => [
                    'per_hour' => 20,
                    'per_day' => 100,
                    'per_ip_hour' => 50,
                ],
                'time_windows' => [
                    'allowed_hours' => [9, 10, 11, 12, 13, 14, 15, 16, 17],
                    'allowed_days' => [1, 2, 3, 4, 5], // Monday to Friday
                    'blackout_windows' => [
                        [
                            'start' => '12:00',
                            'end' => '13:00',
                            'days' => [1, 2, 3, 4, 5],
                        ],
                    ],
                ],
                'content_restrictions' => [
                    'blocked_terms' => ['spam', 'scam', 'phishing'],
                    'blocked_patterns' => ['/\b(viagra|cialis)\b/i', '/\b(free\s+money)\b/i'],
                    'severity_levels' => [
                        'spam' => 'high',
                        'scam' => 'critical',
                        'phishing' => 'critical',
                    ],
                ],
                'entity_rules' => [
                    'allowed_post_types' => ['post', 'page'],
                    'allowed_statuses' => ['draft', 'pending'],
                ],
                'approval_workflows' => [
                    [
                        'name' => 'Long Content Approval',
                        'conditions' => [
                            [
                                'type' => 'field_length_greater',
                                'field' => 'post_content',
                                'value' => 1000,
                            ],
                        ],
                    ],
                ],
            ],
            'posts.update' => [
                'rate_limits' => [
                    'per_hour' => 30,
                    'per_day' => 150,
                    'per_ip_hour' => 75,
                ],
                'time_windows' => [
                    'allowed_hours' => [9, 10, 11, 12, 13, 14, 15, 16, 17],
                    'allowed_days' => [1, 2, 3, 4, 5],
                ],
                'content_restrictions' => [
                    'blocked_terms' => ['spam', 'scam'],
                    'blocked_patterns' => ['/\b(viagra|cialis)\b/i'],
                ],
                'entity_rules' => [
                    'allowed_post_types' => ['post', 'page'],
                    'allowed_statuses' => ['draft', 'pending', 'publish'],
                ],
            ],
        ];
    }
}

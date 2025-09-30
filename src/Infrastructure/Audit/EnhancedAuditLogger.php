<?php

namespace AIAgent\Infrastructure\Audit;

use AIAgent\Support\Logger;

final class EnhancedAuditLogger
{
    private Logger $logger;
    private array $errorTaxonomy = [];

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->initializeErrorTaxonomy();
    }

    public function logAction(
        string $action,
        int $userId,
        string $entityType,
        ?int $entityId,
        string $mode,
        array $data,
        string $status,
        array $policyContext = []
    ): void {
        if (!isset($GLOBALS['wpdb'])) {
            return; // Skip in non-WP environments
        }

        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ai_agent_audit_log';
        
        $logData = [
            'action' => $action,
            'user_id' => $userId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'mode' => $mode,
            'data' => wp_json_encode($data),
            'status' => $status,
            'policy_version' => $policyContext['policy_version'] ?? null,
            'policy_verdict' => $policyContext['policy_verdict'] ?? null,
            'policy_reason' => $policyContext['policy_reason'] ?? null,
            'policy_details' => $policyContext['policy_details'] ?? null,
            'error_code' => $policyContext['error_code'] ?? null,
            'error_category' => $policyContext['error_category'] ?? null,
            'ip_address' => $this->getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => current_time('mysql'),
        ];

        $result = $wpdb->insert($table_name, $logData);
        
        if ($result === false) {
            $this->logger->error('Failed to log audit action', [
                'action' => $action,
                'error' => $wpdb->last_error,
            ]);
        } else {
            $this->logger->info('Audit action logged', [
                'action' => $action,
                'log_id' => $wpdb->insert_id,
            ]);
        }
    }

    public function logPolicyDecision(
        string $tool,
        int $userId,
        ?int $entityId,
        array $fields,
        array $policyResult,
        string $policyVersion = null
    ): void {
        $errorCode = $this->categorizePolicyError($policyResult);
        
        $this->logAction(
            $tool,
            $userId,
            'policy_decision',
            $entityId,
            'policy_check',
            $fields,
            $policyResult['allowed'] ? 'approved' : 'denied',
            [
                'policy_version' => $policyVersion,
                'policy_verdict' => $policyResult['allowed'] ? 'allow' : 'deny',
                'policy_reason' => $policyResult['reason'] ?? 'unknown',
                'policy_details' => $policyResult['details'] ?? '',
                'error_code' => $errorCode['code'],
                'error_category' => $errorCode['category'],
            ]
        );
    }

    public function logSecurityEvent(
        string $eventType,
        int $userId,
        array $context = []
    ): void {
        $this->logAction(
            $eventType,
            $userId,
            'security_event',
            null,
            'security',
            $context,
            'security_event',
            [
                'error_code' => $context['error_code'] ?? 'SECURITY_EVENT',
                'error_category' => $context['error_category'] ?? 'security',
            ]
        );
    }

    public function getAuditLogs(array $filters = []): array
    {
        if (!isset($GLOBALS['wpdb'])) {
            return [];
        }

        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ai_agent_audit_log';
        
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['action'])) {
            $where[] = 'action = %s';
            $params[] = $filters['action'];
        }
        
        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = %d';
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = 'status = %s';
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= %s';
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= %s';
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['error_category'])) {
            $where[] = 'error_category = %s';
            $params[] = $filters['error_category'];
        }
        
        $limit = $filters['limit'] ?? 100;
        $offset = $filters['offset'] ?? 0;
        
        $sql = "SELECT * FROM $table_name WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        $results = $wpdb->get_results($wpdb->prepare($sql, $params));
        
        return array_map(function($row) {
            return [
                'id' => (int) $row->id,
                'action' => $row->action,
                'user_id' => (int) $row->user_id,
                'entity_type' => $row->entity_type,
                'entity_id' => $row->entity_id ? (int) $row->entity_id : null,
                'mode' => $row->mode,
                'data' => json_decode($row->data, true),
                'status' => $row->status,
                'policy_version' => $row->policy_version,
                'policy_verdict' => $row->policy_verdict,
                'policy_reason' => $row->policy_reason,
                'policy_details' => $row->policy_details,
                'error_code' => $row->error_code,
                'error_category' => $row->error_category,
                'ip_address' => $row->ip_address,
                'user_agent' => $row->user_agent,
                'created_at' => $row->created_at,
            ];
        }, $results);
    }

    public function getSecurityMetrics(array $filters = []): array
    {
        if (!isset($GLOBALS['wpdb'])) {
            return [];
        }

        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ai_agent_audit_log';
        
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= %s';
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= %s';
            $params[] = $filters['date_to'];
        }
        
        $sql = "SELECT 
                    status,
                    error_category,
                    COUNT(*) as count,
                    DATE(created_at) as date
                FROM $table_name 
                WHERE " . implode(' AND ', $where) . "
                GROUP BY status, error_category, DATE(created_at)
                ORDER BY date DESC";
        
        $results = $wpdb->get_results($wpdb->prepare($sql, $params));
        
        $metrics = [
            'total_events' => 0,
            'by_status' => [],
            'by_category' => [],
            'by_date' => [],
            'security_events' => 0,
            'policy_denials' => 0,
        ];
        
        foreach ($results as $row) {
            $metrics['total_events'] += (int) $row->count;
            
            if (!isset($metrics['by_status'][$row->status])) {
                $metrics['by_status'][$row->status] = 0;
            }
            $metrics['by_status'][$row->status] += (int) $row->count;
            
            if (!isset($metrics['by_category'][$row->error_category])) {
                $metrics['by_category'][$row->error_category] = 0;
            }
            $metrics['by_category'][$row->error_category] += (int) $row->count;
            
            if (!isset($metrics['by_date'][$row->date])) {
                $metrics['by_date'][$row->date] = 0;
            }
            $metrics['by_date'][$row->date] += (int) $row->count;
            
            if ($row->error_category === 'security') {
                $metrics['security_events'] += (int) $row->count;
            }
            
            if ($row->status === 'denied' && $row->error_category === 'policy') {
                $metrics['policy_denials'] += (int) $row->count;
            }
        }
        
        return $metrics;
    }

    private function categorizePolicyError(array $policyResult): array
    {
        $reason = $policyResult['reason'] ?? 'unknown';
        $details = $policyResult['details'] ?? '';
        
        // Map policy reasons to error codes and categories
        $errorMap = [
            'rate_limit_exceeded' => ['code' => 'RATE_LIMIT_EXCEEDED', 'category' => 'rate_limit'],
            'time_restriction' => ['code' => 'TIME_RESTRICTION', 'category' => 'policy'],
            'day_restriction' => ['code' => 'DAY_RESTRICTION', 'category' => 'policy'],
            'blackout_window' => ['code' => 'BLACKOUT_WINDOW', 'category' => 'policy'],
            'blocked_term' => ['code' => 'CONTENT_BLOCKED', 'category' => 'content'],
            'blocked_pattern' => ['code' => 'PATTERN_BLOCKED', 'category' => 'content'],
            'post_type_restriction' => ['code' => 'POST_TYPE_RESTRICTED', 'category' => 'entity'],
            'status_restriction' => ['code' => 'STATUS_RESTRICTED', 'category' => 'entity'],
            'approval_required' => ['code' => 'APPROVAL_REQUIRED', 'category' => 'workflow'],
            'no_policy' => ['code' => 'NO_POLICY', 'category' => 'configuration'],
            'invalid_tool' => ['code' => 'INVALID_TOOL', 'category' => 'validation'],
        ];
        
        return $errorMap[$reason] ?? ['code' => 'UNKNOWN_ERROR', 'category' => 'unknown'];
    }

    private function initializeErrorTaxonomy(): void
    {
        $this->errorTaxonomy = [
            'rate_limit' => [
                'name' => 'Rate Limiting',
                'description' => 'Requests exceeding configured rate limits',
                'severity' => 'medium',
            ],
            'policy' => [
                'name' => 'Policy Violations',
                'description' => 'Actions not allowed by current policies',
                'severity' => 'high',
            ],
            'content' => [
                'name' => 'Content Restrictions',
                'description' => 'Content violating filtering rules',
                'severity' => 'high',
            ],
            'entity' => [
                'name' => 'Entity Restrictions',
                'description' => 'Actions on restricted entity types or statuses',
                'severity' => 'medium',
            ],
            'workflow' => [
                'name' => 'Approval Workflows',
                'description' => 'Actions requiring approval',
                'severity' => 'low',
            ],
            'configuration' => [
                'name' => 'Configuration Issues',
                'description' => 'Missing or invalid configuration',
                'severity' => 'critical',
            ],
            'validation' => [
                'name' => 'Validation Errors',
                'description' => 'Invalid input or parameters',
                'severity' => 'medium',
            ],
            'security' => [
                'name' => 'Security Events',
                'description' => 'Security-related events and alerts',
                'severity' => 'critical',
            ],
            'unknown' => [
                'name' => 'Unknown Errors',
                'description' => 'Uncategorized errors',
                'severity' => 'medium',
            ],
        ];
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
}

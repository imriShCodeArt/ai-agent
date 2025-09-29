<?php

namespace AIAgent\Infrastructure\Security;

use AIAgent\Support\Logger;

final class Policy
{
    private Logger $logger;
    private array $policies = [];

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->loadPolicies();
    }

    public function isAllowed(string $tool, int $entityId = null, array $fields = []): bool
    {
        // Input validation and sanitization
        $tool = $this->sanitizeToolName($tool);
        $entityId = $this->sanitizeEntityId($entityId);
        $fields = $this->sanitizeFields($fields);
        
        if (empty($tool)) {
            $this->logger->warning('Invalid tool name provided', ['tool' => $tool]);
            return false;
        }
        
        $policy = $this->getPolicyForTool($tool);
        
        if (!$policy) {
            $this->logger->warning('No policy found for tool', ['tool' => $tool]);
            return false;
        }

        // Check rate limits
        if (!$this->checkRateLimit($tool, $policy)) {
            $this->logger->info('Rate limit exceeded', ['tool' => $tool]);
            return false;
        }

        // Check time windows
        if (!$this->checkTimeWindow($policy)) {
            $this->logger->info('Operation not allowed in current time window', ['tool' => $tool]);
            return false;
        }

        // Check content restrictions
        if (!$this->checkContentRestrictions($fields, $policy)) {
            $this->logger->info('Content restrictions violated', ['tool' => $tool]);
            return false;
        }

        // Check entity-specific rules
        if (!$this->checkEntityRules($tool, $entityId, $policy)) {
            $this->logger->info('Entity rules violated', ['tool' => $tool, 'entity_id' => $entityId]);
            return false;
        }

        return true;
    }

    public function getPolicyForTool(string $tool): ?array
    {
        return $this->policies[$tool] ?? null;
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

    private function checkRateLimit(string $tool, array $policy): bool
    {
        if (!isset($policy['rate_limits'])) {
            return true;
        }

        $limits = $policy['rate_limits'];
        $currentHour = date('Y-m-d H:00:00');
        $currentDay = date('Y-m-d');

        // Check hourly limit
        if (isset($limits['per_hour'])) {
            $hourlyCount = $this->getOperationCount($tool, $currentHour);
            if ($hourlyCount >= $limits['per_hour']) {
                return false;
            }
        }

        // Check daily limit
        if (isset($limits['per_day'])) {
            $dailyCount = $this->getOperationCount($tool, $currentDay);
            if ($dailyCount >= $limits['per_day']) {
                return false;
            }
        }

        return true;
    }

    private function checkTimeWindow(array $policy): bool
    {
        if (!isset($policy['time_windows'])) {
            return true;
        }

        $currentHour = (int) date('H');
        $allowedHours = $policy['time_windows']['allowed_hours'] ?? [];

        if (!empty($allowedHours) && !in_array($currentHour, $allowedHours, true)) {
            return false;
        }

        return true;
    }

    private function checkContentRestrictions(array $fields, array $policy): bool
    {
        if (!isset($policy['content_restrictions'])) {
            return true;
        }

        $restrictions = $policy['content_restrictions'];

        // Check blocked terms
        if (isset($restrictions['blocked_terms'])) {
            $content = $this->extractContent($fields);
            foreach ($restrictions['blocked_terms'] as $term) {
                if (stripos($content, $term) !== false) {
                    return false;
                }
            }
        }

        // Check regex patterns
        if (isset($restrictions['blocked_patterns'])) {
            $content = $this->extractContent($fields);
            foreach ($restrictions['blocked_patterns'] as $pattern) {
                if (preg_match($pattern, $content)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function checkEntityRules(string $tool, ?int $entityId, array $policy): bool
    {
        if (!isset($policy['entity_rules'])) {
            return true;
        }

        $rules = $policy['entity_rules'];

        // Check allowed post types
        if (isset($rules['allowed_post_types']) && $entityId) {
            $post = get_post($entityId);
            if ($post && !in_array($post->post_type, $rules['allowed_post_types'], true)) {
                return false;
            }
        }

        // Check allowed statuses
        if (isset($rules['allowed_statuses']) && $entityId) {
            $post = get_post($entityId);
            if ($post && !in_array($post->post_status, $rules['allowed_statuses'], true)) {
                return false;
            }
        }

        return true;
    }

    private function getOperationCount(string $tool, string $timeframe): int
    {
        global $wpdb;
        if (!($wpdb instanceof \wpdb)) {
            // In non-WordPress contexts (unit tests), treat as zero prior operations
            return 0;
        }
        /** @var \wpdb $wpdb */
        $table_name = $wpdb->prefix . 'ai_agent_actions';

        $sql = "SELECT COUNT(*) FROM $table_name WHERE tool = %s AND ts >= %s";
        $prepared = $wpdb->prepare($sql, $tool, $timeframe);
        $count = $wpdb->get_var($prepared);

        return (int) $count;
    }

    private function extractContent(array $fields): string
    {
        $content = '';
        $contentFields = ['post_content', 'post_title', 'post_excerpt', 'description', 'short_description'];
        
        foreach ($contentFields as $field) {
            if (isset($fields[$field])) {
                $content .= ' ' . $fields[$field];
            }
        }

        return $content;
    }

    /**
     * Sanitize tool name to prevent injection attacks
     */
    private function sanitizeToolName(string $tool): string
    {
        // Remove any non-alphanumeric characters except dots and underscores
        $sanitized = preg_replace('/[^a-zA-Z0-9._-]/', '', $tool);
        
        // Ensure we have a string before using substr
        if ($sanitized === null) {
            $sanitized = '';
        }
        
        // Limit length to prevent buffer overflow
        return substr($sanitized, 0, 50);
    }

    /**
     * Sanitize entity ID to ensure it's a positive integer
     */
    private function sanitizeEntityId(?int $entityId): ?int
    {
        if ($entityId === null) {
            return null;
        }
        
        // Ensure positive integer
        return max(0, (int) $entityId);
    }

    /**
     * Sanitize fields array to prevent XSS and injection attacks
     */
    private function sanitizeFields(array $fields): array
    {
        $sanitized = [];
        
        foreach ($fields as $key => $value) {
            // Sanitize key with WP fallback
            if (function_exists('sanitize_key')) {
                $sanitizedKey = sanitize_key($key);
            } else {
                $sanitizedKey = preg_replace('/[^a-z0-9_]/i', '', (string) $key) ?? '';
                $sanitizedKey = strtolower($sanitizedKey);
            }

            if ($sanitizedKey === '') {
                continue;
            }

            // Sanitize value based on type with WP fallbacks
            if (is_string($value)) {
                $text = (string) $value;
                if (function_exists('wp_strip_all_tags')) {
                    $text = wp_strip_all_tags($text);
                } else {
                    $text = strip_tags($text);
                }
                if (function_exists('sanitize_text_field')) {
                    $sanitizedValue = sanitize_text_field($text);
                } else {
                    $sanitizedValue = trim($text);
                }
            } elseif (is_array($value)) {
                $sanitizedValue = $this->sanitizeFields($value);
            } elseif (is_numeric($value)) {
                $sanitizedValue = (int) $value;
            } else {
                $text = (string) $value;
                if (function_exists('sanitize_text_field')) {
                    $sanitizedValue = sanitize_text_field($text);
                } else {
                    $sanitizedValue = trim(strip_tags($text));
                }
            }

            $sanitized[$sanitizedKey] = $sanitizedValue;
        }
        
        return $sanitized;
    }

    private function getDefaultPolicies(): array
    {
        return [
            'posts.create' => [
                'rate_limits' => [
                    'per_hour' => 20,
                    'per_day' => 100,
                ],
                'time_windows' => [
                    'allowed_hours' => [9, 10, 11, 12, 13, 14, 15, 16, 17], // 9 AM to 5 PM
                ],
                'content_restrictions' => [
                    'blocked_terms' => ['spam', 'scam'],
                    'blocked_patterns' => ['/\b(viagra|cialis)\b/i'],
                ],
                'entity_rules' => [
                    'allowed_post_types' => ['post', 'page'],
                    'allowed_statuses' => ['draft', 'pending'],
                ],
            ],
            'posts.update' => [
                'rate_limits' => [
                    'per_hour' => 30,
                    'per_day' => 150,
                ],
                'time_windows' => [
                    'allowed_hours' => [9, 10, 11, 12, 13, 14, 15, 16, 17],
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
            'posts.delete' => [
                'rate_limits' => [
                    'per_hour' => 5,
                    'per_day' => 20,
                ],
                'time_windows' => [
                    'allowed_hours' => [9, 10, 11, 12, 13, 14, 15, 16, 17],
                ],
                'entity_rules' => [
                    'allowed_post_types' => ['post', 'page'],
                    'requires_approval' => true,
                ],
            ],
        ];
    }
}

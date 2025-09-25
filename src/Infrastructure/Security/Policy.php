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
        $table_name = $wpdb->prefix . 'ai_agent_actions';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE tool = %s AND ts >= %s",
            $tool,
            $timeframe
        ));

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

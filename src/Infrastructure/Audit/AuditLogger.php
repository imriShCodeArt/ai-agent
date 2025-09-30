<?php

namespace AIAgent\Infrastructure\Audit;

use AIAgent\Support\Logger;

final class AuditLogger
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function logAction(
        string $tool,
        int $userId,
        string $entityType,
        ?int $entityId,
        string $mode,
        array $payload,
        string $status,
        ?string $error = null,
        ?array $policyVerdict = null
    ): int {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_agent_actions';

        // Redact sensitive data from payload
        $redactedPayload = $this->redactPayload($payload);

        // Generate hashes for before/after comparison
        $beforeHash = $this->generateEntityHash($entityType, $entityId);
        $afterHash = $this->generateEntityHash($entityType, $entityId, $payload);

        $data = [
            'user_id' => $userId,
            'tool' => $tool,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'mode' => $mode,
            'payload_redacted' => wp_json_encode($redactedPayload),
            'before_hash' => $beforeHash,
            'after_hash' => $afterHash,
            'status' => $status,
            'error' => $error,
            'policy_verdict' => $policyVerdict ? wp_json_encode($policyVerdict) : null,
        ];

        $result = $wpdb->insert($table_name, $data);

        if ($result === false) {
            $this->logger->error('Failed to log audit action', [
                'tool' => $tool,
                'user_id' => $userId,
                'error' => $wpdb->last_error,
            ]);
            throw new \Exception('Failed to log audit action: ' . $wpdb->last_error);
        }

        $actionId = $wpdb->insert_id;
        
        $this->logger->info('Audit action logged', [
            'action_id' => $actionId,
            'tool' => $tool,
            'user_id' => $userId,
            'status' => $status,
        ]);

        return $actionId;
    }

    public function getActions(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_agent_actions';

        $where = ['1=1'];
        $values = [];

        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = %d';
            $values[] = $filters['user_id'];
        }

        if (!empty($filters['tool'])) {
            $where[] = 'tool = %s';
            $values[] = $filters['tool'];
        }

        if (!empty($filters['entity_type'])) {
            $where[] = 'entity_type = %s';
            $values[] = $filters['entity_type'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'status = %s';
            $values[] = $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'ts >= %s';
            $values[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'ts <= %s';
            $values[] = $filters['date_to'];
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT * FROM $table_name WHERE $whereClause ORDER BY ts DESC LIMIT %d OFFSET %d";
        $values[] = $limit;
        $values[] = $offset;

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        $results = $wpdb->get_results($sql, ARRAY_A);

        return $results ?: [];
    }

    public function getActionById(int $actionId): ?array
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_agent_actions';

        $result = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $actionId),
            ARRAY_A
        );

        return $result ?: null;
    }

    public function generateDiff(int $actionId): ?string
    {
        $action = $this->getActionById($actionId);
        if (!$action) {
            return null;
        }

        $beforeHash = $action['before_hash'];
        $afterHash = $action['after_hash'];

        if ($beforeHash === $afterHash) {
            return 'No changes detected';
        }

        // Get the entity before and after states
        $beforeContent = $this->getEntityContent($action['entity_type'], $action['entity_id'], $beforeHash);
        $afterContent = $this->getEntityContent($action['entity_type'], $action['entity_id'], $afterHash);

        if (!$beforeContent || !$afterContent) {
            return 'Unable to generate diff - content not available';
        }

        // Use WordPress built-in diff function
        $diff = wp_text_diff($beforeContent, $afterContent, [
            'show_split_view' => true,
            'title_left' => 'Before',
            'title_right' => 'After',
        ]);

        return $diff;
    }

    private function redactPayload(array $payload): array
    {
        $sensitiveFields = ['password', 'secret', 'key', 'token', 'auth', 'email', 'phone'];
        $redacted = $payload;

        foreach ($sensitiveFields as $field) {
            if (isset($redacted[$field])) {
                $redacted[$field] = '[REDACTED]';
            }
        }

        return $redacted;
    }

    private function generateEntityHash(string $entityType, ?int $entityId, array $payload = []): ?string
    {
        if (!$entityId) {
            return null;
        }

        $content = '';
        
        switch ($entityType) {
            case 'post':
                $post = get_post($entityId);
                if ($post) {
                    $content = $post->post_title . '|' . $post->post_content . '|' . $post->post_excerpt;
                }
                break;
            case 'media':
                $attachment = get_post($entityId);
                if ($attachment) {
                    $content = $attachment->post_title . '|' . $attachment->post_content;
                }
                break;
        }

        // Include payload changes if provided
        if (!empty($payload)) {
            $content .= '|' . wp_json_encode($payload);
        }

        return hash('sha256', $content);
    }

    private function getEntityContent(string $entityType, ?int $entityId, string $hash): ?string
    {
        // This is a simplified version - in a real implementation,
        // you might store snapshots of the entity state
        if (!$entityId) {
            return null;
        }

        switch ($entityType) {
            case 'post':
                $post = get_post($entityId);
                return $post ? $post->post_content : null;
            case 'media':
                $attachment = get_post($entityId);
                return $attachment ? $attachment->post_content : null;
        }

        return null;
    }
}

<?php

namespace AIAgent\REST\Controllers;

use AIAgent\Infrastructure\Audit\AuditLogger;
use AIAgent\Infrastructure\Security\Policy;
use AIAgent\Support\Logger;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

final class ChatController extends BaseRestController
{
    private Policy $policy;
    private AuditLogger $auditLogger;
    private Logger $logger;

    public function __construct(
        Policy $policy,
        AuditLogger $auditLogger,
        Logger $logger
    ) {
        $this->policy = $policy;
        $this->auditLogger = $auditLogger;
        $this->logger = $logger;
    }

    public function chat(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        // Verify nonce for security
        if (!wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest')) {
            return new WP_Error(
                'invalid_nonce',
                'Invalid nonce provided',
                ['status' => 403]
            );
        }

        // Check user capabilities
        if (!current_user_can('ai_agent_read')) {
            return new WP_Error(
                'insufficient_permissions',
                'Insufficient permissions to use AI Agent',
                ['status' => 403]
            );
        }

        $prompt = $request->get_param('prompt');
        $mode = $request->get_param('mode') ?? 'suggest';
        $sessionId = $request->get_param('session_id') ?? wp_generate_uuid4();

        // Sanitize and validate input
        $prompt = sanitize_textarea_field($prompt);
        $mode = sanitize_text_field($mode);
        $sessionId = sanitize_text_field($sessionId);

        if (empty($prompt)) {
            return new WP_Error(
                'missing_prompt',
                'Prompt is required',
                ['status' => 400]
            );
        }

        // Validate mode parameter
        $allowedModes = ['suggest', 'autonomous', 'review'];
        if (!in_array($mode, $allowedModes, true)) {
            return new WP_Error(
                'invalid_mode',
                'Invalid mode specified',
                ['status' => 400]
            );
        }

        $userId = get_current_user_id();
        if (!$userId) {
            return new WP_Error(
                'unauthorized',
                'User must be logged in',
                ['status' => 401]
            );
        }

        try {
            // Log the chat request
            $this->auditLogger->logAction(
                'chat.request',
                $userId,
                'chat',
                null,
                $mode,
                ['prompt' => $prompt, 'session_id' => $sessionId],
                'success'
            );

            // Process the prompt and generate response
            $response = $this->processPrompt($prompt, $mode, $sessionId, $userId);

            return new WP_REST_Response([
                'success' => true,
                'data' => $response,
                'session_id' => $sessionId,
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Chat request failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'prompt' => $prompt,
            ]);

            $this->auditLogger->logAction(
                'chat.request',
                $userId,
                'chat',
                null,
                $mode,
                ['prompt' => $prompt, 'session_id' => $sessionId],
                'error',
                $e->getMessage()
            );

            return new WP_Error(
                'chat_failed',
                'Failed to process chat request',
                ['status' => 500]
            );
        }
    }

    public function dryRun(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        // Verify nonce for security
        if (!wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest')) {
            return new WP_Error(
                'invalid_nonce',
                'Invalid nonce provided',
                ['status' => 403]
            );
        }

        // Check user capabilities
        if (!current_user_can('ai_agent_read')) {
            return new WP_Error(
                'insufficient_permissions',
                'Insufficient permissions to use AI Agent',
                ['status' => 403]
            );
        }

        $tool = $request->get_param('tool');
        $entityId = $request->get_param('entity_id');
        $fields = $request->get_param('fields') ?? [];

        // Sanitize and validate input
        $tool = sanitize_text_field($tool);
        $entityId = $entityId ? (int) $entityId : null;
        $fields = is_array($fields) ? $fields : [];

        if (empty($tool)) {
            return new WP_Error(
                'missing_tool',
                'Tool is required',
                ['status' => 400]
            );
        }

        $userId = get_current_user_id();
        if (!$userId) {
            return new WP_Error(
                'unauthorized',
                'User must be logged in',
                ['status' => 401]
            );
        }

        try {
            // Check if the operation is allowed by policy
            $isAllowed = $this->policy->isAllowed($tool, $entityId, $fields);
            
            if (!$isAllowed) {
                return new WP_Error(
                    'policy_blocked',
                    'Operation not allowed by policy',
                    ['status' => 403]
                );
            }

            // Generate diff preview
            $diff = $this->generateDiffPreview($tool, $entityId, $fields);

            // Log the dry run
            $this->auditLogger->logAction(
                'dry_run',
                $userId,
                $tool,
                $entityId,
                'suggest',
                ['tool' => $tool, 'entity_id' => $entityId, 'fields' => $fields],
                'success'
            );

            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'allowed' => true,
                    'diff' => $diff,
                    'policy_verdict' => $this->policy->getPolicyForTool($tool),
                ],
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Dry run failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'tool' => $tool,
            ]);

            return new WP_Error(
                'dry_run_failed',
                'Failed to process dry run',
                ['status' => 500]
            );
        }
    }

    private function processPrompt(string $prompt, string $mode, string $sessionId, int $userId): array
    {
        // This is a placeholder implementation
        // In a real implementation, this would:
        // 1. Send the prompt to an external LLM service
        // 2. Parse the LLM response for tool calls
        // 3. Execute the tools if in autonomous mode
        // 4. Return the response with suggested actions

        $response = [
            'message' => 'This is a placeholder response. The AI Agent is not yet connected to an LLM service.',
            'suggested_actions' => [],
            'mode' => $mode,
        ];

        // Example suggested actions based on prompt analysis
        if (stripos($prompt, 'create') !== false && stripos($prompt, 'post') !== false) {
            $response['suggested_actions'][] = [
                'tool' => 'posts.create',
                'description' => 'Create a new post',
                'parameters' => [
                    'post_title' => 'New Post Title',
                    'post_content' => 'Post content based on your request',
                    'post_status' => 'draft',
                ],
            ];
        }

        if (stripos($prompt, 'update') !== false && stripos($prompt, 'post') !== false) {
            $response['suggested_actions'][] = [
                'tool' => 'posts.update',
                'description' => 'Update an existing post',
                'parameters' => [
                    'id' => 1, // This would be determined by the LLM
                    'fields' => [
                        'post_title' => 'Updated Post Title',
                        'post_content' => 'Updated post content',
                    ],
                ],
            ];
        }

        return $response;
    }

    private function generateDiffPreview(string $tool, ?int $entityId, array $fields): ?string
    {
        if (!$entityId) {
            return null;
        }

        // Get current entity state
        $entity = get_post($entityId);
        if (!$entity) {
            return null;
        }

        // Create a preview of what the entity would look like after changes
        $preview = clone $entity;
        
        foreach ($fields as $field => $value) {
            if (property_exists($preview, $field)) {
                $preview->$field = $value;
            }
        }

        // Generate diff
        $before = $entity->post_content;
        $after = $preview->post_content;

        if ($before === $after) {
            return 'No changes detected';
        }

        return wp_text_diff($before, $after, [
            'show_split_view' => true,
            'title_left' => 'Current',
            'title_right' => 'Proposed',
        ]);
    }
}

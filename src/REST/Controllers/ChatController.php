<?php

namespace AIAgent\REST\Controllers;

use AIAgent\Infrastructure\Audit\AuditLogger;
use AIAgent\Infrastructure\Security\Policy;
use AIAgent\Infrastructure\LLM\LLMProviderInterface;
use AIAgent\Infrastructure\Tools\ToolExecutionEngine;
use AIAgent\Support\Logger;
use WP_REST_Response;
use WP_Error;

final class ChatController extends BaseRestController
{
    private Policy $policy;
    private AuditLogger $auditLogger;
    private Logger $logger;
    private LLMProviderInterface $llm;
    private ToolExecutionEngine $engine;

    public function __construct(
        Policy $policy,
        AuditLogger $auditLogger,
        Logger $logger,
        LLMProviderInterface $llm,
        ToolExecutionEngine $engine
    ) {
        $this->policy = $policy;
        $this->auditLogger = $auditLogger;
        $this->logger = $logger;
        $this->llm = $llm;
        $this->engine = $engine;
    }

    /**
     * @param mixed $request
     */
    public function chat($request): WP_REST_Response|WP_Error
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

    /**
     * @param mixed $request
     */
    public function dryRun($request): WP_REST_Response|WP_Error
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

    /**
     * @param mixed $request
     */
    public function execute($request): WP_REST_Response|WP_Error
    {
        if (!wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest')) {
            return new WP_Error('invalid_nonce', 'Invalid nonce provided', ['status' => 403]);
        }
        if (!current_user_can('ai_agent_read')) {
            return new WP_Error('insufficient_permissions', 'Insufficient permissions', ['status' => 403]);
        }

        $tool = sanitize_text_field($request->get_param('tool'));
        $entityId = $request->get_param('entity_id');
        $fields = $request->get_param('fields') ?? [];
        $mode = sanitize_text_field($request->get_param('mode') ?? 'autonomous');

        $entityId = $entityId ? (int) $entityId : null;
        $fields = is_array($fields) ? $fields : [];

        // Run via engine
        $result = $this->engine->run($tool, [
            'fields' => $fields,
            'entity_id' => $entityId,
            'entity_type' => 'generic',
            'mode' => $mode,
        ]);

        if (isset($result['error'])) {
            return new WP_Error('execute_failed', (string) ($result['message'] ?? $result['error']), ['status' => 400]);
        }

        return new WP_REST_Response(['success' => true, 'data' => $result]);
    }

    private function processPrompt(string $prompt, string $mode, string $sessionId, int $userId): array
    {
        // Ask the LLM for a suggested summary and tools
        $llmResult = $this->llm->complete('Summarize user intent and propose a tool if relevant: ' . $prompt);

        $suggestedActions = [];
        // naive rule: if prompt mentions "summarize", suggest text.summarize
        if (stripos($prompt, 'summarize') !== false || stripos($llmResult, 'summary') !== false) {
            $suggestedActions[] = [
                'tool' => 'text.summarize',
                'parameters' => [
                    'content' => $prompt,
                    'length' => 'short',
                ],
            ];
        }

        $executed = null;
        if ($mode === 'autonomous' && !empty($suggestedActions)) {
            $first = $suggestedActions[0];
            $executed = $this->engine->run($first['tool'], [
                'fields' => $first['parameters'],
                'entity_type' => 'text',
                'mode' => $mode,
            ]);
        }

        return [
            'message' => $llmResult,
            'suggested_actions' => $suggestedActions,
            'executed' => $executed,
            'mode' => $mode,
        ];
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

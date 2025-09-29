<?php
namespace AIAgent\Infrastructure\Tools;

use AIAgent\Infrastructure\Security\Policy;
use AIAgent\Infrastructure\Security\Capabilities;
use AIAgent\Infrastructure\Audit\AuditLogger;

final class ToolExecutionEngine
{
    private ToolRegistry $registry;
    private Policy $policy;
    private Capabilities $capabilities;
    private AuditLogger $auditLogger;

    public function __construct(
        ToolRegistry $registry,
        Policy $policy,
        Capabilities $capabilities,
        AuditLogger $auditLogger
    ) {
        $this->registry = $registry;
        $this->policy = $policy;
        $this->capabilities = $capabilities;
        $this->auditLogger = $auditLogger;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function run(string $toolName, array $input): array
    {
        // Basic capability and policy check placeholders; real checks can be expanded
        if (!current_user_can(Capabilities::EXECUTE_TOOL)) {
            return ['error' => 'forbidden'];
        }
        // Policy expects (tool, ?int $entityId, array $fields)
        $entityId = isset($input['entity_id']) && is_numeric($input['entity_id']) ? (int) $input['entity_id'] : null;
        $fields = isset($input['fields']) && is_array($input['fields']) ? $input['fields'] : [];
        if (!$this->policy->isAllowed($toolName, $entityId, $fields)) {
            return ['error' => 'policy_denied'];
        }

        $tool = $this->registry->get($toolName);
        // Minimal audit trail compatible with AuditLogger API
        $userId = get_current_user_id();
        $mode = is_string($input['mode'] ?? null) ? (string) $input['mode'] : 'suggest';
        $entityType = is_string($input['entity_type'] ?? null) ? (string) $input['entity_type'] : 'generic';
        $this->auditLogger->logAction($toolName, (int) $userId, $entityType, $entityId, $mode, $input, 'started');
        $result = $tool->execute($input);
        $this->auditLogger->logAction($toolName, (int) $userId, $entityType, $entityId, $mode, $result, 'completed');
        return $result;
    }
}



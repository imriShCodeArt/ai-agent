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
        if (!$this->capabilities->currentUserCan('ai_agent_execute_tool')) {
            return ['error' => 'forbidden'];
        }
        if (!$this->policy->isAllowed('tool', $toolName, $input)) {
            return ['error' => 'policy_denied'];
        }

        $tool = $this->registry->get($toolName);
        $this->auditLogger->log('tool_execute', ['tool' => $toolName, 'input' => $input]);
        $result = $tool->execute($input);
        $this->auditLogger->log('tool_result', ['tool' => $toolName, 'result' => $result]);
        return $result;
    }
}



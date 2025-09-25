<?php

namespace AIAgent\REST\Controllers;

use AIAgent\Infrastructure\Audit\AuditLogger;
use AIAgent\Support\Logger;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

final class LogsController extends BaseRestController
{
    private AuditLogger $auditLogger;
    private Logger $logger;

    public function __construct(AuditLogger $auditLogger, Logger $logger)
    {
        $this->auditLogger = $auditLogger;
        $this->logger = $logger;
    }

    public function getLogs(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $userId = get_current_user_id();
        if (!$userId) {
            return new WP_Error(
                'unauthorized',
                'User must be logged in',
                ['status' => 401]
            );
        }

        // Check capabilities
        if (!current_user_can('ai_agent_view_logs')) {
            return new WP_Error(
                'forbidden',
                'Insufficient permissions to view logs',
                ['status' => 403]
            );
        }

        $filters = [
            'user_id' => $request->get_param('user_id'),
            'tool' => $request->get_param('tool'),
            'entity_type' => $request->get_param('entity_type'),
            'status' => $request->get_param('status'),
            'date_from' => $request->get_param('date_from'),
            'date_to' => $request->get_param('date_to'),
        ];

        // Remove empty filters
        $filters = array_filter($filters, function ($value) {
            return $value !== null && $value !== '';
        });

        $limit = min((int) $request->get_param('limit'), 100) ?: 50;
        $offset = (int) $request->get_param('offset') ?: 0;

        try {
            $logs = $this->auditLogger->getActions($filters, $limit, $offset);

            // Decode JSON fields
            foreach ($logs as &$log) {
                if ($log['payload_redacted']) {
                    $log['payload_redacted'] = json_decode($log['payload_redacted'], true);
                }
                if ($log['policy_verdict']) {
                    $log['policy_verdict'] = json_decode($log['policy_verdict'], true);
                }
            }

            return new WP_REST_Response([
                'success' => true,
                'data' => $logs,
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => count($logs) === $limit,
                ],
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve logs', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'filters' => $filters,
            ]);

            return new WP_Error(
                'logs_failed',
                'Failed to retrieve logs',
                ['status' => 500]
            );
        }
    }

    public function getLogById(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $id = (int) $request->get_param('id');

        if (!$id) {
            return new WP_Error(
                'missing_id',
                'Log ID is required',
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

        // Check capabilities
        if (!current_user_can('ai_agent_view_logs')) {
            return new WP_Error(
                'forbidden',
                'Insufficient permissions to view logs',
                ['status' => 403]
            );
        }

        try {
            $log = $this->auditLogger->getActionById($id);

            if (!$log) {
                return new WP_Error(
                    'log_not_found',
                    'Log not found',
                    ['status' => 404]
                );
            }

            // Decode JSON fields
            if ($log['payload_redacted']) {
                $log['payload_redacted'] = json_decode($log['payload_redacted'], true);
            }
            if ($log['policy_verdict']) {
                $log['policy_verdict'] = json_decode($log['policy_verdict'], true);
            }

            return new WP_REST_Response([
                'success' => true,
                'data' => $log,
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve log', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'log_id' => $id,
            ]);

            return new WP_Error(
                'log_failed',
                'Failed to retrieve log',
                ['status' => 500]
            );
        }
    }

    public function getDiff(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $id = (int) $request->get_param('id');

        if (!$id) {
            return new WP_Error(
                'missing_id',
                'Log ID is required',
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

        // Check capabilities
        if (!current_user_can('ai_agent_view_logs')) {
            return new WP_Error(
                'forbidden',
                'Insufficient permissions to view logs',
                ['status' => 403]
            );
        }

        try {
            $diff = $this->auditLogger->generateDiff($id);

            if (!$diff) {
                return new WP_Error(
                    'diff_not_available',
                    'Diff not available for this log entry',
                    ['status' => 404]
                );
            }

            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'diff' => $diff,
                ],
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to generate diff', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'log_id' => $id,
            ]);

            return new WP_Error(
                'diff_failed',
                'Failed to generate diff',
                ['status' => 500]
            );
        }
    }
}

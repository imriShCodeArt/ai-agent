<?php

namespace AIAgent\REST\Controllers;

use AIAgent\Support\Logger;
use AIAgent\Infrastructure\Audit\EnhancedAuditLogger;
use AIAgent\Infrastructure\Audit\AuditLogger;
use AIAgent\Infrastructure\Notifications\NotificationsService;

final class ReviewController extends BaseRestController
{
    private Logger $logger;
    private EnhancedAuditLogger $auditLogger;
    private AuditLogger $diffLogger;
    private NotificationsService $notifications;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->auditLogger = new EnhancedAuditLogger($logger);
        $this->diffLogger = new AuditLogger($logger);
        $this->notifications = new NotificationsService($logger);
    }

    /**
     * @param mixed $request
     * @return \WP_REST_Response
     */
    public function list($request): \WP_REST_Response
    {
        // Minimal placeholder: list recent pending actions from audit/actions table
        global $wpdb;
        $table = $wpdb->prefix . 'ai_agent_actions';
        $page = max(1, (int) ($request->get_param('page') ?? 1));
        $perPage = min(50, max(1, (int) ($request->get_param('per_page') ?? 20)));
        $offset = ($page - 1) * $perPage;
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE status = %s ORDER BY ts DESC LIMIT %d OFFSET %d", 'pending', $perPage, $offset), ARRAY_A);
        $items = is_array($rows) ? $rows : [];
        return new \WP_REST_Response([
            'items' => $items,
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    /**
     * @param mixed $request
     * @return \WP_REST_Response
     */
    public function approve($request): \WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $userId = function_exists('get_current_user_id') ? (int) get_current_user_id() : 0;
        global $wpdb;
        $table = $wpdb->prefix . 'ai_agent_actions';
        // Mark approved
        $wpdb->update($table, ['status' => 'approved'], ['id' => $id]);
        $this->auditLogger->logSecurityEvent('review_approved', $userId, ['action_id' => $id, 'error_code' => 'REVIEW_APPROVED', 'error_category' => 'review']);
        $this->notifications->notifyReviewEvent($id, 'approved');
        return new \WP_REST_Response(['ok' => true, 'id' => $id]);
    }

    /**
     * @param mixed $request
     * @return \WP_REST_Response
     */
    public function reject($request): \WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $reason = (string) ($request->get_param('reason') ?? '');
        $userId = function_exists('get_current_user_id') ? (int) get_current_user_id() : 0;
        global $wpdb;
        $table = $wpdb->prefix . 'ai_agent_actions';
        $wpdb->update($table, ['status' => 'rejected', 'error' => $reason], ['id' => $id]);
        $this->auditLogger->logSecurityEvent('review_rejected', $userId, ['action_id' => $id, 'reason' => $reason, 'error_code' => 'REVIEW_REJECTED', 'error_category' => 'review']);
        $this->notifications->notifyReviewEvent($id, 'rejected', $reason);
        return new \WP_REST_Response(['ok' => true, 'id' => $id]);
    }

    /**
     * @param mixed $request
     * @return \WP_REST_Response
     */
    public function comment($request): \WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $comment = (string) ($request->get_param('comment') ?? '');
        $userId = function_exists('get_current_user_id') ? (int) get_current_user_id() : 0;
        // For MVP, log comment via audit
        $this->auditLogger->logSecurityEvent('review_comment', $userId, ['action_id' => $id, 'comment' => $comment, 'error_code' => 'REVIEW_COMMENT', 'error_category' => 'review']);
        return new \WP_REST_Response(['ok' => true, 'id' => $id]);
    }

    /**
     * @param mixed $request
     */
    public function notify($request): \WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $type = (string) ($request->get_param('type') ?? 'pending');
        $message = (string) ($request->get_param('message') ?? '');
        $this->notifications->notifyReviewEvent($id, $type, $message);
        return new \WP_REST_Response(['ok' => true]);
    }

    /**
     * @param mixed $request
     * @return \WP_REST_Response
     */
    public function getDiff($request): \WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        global $wpdb;
        $table = $wpdb->prefix . 'ai_agent_actions';
        
        $action = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id), ARRAY_A);
        if (!$action) {
            return new \WP_REST_Response(['error' => 'Action not found'], 404);
        }

        $diff = $this->diffLogger->generateDiff($id);
        if (!$diff) {
            return new \WP_REST_Response(['error' => 'Unable to generate diff'], 500);
        }

        return new \WP_REST_Response([
            'diff' => $diff,
            'action_id' => $id,
            'entity_type' => $action['entity_type'],
            'entity_id' => $action['entity_id']
        ]);
    }

    /**
     * @param mixed $request
     * @return \WP_REST_Response
     */
    public function batchApprove($request): \WP_REST_Response
    {
        $ids = $request->get_param('ids');
        if (!is_array($ids) || empty($ids)) {
            return new \WP_REST_Response(['error' => 'No IDs provided'], 400);
        }

        $userId = function_exists('get_current_user_id') ? (int) get_current_user_id() : 0;
        $approved = [];
        $errors = [];

        foreach ($ids as $id) {
            $id = (int) $id;
            global $wpdb;
            $table = $wpdb->prefix . 'ai_agent_actions';
            
            $result = $wpdb->update($table, ['status' => 'approved'], ['id' => $id]);
            if ($result !== false) {
                $approved[] = $id;
                $this->auditLogger->logSecurityEvent('review_approved', $userId, ['action_id' => $id, 'error_code' => 'REVIEW_APPROVED', 'error_category' => 'review']);
                $this->notifications->notifyReviewEvent($id, 'approved');
            } else {
                $errors[] = $id;
            }
        }

        return new \WP_REST_Response([
            'approved' => $approved,
            'errors' => $errors,
            'total' => count($ids)
        ]);
    }

    /**
     * @param mixed $request
     * @return \WP_REST_Response
     */
    public function batchReject($request): \WP_REST_Response
    {
        $ids = $request->get_param('ids');
        $reason = (string) ($request->get_param('reason') ?? 'Batch rejection');
        
        if (!is_array($ids) || empty($ids)) {
            return new \WP_REST_Response(['error' => 'No IDs provided'], 400);
        }

        $userId = function_exists('get_current_user_id') ? (int) get_current_user_id() : 0;
        $rejected = [];
        $errors = [];

        foreach ($ids as $id) {
            $id = (int) $id;
            global $wpdb;
            $table = $wpdb->prefix . 'ai_agent_actions';
            
            $result = $wpdb->update($table, ['status' => 'rejected', 'error' => $reason], ['id' => $id]);
            if ($result !== false) {
                $rejected[] = $id;
                $this->auditLogger->logSecurityEvent('review_rejected', $userId, ['action_id' => $id, 'reason' => $reason, 'error_code' => 'REVIEW_REJECTED', 'error_category' => 'review']);
                $this->notifications->notifyReviewEvent($id, 'rejected', $reason);
            } else {
                $errors[] = $id;
            }
        }

        return new \WP_REST_Response([
            'rejected' => $rejected,
            'errors' => $errors,
            'total' => count($ids)
        ]);
    }

    /**
     * @param mixed $request
     * @return \WP_REST_Response
     */
    public function rollback($request): \WP_REST_Response
    {
        $id = (int) $request->get_param('id');
        $reason = (string) ($request->get_param('reason') ?? 'Rollback requested');
        $userId = function_exists('get_current_user_id') ? (int) get_current_user_id() : 0;
        
        global $wpdb;
        $table = $wpdb->prefix . 'ai_agent_actions';
        
        // Get the action details
        $action = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id), ARRAY_A);
        if (!$action) {
            return new \WP_REST_Response(['error' => 'Action not found'], 404);
        }

        if ($action['status'] !== 'approved') {
            return new \WP_REST_Response(['error' => 'Only approved actions can be rolled back'], 400);
        }

        // For MVP, we'll mark it as rolled back and log the event
        // In a full implementation, this would restore the previous state
        $result = $wpdb->update($table, [
            'status' => 'rolled_back',
            'error' => $reason
        ], ['id' => $id]);

        if ($result === false) {
            return new \WP_REST_Response(['error' => 'Failed to rollback action'], 500);
        }

        $this->auditLogger->logSecurityEvent('review_rolled_back', $userId, [
            'action_id' => $id,
            'reason' => $reason,
            'error_code' => 'REVIEW_ROLLED_BACK',
            'error_category' => 'review'
        ]);

        $this->notifications->notifyReviewEvent($id, 'rolled_back', $reason);

        return new \WP_REST_Response([
            'ok' => true,
            'id' => $id,
            'status' => 'rolled_back'
        ]);
    }

    private function verify_nonce($request): bool
    {
        $nonce = $request->get_header('X-WP-Nonce');
        if (!$nonce) {
            $nonce = $request->get_param('_wpnonce');
        }
        
        return wp_verify_nonce($nonce, 'wp_rest');
    }

    private function check_review_permission($request): bool
    {
        // Verify nonce for security
        if (!$this->verify_nonce($request)) {
            return false;
        }
        
        return current_user_can('ai_agent_approve_changes') || current_user_can('manage_options');
    }
}



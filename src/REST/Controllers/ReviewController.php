<?php

namespace AIAgent\REST\Controllers;

use AIAgent\Support\Logger;
use AIAgent\Infrastructure\Audit\EnhancedAuditLogger;
use AIAgent\Infrastructure\Notifications\NotificationsService;

final class ReviewController extends BaseRestController
{
    private Logger $logger;
    private EnhancedAuditLogger $auditLogger;
    private NotificationsService $notifications;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->auditLogger = new EnhancedAuditLogger($logger);
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
}



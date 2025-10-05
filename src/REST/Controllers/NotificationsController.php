<?php

namespace AIAgent\REST\Controllers;

use AIAgent\Infrastructure\Notifications\NotificationsService;
use AIAgent\Infrastructure\Audit\EnhancedAuditLogger;
use AIAgent\Support\Logger;

final class NotificationsController extends BaseRestController
{
    private NotificationsService $notificationsService;
    private EnhancedAuditLogger $auditLogger;
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->notificationsService = new NotificationsService($logger);
        $this->auditLogger = new EnhancedAuditLogger($logger);
    }

    public function register_routes(): void
    {
        register_rest_route('ai-agent/v1', '/notifications', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_notifications'],
                'permission_callback' => [$this, 'check_notification_read_permission'],
                'args' => [
                    'page' => [
                        'type' => 'integer',
                        'default' => 1,
                        'sanitize_callback' => 'absint',
                    ],
                    'per_page' => [
                        'type' => 'integer',
                        'default' => 20,
                        'sanitize_callback' => 'absint',
                    ],
                    'type' => [
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'status' => [
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ],
        ]);

        register_rest_route('ai-agent/v1', '/notifications/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_notification'],
                'permission_callback' => [$this, 'check_notification_read_permission'],
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_notification'],
                'permission_callback' => [$this, 'check_notification_write_permission'],
                'args' => [
                    'status' => [
                        'required' => true,
                        'type' => 'string',
                        'enum' => ['read', 'unread', 'archived'],
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ],
        ]);

        register_rest_route('ai-agent/v1', '/notifications/bulk', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'bulk_update_notifications'],
                'permission_callback' => [$this, 'check_notification_write_permission'],
                'args' => [
                    'ids' => [
                        'required' => true,
                        'type' => 'array',
                        'items' => ['type' => 'integer'],
                    ],
                    'action' => [
                        'required' => true,
                        'type' => 'string',
                        'enum' => ['mark_read', 'mark_unread', 'archive', 'delete'],
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ],
        ]);

        register_rest_route('ai-agent/v1', '/notifications/send', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'send_notification'],
                'permission_callback' => [$this, 'check_notification_write_permission'],
                'args' => [
                    'type' => [
                        'required' => true,
                        'type' => 'string',
                        'enum' => ['review_pending', 'review_approved', 'review_rejected', 'policy_updated', 'system_alert'],
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'recipients' => [
                        'required' => true,
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'subject' => [
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'message' => [
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'wp_kses_post',
                    ],
                    'metadata' => [
                        'type' => 'object',
                        'default' => [],
                    ],
                ],
            ],
        ]);

        register_rest_route('ai-agent/v1', '/notifications/preferences', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_notification_preferences'],
                'permission_callback' => [$this, 'check_notification_read_permission'],
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_notification_preferences'],
                'permission_callback' => [$this, 'check_notification_write_permission'],
                'args' => [
                    'email_enabled' => [
                        'type' => 'boolean',
                        'default' => true,
                    ],
                    'review_notifications' => [
                        'type' => 'boolean',
                        'default' => true,
                    ],
                    'policy_notifications' => [
                        'type' => 'boolean',
                        'default' => true,
                    ],
                    'system_notifications' => [
                        'type' => 'boolean',
                        'default' => true,
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param mixed $request
     */
    public function get_notifications($request): \WP_REST_Response
    {
        try {
            $page = $request->get_param('page');
            $per_page = $request->get_param('per_page');
            $type = $request->get_param('type');
            $status = $request->get_param('status');

            // In a real implementation, this would query a notifications table
            $notifications = $this->get_mock_notifications($page, $per_page, $type, $status);

            return new \WP_REST_Response([
                'success' => true,
                'notifications' => $notifications,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $per_page,
                    'total' => count($notifications),
                ],
            ], 200);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get notifications', ['error' => $e->getMessage()]);
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Failed to retrieve notifications',
            ], 500);
        }
    }

    /**
     * @param mixed $request
     */
    public function get_notification($request): \WP_REST_Response
    {
        try {
            $id = (int) $request->get_param('id');
            
            // In a real implementation, this would query a specific notification
            $notification = $this->get_mock_notification($id);
            
            if (!$notification) {
                return new \WP_REST_Response([
                    'success' => false,
                    'error' => 'Notification not found',
                ], 404);
            }

            return new \WP_REST_Response([
                'success' => true,
                'notification' => $notification,
            ], 200);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get notification', ['id' => $request->get_param('id'), 'error' => $e->getMessage()]);
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Failed to retrieve notification',
            ], 500);
        }
    }

    /**
     * @param mixed $request
     */
    public function update_notification($request): \WP_REST_Response
    {
        try {
            $id = (int) $request->get_param('id');
            $status = $request->get_param('status');
            $userId = get_current_user_id();

            // In a real implementation, this would update the notification status
            $result = $this->update_notification_status($id, $status);

            if ($result) {
                $this->auditLogger->logSecurityEvent('notification_updated', $userId, [
                    'notification_id' => $id,
                    'status' => $status,
                    'error_code' => 'NOTIFICATION_UPDATED',
                    'error_category' => 'notification',
                ]);

                return new \WP_REST_Response([
                    'success' => true,
                    'message' => 'Notification updated successfully',
                ], 200);
            } else {
                return new \WP_REST_Response([
                    'success' => false,
                    'error' => 'Failed to update notification',
                ], 400);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to update notification', ['id' => $request->get_param('id'), 'error' => $e->getMessage()]);
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Failed to update notification',
            ], 500);
        }
    }

    /**
     * @param mixed $request
     */
    public function bulk_update_notifications($request): \WP_REST_Response
    {
        try {
            $ids = $request->get_param('ids');
            $action = $request->get_param('action');
            $userId = get_current_user_id();

            $results = [];
            foreach ($ids as $id) {
                $result = $this->bulk_update_notification($id, $action);
                $results[] = ['id' => $id, 'success' => $result];
            }

            $this->auditLogger->logSecurityEvent('notifications_bulk_updated', $userId, [
                'action' => $action,
                'count' => count($ids),
                'error_code' => 'NOTIFICATIONS_BULK_UPDATED',
                'error_category' => 'notification',
            ]);

            return new \WP_REST_Response([
                'success' => true,
                'results' => $results,
                'message' => 'Bulk update completed',
            ], 200);
        } catch (\Exception $e) {
            $this->logger->error('Failed to bulk update notifications', ['error' => $e->getMessage()]);
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Failed to bulk update notifications',
            ], 500);
        }
    }

    /**
     * @param mixed $request
     */
    public function send_notification($request): \WP_REST_Response
    {
        try {
            $type = $request->get_param('type');
            $recipients = $request->get_param('recipients');
            $subject = $request->get_param('subject');
            $message = $request->get_param('message');
            $metadata = $request->get_param('metadata');
            $userId = get_current_user_id();

            $result = $this->send_notification_to_recipients($type, $recipients, $subject, $message, $metadata);

            if ($result['success']) {
                $this->auditLogger->logSecurityEvent('notification_sent', $userId, [
                    'type' => $type,
                    'recipients_count' => count($recipients),
                    'error_code' => 'NOTIFICATION_SENT',
                    'error_category' => 'notification',
                ]);

                return new \WP_REST_Response($result, 200);
            } else {
                return new \WP_REST_Response($result, 400);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to send notification', ['error' => $e->getMessage()]);
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Failed to send notification',
            ], 500);
        }
    }

    /**
     * @param mixed $request
     */
    public function get_notification_preferences($request): \WP_REST_Response
    {
        try {
            $userId = get_current_user_id();
            $preferences = get_user_meta($userId, 'ai_agent_notification_preferences', true);
            
            if (!$preferences) {
                $preferences = [
                    'email_enabled' => true,
                    'review_notifications' => true,
                    'policy_notifications' => true,
                    'system_notifications' => true,
                ];
            }

            return new \WP_REST_Response([
                'success' => true,
                'preferences' => $preferences,
            ], 200);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get notification preferences', ['error' => $e->getMessage()]);
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Failed to retrieve notification preferences',
            ], 500);
        }
    }

    /**
     * @param mixed $request
     */
    public function update_notification_preferences($request): \WP_REST_Response
    {
        try {
            $userId = get_current_user_id();
            $preferences = [
                'email_enabled' => $request->get_param('email_enabled'),
                'review_notifications' => $request->get_param('review_notifications'),
                'policy_notifications' => $request->get_param('policy_notifications'),
                'system_notifications' => $request->get_param('system_notifications'),
            ];

            $result = update_user_meta($userId, 'ai_agent_notification_preferences', $preferences);

            if ($result) {
                $this->auditLogger->logSecurityEvent('notification_preferences_updated', $userId, [
                    'preferences' => $preferences,
                    'error_code' => 'NOTIFICATION_PREFERENCES_UPDATED',
                    'error_category' => 'notification',
                ]);

                return new \WP_REST_Response([
                    'success' => true,
                    'message' => 'Notification preferences updated successfully',
                ], 200);
            } else {
                return new \WP_REST_Response([
                    'success' => false,
                    'error' => 'Failed to update notification preferences',
                ], 400);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to update notification preferences', ['error' => $e->getMessage()]);
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Failed to update notification preferences',
            ], 500);
        }
    }

    private function get_mock_notifications(int $page, int $per_page, ?string $type, ?string $status): array
    {
        // Mock data for testing - in a real implementation, this would query the database
        $notifications = [
            [
                'id' => 1,
                'type' => 'review_pending',
                'subject' => 'New review pending for action #123',
                'message' => 'A new action requires your review and approval.',
                'status' => 'unread',
                'created_at' => '2024-01-15 10:30:00',
                'metadata' => ['action_id' => 123],
            ],
            [
                'id' => 2,
                'type' => 'policy_updated',
                'subject' => 'Policy updated for products.create',
                'message' => 'The policy for products.create has been updated.',
                'status' => 'read',
                'created_at' => '2024-01-15 09:15:00',
                'metadata' => ['tool' => 'products.create'],
            ],
        ];

        // Apply filters
        if ($type) {
            $notifications = array_filter($notifications, fn($n) => $n['type'] === $type);
        }
        if ($status) {
            $notifications = array_filter($notifications, fn($n) => $n['status'] === $status);
        }

        // Apply pagination
        $offset = ($page - 1) * $per_page;
        return array_slice($notifications, $offset, $per_page);
    }

    private function get_mock_notification(int $id): ?array
    {
        $notifications = $this->get_mock_notifications(1, 10, null, null);
        foreach ($notifications as $notification) {
            if ($notification['id'] === $id) {
                return $notification;
            }
        }
        return null;
    }

    private function update_notification_status(int $id, string $status): bool
    {
        // Mock implementation - in a real implementation, this would update the database
        return true;
    }

    private function bulk_update_notification(int $id, string $action): bool
    {
        // Mock implementation - in a real implementation, this would update the database
        return true;
    }

    private function send_notification_to_recipients(string $type, array $recipients, string $subject, string $message, array $metadata): array
    {
        $sent = 0;
        $failed = 0;

        foreach ($recipients as $recipient) {
            if (is_email($recipient) && wp_mail($recipient, $subject, $message)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return [
            'success' => $failed === 0,
            'sent' => $sent,
            'failed' => $failed,
            'message' => $failed === 0 ? 'All notifications sent successfully' : "Sent {$sent}, failed {$failed}",
        ];
    }

    public function check_notification_read_permission(mixed $request): bool
    {
        // Verify nonce for security
        if (!$this->verify_nonce($request)) {
            return false;
        }
        
        return current_user_can('manage_options') || current_user_can('ai_agent_view_logs');
    }

    public function check_notification_write_permission(mixed $request): bool
    {
        // Verify nonce for security
        if (!$this->verify_nonce($request)) {
            return false;
        }
        
        return current_user_can('manage_options') || current_user_can('ai_agent_manage_policies');
    }

    private function verify_nonce(mixed $request): bool
    {
        $nonce = $request->get_header('X-WP-Nonce');
        if (!$nonce) {
            $nonce = $request->get_param('_wpnonce');
        }
        
        return wp_verify_nonce($nonce, 'wp_rest');
    }
}

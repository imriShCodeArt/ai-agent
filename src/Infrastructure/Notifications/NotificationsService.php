<?php

namespace AIAgent\Infrastructure\Notifications;

use AIAgent\Support\Logger;

final class NotificationsService
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param int $actionId
     * @param string $type pending|approved|rejected
     */
    public function notifyReviewEvent(int $actionId, string $type, string $message = ''): void
    {
        // Admin email fallback
        $adminEmail = function_exists('get_option') ? (string) get_option('admin_email', '') : '';
        if ($adminEmail !== '' && function_exists('wp_mail')) {
            $subject = sprintf('[AI Agent] Review %s for action #%d', $type, $actionId);
            $body = $message !== '' ? $message : sprintf('Review %s for action #%d', $type, $actionId);
            wp_mail($adminEmail, $subject, $body);
        }
        $this->logger->info('Notification dispatched', ['action_id' => $actionId, 'type' => $type]);
    }
}



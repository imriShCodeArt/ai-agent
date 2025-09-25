<?php

namespace AIAgent\Frontend;

use AIAgent\Support\Logger;
use AIAgent\Frontend\ChatWidget;

final class Shortcodes
{
    private Logger $logger;
    private ChatWidget $chatWidget;

    public function __construct(Logger $logger, ChatWidget $chatWidget)
    {
        $this->logger = $logger;
        $this->chatWidget = $chatWidget;
    }

    public function addHooks(): void
    {
        add_action('init', [$this, 'registerShortcodes']);
    }

    public function registerShortcodes(): void
    {
        add_shortcode('ai_agent_chat', [$this, 'renderChatShortcode']);
    }

    /**
     * @param array<string, mixed> $atts
     */
    public function renderChatShortcode($atts): string
    {
        $atts = shortcode_atts([
            'mode' => 'suggest',
            'types' => 'post,page',
            'max_ops' => 10,
            'height' => '400px',
            'width' => '100%',
        ], $atts);

        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<div class="ai-agent-error">You must be logged in to use the AI Agent chat.</div>';
        }

        // Check permissions
        if (!current_user_can('ai_agent_read')) {
            return '<div class="ai-agent-error">You do not have permission to use the AI Agent.</div>';
        }

        try {
            return $this->chatWidget->renderChatWidget($atts);
        } catch (\Exception $e) {
            $this->logger->error('Failed to render chat widget', [
                'error' => $e->getMessage(),
                'atts' => $atts,
            ]);
            
            return '<div class="ai-agent-error">Failed to load AI Agent chat widget.</div>';
        }
    }
}
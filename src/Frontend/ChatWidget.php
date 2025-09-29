<?php

namespace AIAgent\Frontend;

use AIAgent\Support\Logger;

final class ChatWidget
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param array<string, mixed> $args
     */
    public function renderChatWidget(array $args = []): string
    {
        $args = wp_parse_args($args, [
            'mode' => 'suggest',
            'types' => 'post,page',
            'max_ops' => 10,
            'height' => '400px',
            'width' => '100%',
        ]);

        $sessionId = wp_generate_uuid4();
        $nonce = wp_create_nonce('wp_rest');

        ob_start();
        ?>
        <div id="ai-agent-chat-widget" class="ai-agent-chat-widget" 
             data-mode="<?php echo esc_attr($args['mode']); ?>"
             data-types="<?php echo esc_attr($args['types']); ?>"
             data-max-ops="<?php echo esc_attr($args['max_ops']); ?>"
             data-session-id="<?php echo esc_attr($sessionId); ?>"
             data-nonce="<?php echo esc_attr($nonce); ?>"
             style="height: <?php echo esc_attr($args['height']); ?>; width: <?php echo esc_attr($args['width']); ?>;">
            
            <div class="ai-agent-chat-header">
                <h3>AI Agent Assistant</h3>
                <div class="ai-agent-mode-indicator">
                    Mode: <span class="ai-agent-mode"><?php echo esc_html(ucfirst($args['mode'])); ?></span>
                </div>
            </div>

            <div class="ai-agent-chat-messages" id="ai-agent-messages">
                <div class="ai-agent-message ai-agent-message-system">
                    <div class="ai-agent-message-content">
                        <p>Hello! I'm your AI assistant. I can help you create, update, or manage content on this site.</p>
                        <p><strong>Current mode:</strong> <?php echo esc_html(ucfirst($args['mode'])); ?></p>
                        <?php if ($args['mode'] === 'suggest'): ?>
                            <p>I'll suggest changes for you to review and apply.</p>
                        <?php elseif ($args['mode'] === 'review'): ?>
                            <p>I'll prepare changes that require your approval before being applied.</p>
                        <?php else: ?>
                            <p>I can make changes directly (use with caution).</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="ai-agent-chat-input">
                <form id="ai-agent-chat-form">
                    <div class="ai-agent-input-group">
                        <input type="text" 
                               id="ai-agent-message-input" 
                               placeholder="Ask me to create or update content..."
                               autocomplete="off">
                        <button type="submit" id="ai-agent-send-btn">
                            <span class="ai-agent-send-text">Send</span>
                            <span class="ai-agent-send-loading" style="display: none;">Sending...</span>
                        </button>
                    </div>
                </form>
            </div>

            <div class="ai-agent-suggested-actions" id="ai-agent-suggested-actions" style="display: none;">
                <h4>Suggested Actions</h4>
                <div class="ai-agent-actions-list"></div>
            </div>
        </div>

        <style>
        .ai-agent-chat-widget {
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #fff;
            display: flex;
            flex-direction: column;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        .ai-agent-chat-header {
            background: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #ddd;
            border-radius: 8px 8px 0 0;
        }

        .ai-agent-chat-header h3 {
            margin: 0 0 5px 0;
            color: #333;
            font-size: 18px;
        }

        .ai-agent-mode-indicator {
            font-size: 12px;
            color: #666;
        }

        .ai-agent-mode {
            font-weight: bold;
            color: #0073aa;
        }

        .ai-agent-chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
            max-height: 300px;
        }

        .ai-agent-message {
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
        }

        .ai-agent-message-content {
            background: #f1f1f1;
            padding: 10px 15px;
            border-radius: 18px;
            max-width: 80%;
            word-wrap: break-word;
        }

        .ai-agent-message-user .ai-agent-message-content {
            background: #0073aa;
            color: white;
            margin-left: auto;
        }

        .ai-agent-message-system .ai-agent-message-content {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
        }

        .ai-agent-message-error .ai-agent-message-content {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .ai-agent-chat-input {
            padding: 15px;
            border-top: 1px solid #ddd;
        }

        .ai-agent-input-group {
            display: flex;
            gap: 10px;
        }

        .ai-agent-input-group input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            outline: none;
        }

        .ai-agent-input-group input:focus {
            border-color: #0073aa;
        }

        .ai-agent-input-group button {
            padding: 10px 20px;
            background: #0073aa;
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-weight: bold;
        }

        .ai-agent-input-group button:hover {
            background: #005a87;
        }

        .ai-agent-input-group button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .ai-agent-suggested-actions {
            padding: 15px;
            border-top: 1px solid #ddd;
            background: #f8f9fa;
        }

        .ai-agent-suggested-actions h4 {
            margin: 0 0 10px 0;
            color: #333;
        }

        .ai-agent-action {
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .ai-agent-action:hover {
            background: #f0f8ff;
        }

        .ai-agent-action-title {
            font-weight: bold;
            color: #0073aa;
            margin-bottom: 5px;
        }

        .ai-agent-action-description {
            font-size: 14px;
            color: #666;
        }

        .ai-agent-loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }

        .ai-agent-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        </style>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatWidget = document.getElementById('ai-agent-chat-widget');
            const messagesContainer = document.getElementById('ai-agent-messages');
            const messageInput = document.getElementById('ai-agent-message-input');
            const sendBtn = document.getElementById('ai-agent-send-btn');
            const chatForm = document.getElementById('ai-agent-chat-form');
            const suggestedActions = document.getElementById('ai-agent-suggested-actions');
            const actionsList = suggestedActions.querySelector('.ai-agent-actions-list');

            const mode = chatWidget.dataset.mode;
            const sessionId = chatWidget.dataset.sessionId;
            const nonce = chatWidget.dataset.nonce;

            chatForm.addEventListener('submit', function(e) {
                e.preventDefault();
                sendMessage();
            });

            function sendMessage() {
                const message = messageInput.value.trim();
                if (!message) return;

                // Add user message to chat
                addMessage(message, 'user');
                messageInput.value = '';
                setLoading(true);

                // Send to API
                fetch('/wp-json/ai-agent/v1/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': nonce
                    },
                    body: JSON.stringify({
                        prompt: message,
                        mode: mode,
                        session_id: sessionId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    setLoading(false);
                    if (data.success) {
                        addMessage(data.data.message, 'system');
                        if (data.data.suggested_actions && data.data.suggested_actions.length > 0) {
                            showSuggestedActions(data.data.suggested_actions);
                        }
                    } else {
                        addMessage('Error: ' + (data.message || 'Unknown error'), 'error');
                    }
                })
                .catch(error => {
                    setLoading(false);
                    addMessage('Error: ' + error.message, 'error');
                });
            }

            function addMessage(content, type) {
                const messageDiv = document.createElement('div');
                messageDiv.className = 'ai-agent-message ai-agent-message-' + type;
                
                const contentDiv = document.createElement('div');
                contentDiv.className = 'ai-agent-message-content';
                contentDiv.innerHTML = content;
                
                messageDiv.appendChild(contentDiv);
                messagesContainer.appendChild(messageDiv);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }

            function showSuggestedActions(actions) {
                actionsList.innerHTML = '';
                actions.forEach(action => {
                    const actionDiv = document.createElement('div');
                    actionDiv.className = 'ai-agent-action';
                    actionDiv.innerHTML = `
                        <div class="ai-agent-action-title">${action.description}</div>
                        <div class="ai-agent-action-description">Tool: ${action.tool}</div>
                    `;
                    actionDiv.addEventListener('click', () => executeAction(action));
                    actionsList.appendChild(actionDiv);
                });
                suggestedActions.style.display = 'block';
            }

            function executeAction(action) {
                addMessage(`Executing: ${action.description || action.tool}`, 'system');
                setLoading(true);

                fetch('/wp-json/ai-agent/v1/dry-run', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
                    body: JSON.stringify({ tool: action.tool, fields: action.parameters || {} })
                })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) throw new Error(data.message || 'Policy denied or error');
                    // If allowed, execute immediately
                    addMessage('Dry run allowed. Diff preview:\n' + (data.data.diff || 'No diff'), 'system');
                    return fetch('/wp-json/ai-agent/v1/execute', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
                        body: JSON.stringify({ tool: action.tool, fields: action.parameters || {}, mode: 'autonomous' })
                    });
                })
                .then(r => r ? r.json() : null)
                .then(exec => { if (exec && exec.success) { addMessage('Execution result: ' + JSON.stringify(exec.data), 'system'); } })
                .catch(e => addMessage('Execution error: ' + e.message, 'error'))
                .finally(() => setLoading(false));
            }

            function setLoading(loading) {
                sendBtn.disabled = loading;
                const sendText = sendBtn.querySelector('.ai-agent-send-text');
                const loadingText = sendBtn.querySelector('.ai-agent-send-loading');
                
                if (loading) {
                    sendText.style.display = 'none';
                    loadingText.style.display = 'inline';
                } else {
                    sendText.style.display = 'inline';
                    loadingText.style.display = 'none';
                }
            }
        });
        </script>
        <?php
        $output = ob_get_clean();
        return $output ?: '';
    }
}

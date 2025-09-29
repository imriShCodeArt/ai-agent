<?php

namespace AIAgent\Application\Providers;

use AIAgent\Infrastructure\Hooks\HookableInterface;
use AIAgent\REST\Controllers\ChatController;
use AIAgent\REST\Controllers\PostsController;
use AIAgent\REST\Controllers\LogsController;
use AIAgent\Infrastructure\Security\Policy;
use AIAgent\Infrastructure\Audit\AuditLogger;
use AIAgent\Support\Logger;
use AIAgent\Infrastructure\LLM\LLMProviderInterface;
use AIAgent\Infrastructure\LLM\OpenAIProvider;
use AIAgent\Infrastructure\Tools\ToolRegistry;
use AIAgent\Infrastructure\Tools\ToolExecutionEngine;
use AIAgent\Infrastructure\Security\Capabilities;
use AIAgent\Infrastructure\Tools\TextSummarizeTool;
use AIAgent\Infrastructure\Tools\PostsCreateTool;
use AIAgent\Infrastructure\Tools\PostsUpdateTool;

final class RestApiServiceProvider extends AbstractServiceProvider implements HookableInterface
{
    public function register(): void
    {
        // Register services in container
        $this->container->singleton(Policy::class, function () {
            return new Policy($this->container->get(Logger::class));
        });

        $this->container->singleton(AuditLogger::class, function () {
            return new AuditLogger($this->container->get(Logger::class));
        });

        $this->container->singleton(ChatController::class, function () {
            return new ChatController(
                $this->container->get(Policy::class),
                $this->container->get(AuditLogger::class),
                $this->container->get(Logger::class),
                $this->container->get(LLMProviderInterface::class),
                $this->container->get(ToolExecutionEngine::class)
            );
        });

        $this->container->singleton(PostsController::class, function () {
            return new PostsController(
                $this->container->get(Policy::class),
                $this->container->get(AuditLogger::class),
                $this->container->get(Logger::class)
            );
        });

        $this->container->singleton(LogsController::class, function () {
            return new LogsController(
                $this->container->get(AuditLogger::class),
                $this->container->get(Logger::class)
            );
        });

        // LLM Provider default binding (OpenAI; can be swapped later)
        $this->container->singleton(LLMProviderInterface::class, function () {
            $logger = $this->container->get(Logger::class);
            $apiKey = (string) get_option('ai_agent_openai_api_key', '');
            return new OpenAIProvider($logger, $apiKey);
        });

        // Tool system services
        $this->container->singleton(ToolRegistry::class, function () {
            $registry = new ToolRegistry();
            // Register built-in tools
            $registry->register(new TextSummarizeTool($this->container->get(LLMProviderInterface::class)));
            $registry->register(new PostsCreateTool());
            $registry->register(new PostsUpdateTool());
            return $registry;
        });

        $this->container->singleton(ToolExecutionEngine::class, function () {
            return new ToolExecutionEngine(
                $this->container->get(ToolRegistry::class),
                $this->container->get(Policy::class),
                $this->container->get(Capabilities::class),
                $this->container->get(AuditLogger::class)
            );
        });
    }

    public function registerHooks(): void
    {
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
    }

    public function registerRestRoutes(): void
    {
        $chatController = $this->container->get(ChatController::class);
        $postsController = $this->container->get(PostsController::class);
        $logsController = $this->container->get(LogsController::class);

        // Chat endpoints
        register_rest_route('ai-agent/v1', '/chat', [
            'methods' => 'POST',
            'callback' => [$chatController, 'chat'],
            'permission_callback' => [$this, 'checkChatPermissions'],
            'args' => [
                'prompt' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'mode' => [
                    'required' => false,
                    'type' => 'string',
                    'enum' => ['suggest', 'review', 'autonomous'],
                    'default' => 'suggest',
                ],
                'session_id' => [
                    'required' => false,
                    'type' => 'string',
                ],
            ],
        ]);

        register_rest_route('ai-agent/v1', '/dry-run', [
            'methods' => 'POST',
            'callback' => [$chatController, 'dryRun'],
            'permission_callback' => [$this, 'checkChatPermissions'],
            'args' => [
                'tool' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'entity_id' => [
                    'required' => false,
                    'type' => 'integer',
                ],
                'fields' => [
                    'required' => false,
                    'type' => 'object',
                ],
            ],
        ]);

        // Posts endpoints
        register_rest_route('ai-agent/v1', '/posts/create', [
            'methods' => 'POST',
            'callback' => [$postsController, 'create'],
            'permission_callback' => [$this, 'checkPostPermissions'],
            'args' => [
                'fields' => [
                    'required' => true,
                    'type' => 'object',
                ],
                'mode' => [
                    'required' => false,
                    'type' => 'string',
                    'enum' => ['suggest', 'review', 'autonomous'],
                    'default' => 'suggest',
                ],
            ],
        ]);

        register_rest_route('ai-agent/v1', '/posts/update', [
            'methods' => 'POST',
            'callback' => [$postsController, 'update'],
            'permission_callback' => [$this, 'checkPostPermissions'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
                'fields' => [
                    'required' => true,
                    'type' => 'object',
                ],
                'mode' => [
                    'required' => false,
                    'type' => 'string',
                    'enum' => ['suggest', 'review', 'autonomous'],
                    'default' => 'suggest',
                ],
            ],
        ]);

        register_rest_route('ai-agent/v1', '/posts/delete', [
            'methods' => 'POST',
            'callback' => [$postsController, 'delete'],
            'permission_callback' => [$this, 'checkPostPermissions'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
                'mode' => [
                    'required' => false,
                    'type' => 'string',
                    'enum' => ['suggest', 'review', 'autonomous'],
                    'default' => 'suggest',
                ],
            ],
        ]);

        register_rest_route('ai-agent/v1', '/posts/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$postsController, 'get'],
            'permission_callback' => [$this, 'checkPostPermissions'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
            ],
        ]);

        // Logs endpoints
        register_rest_route('ai-agent/v1', '/logs', [
            'methods' => 'GET',
            'callback' => [$logsController, 'getLogs'],
            'permission_callback' => [$this, 'checkLogsPermissions'],
            'args' => [
                'user_id' => [
                    'required' => false,
                    'type' => 'integer',
                ],
                'tool' => [
                    'required' => false,
                    'type' => 'string',
                ],
                'entity_type' => [
                    'required' => false,
                    'type' => 'string',
                ],
                'status' => [
                    'required' => false,
                    'type' => 'string',
                ],
                'date_from' => [
                    'required' => false,
                    'type' => 'string',
                    'format' => 'date-time',
                ],
                'date_to' => [
                    'required' => false,
                    'type' => 'string',
                    'format' => 'date-time',
                ],
                'limit' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 50,
                    'minimum' => 1,
                    'maximum' => 100,
                ],
                'offset' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 0,
                    'minimum' => 0,
                ],
            ],
        ]);

        register_rest_route('ai-agent/v1', '/logs/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$logsController, 'getLogById'],
            'permission_callback' => [$this, 'checkLogsPermissions'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
            ],
        ]);

        register_rest_route('ai-agent/v1', '/logs/(?P<id>\d+)/diff', [
            'methods' => 'GET',
            'callback' => [$logsController, 'getDiff'],
            'permission_callback' => [$this, 'checkLogsPermissions'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
            ],
        ]);
    }

    public function checkChatPermissions(): bool
    {
        return is_user_logged_in() && current_user_can('ai_agent_read');
    }

    public function checkPostPermissions(): bool
    {
        return is_user_logged_in() && current_user_can('ai_agent_edit_posts');
    }

    public function checkLogsPermissions(): bool
    {
        return is_user_logged_in() && current_user_can('ai_agent_view_logs');
    }

    public function addHooks(): void
    {
        $this->registerHooks();
    }
}
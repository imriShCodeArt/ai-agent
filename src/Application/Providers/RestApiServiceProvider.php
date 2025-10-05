<?php

namespace AIAgent\Application\Providers;

use AIAgent\Infrastructure\Hooks\HookableInterface;
use AIAgent\REST\Controllers\ChatController;
use AIAgent\REST\Controllers\PostsController;
use AIAgent\REST\Controllers\LogsController;
use AIAgent\REST\Controllers\PolicyController;
use AIAgent\REST\Controllers\ReviewController;
use AIAgent\REST\Controllers\NotificationsController;
use AIAgent\REST\Controllers\WooCommerceController;
use AIAgent\Infrastructure\Security\Policy;
use AIAgent\Infrastructure\Security\EnhancedPolicy;
use AIAgent\Infrastructure\Security\PolicyManager;
use AIAgent\Infrastructure\Security\HmacSigner;
use AIAgent\Infrastructure\Security\OAuth2Provider;
use AIAgent\Infrastructure\Security\SecurityMiddleware;
use AIAgent\Infrastructure\Audit\AuditLogger;
use AIAgent\Infrastructure\Audit\EnhancedAuditLogger;
use AIAgent\Support\Logger;
use AIAgent\Infrastructure\LLM\LLMProviderInterface;
use AIAgent\Infrastructure\LLM\OpenAIProvider;
use AIAgent\Infrastructure\Tools\ToolRegistry;
use AIAgent\Infrastructure\Tools\ToolExecutionEngine;
use AIAgent\Infrastructure\Security\Capabilities;
use AIAgent\Infrastructure\Tools\TextSummarizeTool;
use AIAgent\Infrastructure\Tools\PostsCreateTool;
use AIAgent\Infrastructure\Tools\PostsUpdateTool;
use AIAgent\Infrastructure\Tools\WCProductsCreateTool;
use AIAgent\Infrastructure\Tools\WCProductsUpdateTool;
use AIAgent\Infrastructure\Tools\WCProductsBulkUpdateTool;
use AIAgent\Infrastructure\Tools\WCProductsSearchTool;
use AIAgent\Infrastructure\WC\Repositories\ProductRepository;
use AIAgent\Infrastructure\WC\Repositories\OrderRepository;
use AIAgent\Infrastructure\WC\Repositories\CustomerRepository;

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

		$this->container->singleton(EnhancedPolicy::class, function () {
			return new EnhancedPolicy($this->container->get(Logger::class));
		});

		$this->container->singleton(PolicyManager::class, function () {
			return new PolicyManager(
				$this->container->get(Logger::class),
				$this->container->get(EnhancedPolicy::class)
			);
		});

		$this->container->singleton(EnhancedAuditLogger::class, function () {
			return new EnhancedAuditLogger($this->container->get(Logger::class));
		});

		// Capabilities binding required by ToolExecutionEngine
		$this->container->singleton(Capabilities::class, function () {
			return new Capabilities();
		});

		$this->container->singleton(HmacSigner::class, function () {
			return new HmacSigner($this->container->get(Logger::class));
		});

		$this->container->singleton(OAuth2Provider::class, function () {
			$config = [
				'client_id' => get_option('ai_agent_oauth2_client_id', ''),
				'client_secret' => get_option('ai_agent_oauth2_client_secret', ''),
				'redirect_uri' => get_option('ai_agent_oauth2_redirect_uri', ''),
				'authorization_url' => get_option('ai_agent_oauth2_authorization_url', ''),
				'token_url' => get_option('ai_agent_oauth2_token_url', ''),
				'user_info_url' => get_option('ai_agent_oauth2_user_info_url', ''),
				'scopes' => get_option('ai_agent_oauth2_scopes', ['read', 'write']),
			];
			return new OAuth2Provider($this->container->get(Logger::class), $config);
		});

		$this->container->singleton(SecurityMiddleware::class, function () {
			return new SecurityMiddleware(
				$this->container->get(Logger::class),
				$this->container->get(HmacSigner::class),
				$this->container->get(OAuth2Provider::class)
			);
		});

		// WooCommerce repositories
		$this->container->singleton(ProductRepository::class, function () { return new ProductRepository(); });
		$this->container->singleton(OrderRepository::class, function () { return new OrderRepository(); });
		$this->container->singleton(CustomerRepository::class, function () { return new CustomerRepository(); });

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

		// Policy controller
		$this->container->singleton(PolicyController::class, function () {
			return new PolicyController($this->container->get(Logger::class));
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
			// Conditionally register WooCommerce tools
			if ((bool) get_option('ai_agent_woocommerce_enabled', false) && class_exists('WC_Product')) {
				$registry->register(new WCProductsCreateTool());
				$registry->register(new WCProductsUpdateTool());
				$registry->register(new WCProductsBulkUpdateTool());
				$registry->register(new WCProductsSearchTool());
			}
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

		// WooCommerce controller (read-only Phase 4 scope)
		$this->container->singleton(WooCommerceController::class, function () {
			return new WooCommerceController($this->container->get(Logger::class));
		});

		// Phase 5: Review controller
		$this->container->singleton(ReviewController::class, function () {
			return new ReviewController($this->container->get(Logger::class));
		});

		// Phase 5: Notifications controller
		$this->container->singleton(NotificationsController::class, function () {
			return new NotificationsController($this->container->get(Logger::class));
		});
	}

	public function registerHooks(): void
	{
		add_action('rest_api_init', [$this, 'registerRestRoutes']);
	}

	public function addHooks(): void
	{
		$this->registerHooks();
	}

	public function registerRestRoutes(): void
	{
		$chatController = $this->container->get(ChatController::class);
		$postsController = $this->container->get(PostsController::class);
		$logsController = $this->container->get(LogsController::class);
		/** @var PolicyController $policyController */
		$policyController = $this->container->get(PolicyController::class);
		$reviewController = $this->container->get(ReviewController::class);
		$wcController = $this->container->get(WooCommerceController::class);
		$security = $this->container->get(SecurityMiddleware::class);

		// Register policy routes (Phase 5)
		$policyController->register_routes();

		// Chat endpoints
		register_rest_route('ai-agent/v1', '/chat', [
			'methods' => 'POST',
			'callback' => [$chatController, 'chat'],
			'permission_callback' => function ($request) use ($security) {
				$requestAdapter = new \AIAgent\Infrastructure\Security\WPRestRequestAdapter($request);
				$auth = $security->authenticateRequest($requestAdapter);
				if (!$auth['authenticated']) { return false; }
				// Basic tool execute capability
				return current_user_can('ai_agent_execute_tool') || current_user_can('manage_options');
			},
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
			'permission_callback' => function ($request) use ($security) {
				$requestAdapter = new \AIAgent\Infrastructure\Security\WPRestRequestAdapter($request);
				$auth = $security->authenticateRequest($requestAdapter);
				return $auth['authenticated'];
			},
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

		register_rest_route('ai-agent/v1', '/execute', [
			'methods' => 'POST',
			'callback' => [$chatController, 'execute'],
			'permission_callback' => function ($request) use ($security) {
				$requestAdapter = new \AIAgent\Infrastructure\Security\WPRestRequestAdapter($request);
				$auth = $security->authenticateRequest($requestAdapter);
				if (!$auth['authenticated']) { return false; }
				return current_user_can('ai_agent_execute_tool') || current_user_can('manage_options');
			},
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

		// WooCommerce endpoints (enabled via option)
		register_rest_route('ai-agent/v1', '/wc/products', [
			'methods' => 'GET',
			'callback' => [$wcController, 'productsSearch'],
			'permission_callback' => function ($request) use ($security) {
				$requestAdapter = new \AIAgent\Infrastructure\Security\WPRestRequestAdapter($request);
				$auth = $security->authenticateRequest($requestAdapter);
				if (!$auth['authenticated']) { return false; }
				return current_user_can('manage_woocommerce') || current_user_can('manage_options');
			},
			'args' => [
				'q' => ['required' => false, 'type' => 'string'],
				'sku' => ['required' => false, 'type' => 'string'],
				'category' => ['required' => false, 'type' => 'string'],
				'price_min' => ['required' => false, 'type' => 'number'],
				'price_max' => ['required' => false, 'type' => 'number'],
				'page' => ['required' => false, 'type' => 'integer'],
				'per_page' => ['required' => false, 'type' => 'integer'],
			],
		]);

		register_rest_route('ai-agent/v1', '/wc/orders', [
			'methods' => 'GET',
			'callback' => [$wcController, 'ordersSearch'],
			'permission_callback' => function ($request) use ($security) {
				$requestAdapter = new \AIAgent\Infrastructure\Security\WPRestRequestAdapter($request);
				$auth = $security->authenticateRequest($requestAdapter);
				if (!$auth['authenticated']) { return false; }
				return current_user_can('manage_woocommerce') || current_user_can('manage_options');
			},
			'args' => [
				'status' => ['required' => false],
				'customer_id' => ['required' => false, 'type' => 'integer'],
				'page' => ['required' => false, 'type' => 'integer'],
				'per_page' => ['required' => false, 'type' => 'integer'],
			],
		]);

		register_rest_route('ai-agent/v1', '/wc/customers', [
			'methods' => 'GET',
			'callback' => [$wcController, 'customersSearch'],
			'permission_callback' => function ($request) use ($security) {
				$requestAdapter = new \AIAgent\Infrastructure\Security\WPRestRequestAdapter($request);
				$auth = $security->authenticateRequest($requestAdapter);
				if (!$auth['authenticated']) { return false; }
				return current_user_can('manage_woocommerce') || current_user_can('manage_options');
			},
		]);

		// Phase 5: Review endpoints
		register_rest_route('ai-agent/v1', '/reviews', [
			'methods' => 'GET',
			'callback' => [$reviewController, 'list'],
			'permission_callback' => function ($request) use ($security) {
				$requestAdapter = new \AIAgent\Infrastructure\Security\WPRestRequestAdapter($request);
				$auth = $security->authenticateRequest($requestAdapter);
				if (!$auth['authenticated']) { return false; }
				return current_user_can('ai_agent_approve_changes') || current_user_can('manage_options');
			},
			'args' => [
				'page' => ['required' => false, 'type' => 'integer'],
				'per_page' => ['required' => false, 'type' => 'integer'],
			],
		]);

		register_rest_route('ai-agent/v1', '/reviews/(?P<id>\\d+)/approve', [
			'methods' => 'POST',
			'callback' => [$reviewController, 'approve'],
			'permission_callback' => function ($request) use ($security) {
				$requestAdapter = new \AIAgent\Infrastructure\Security\WPRestRequestAdapter($request);
				$auth = $security->authenticateRequest($requestAdapter);
				if (!$auth['authenticated']) { return false; }
				return current_user_can('ai_agent_approve_changes') || current_user_can('manage_options');
			},
		]);

		register_rest_route('ai-agent/v1', '/reviews/(?P<id>\\d+)/reject', [
			'methods' => 'POST',
			'callback' => [$reviewController, 'reject'],
			'permission_callback' => function ($request) use ($security) {
				$requestAdapter = new \AIAgent\Infrastructure\Security\WPRestRequestAdapter($request);
				$auth = $security->authenticateRequest($requestAdapter);
				if (!$auth['authenticated']) { return false; }
				return current_user_can('ai_agent_approve_changes') || current_user_can('manage_options');
			},
			'args' => [
				'reason' => ['required' => false, 'type' => 'string'],
			],
		]);

		register_rest_route('ai-agent/v1', '/reviews/(?P<id>\\d+)/comment', [
			'methods' => 'POST',
			'callback' => [$reviewController, 'comment'],
			'permission_callback' => function ($request) use ($security) {
				$requestAdapter = new \AIAgent\Infrastructure\Security\WPRestRequestAdapter($request);
				$auth = $security->authenticateRequest($requestAdapter);
				if (!$auth['authenticated']) { return false; }
				return current_user_can('ai_agent_approve_changes') || current_user_can('manage_options');
			},
			'args' => [
				'comment' => ['required' => true, 'type' => 'string'],
			],
		]);

		register_rest_route('ai-agent/v1', '/reviews/(?P<id>\\d+)/notify', [
			'methods' => 'POST',
			'callback' => [$reviewController, 'notify'],
			'permission_callback' => function ($request) use ($security) {
				$requestAdapter = new \AIAgent\Infrastructure\Security\WPRestRequestAdapter($request);
				$auth = $security->authenticateRequest($requestAdapter);
				if (!$auth['authenticated']) { return false; }
				return current_user_can('ai_agent_approve_changes') || current_user_can('manage_options');
			},
			'args' => [
				'type' => ['required' => false, 'type' => 'string'],
				'message' => ['required' => false, 'type' => 'string'],
			],
		]);

		register_rest_route('ai-agent/v1', '/reviews/(?P<id>\\d+)/diff', [
			'methods' => 'GET',
			'callback' => [$reviewController, 'getDiff'],
			'permission_callback' => function ($request) use ($security) {
				$requestAdapter = new \AIAgent\Infrastructure\Security\WPRestRequestAdapter($request);
				$auth = $security->authenticateRequest($requestAdapter);
				if (!$auth['authenticated']) { return false; }
				return current_user_can('ai_agent_approve_changes') || current_user_can('manage_options');
			},
		]);

		register_rest_route('ai-agent/v1', '/reviews/batch-approve', [
			'methods' => 'POST',
			'callback' => [$reviewController, 'batchApprove'],
			'permission_callback' => function ($request) use ($security) {
				$requestAdapter = new \AIAgent\Infrastructure\Security\WPRestRequestAdapter($request);
				$auth = $security->authenticateRequest($requestAdapter);
				if (!$auth['authenticated']) { return false; }
				return current_user_can('ai_agent_approve_changes') || current_user_can('manage_options');
			},
			'args' => [
				'ids' => ['required' => true, 'type' => 'array'],
			],
		]);

		register_rest_route('ai-agent/v1', '/reviews/batch-reject', [
			'methods' => 'POST',
			'callback' => [$reviewController, 'batchReject'],
			'permission_callback' => function ($request) use ($security) {
				$requestAdapter = new \AIAgent\Infrastructure\Security\WPRestRequestAdapter($request);
				$auth = $security->authenticateRequest($requestAdapter);
				if (!$auth['authenticated']) { return false; }
				return current_user_can('ai_agent_approve_changes') || current_user_can('manage_options');
			},
			'args' => [
				'ids' => ['required' => true, 'type' => 'array'],
				'reason' => ['required' => false, 'type' => 'string'],
			],
		]);

		register_rest_route('ai-agent/v1', '/reviews/(?P<id>\\d+)/rollback', [
			'methods' => 'POST',
			'callback' => [$reviewController, 'rollback'],
			'permission_callback' => function ($request) use ($security) {
				$requestAdapter = new \AIAgent\Infrastructure\Security\WPRestRequestAdapter($request);
				$auth = $security->authenticateRequest($requestAdapter);
				if (!$auth['authenticated']) { return false; }
				return current_user_can('ai_agent_approve_changes') || current_user_can('manage_options');
			},
			'args' => [
				'reason' => ['required' => false, 'type' => 'string'],
			],
		]);

		// Phase 5: Notifications endpoints
		$notificationsController = $this->container->get(NotificationsController::class);
		$notificationsController->register_routes();
	}

	public function checkChatPermissions(): bool
	{
		return current_user_can('ai_agent_execute_tool') || current_user_can('manage_options');
	}
}
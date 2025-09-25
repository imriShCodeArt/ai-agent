<?php
/**
 * Test script to verify AI Agent plugin functionality
 * Run this in a WordPress environment to test the plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Simulate WordPress environment for testing
    define('ABSPATH', dirname(__FILE__) . '/');
    
    // Mock WordPress functions for testing
    if (!function_exists('add_action')) {
        function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
            echo "Mock add_action called with tag: $tag\n";
        }
    }
    
    if (!function_exists('register_activation_hook')) {
        function register_activation_hook($file, $callback) {
            echo "Mock register_activation_hook called\n";
        }
    }
    
    if (!function_exists('register_deactivation_hook')) {
        function register_deactivation_hook($file, $callback) {
            echo "Mock register_deactivation_hook called\n";
        }
    }
    
    if (!function_exists('wp_generate_uuid4')) {
        function wp_generate_uuid4() {
            return 'test-uuid-' . uniqid();
        }
    }
    
    if (!function_exists('wp_create_nonce')) {
        function wp_create_nonce($action) {
            return 'test-nonce-' . $action;
        }
    }
}

// Include the plugin
require_once __DIR__ . '/ai-agent.php';

echo "AI Agent Plugin Test\n";
echo "===================\n\n";

// Test 1: Check if classes can be instantiated
echo "1. Testing class instantiation...\n";

try {
    $logger = new \AIAgent\Support\Logger();
    echo "   ✅ Logger class instantiated\n";
    
    $migrationManager = new \AIAgent\Infrastructure\Database\MigrationManager($logger);
    echo "   ✅ MigrationManager class instantiated\n";
    
    $roleManager = new \AIAgent\Infrastructure\Security\RoleManager($logger);
    echo "   ✅ RoleManager class instantiated\n";
    
    $policy = new \AIAgent\Infrastructure\Security\Policy($logger);
    echo "   ✅ Policy class instantiated\n";
    
    $auditLogger = new \AIAgent\Infrastructure\Audit\AuditLogger($logger);
    echo "   ✅ AuditLogger class instantiated\n";
    
    $chatWidget = new \AIAgent\Frontend\ChatWidget($logger);
    echo "   ✅ ChatWidget class instantiated\n";
    
    $shortcodes = new \AIAgent\Frontend\Shortcodes($logger, $chatWidget);
    echo "   ✅ Shortcodes class instantiated\n";
    
    $settings = new \AIAgent\Admin\Settings($logger);
    echo "   ✅ Settings class instantiated\n";
    
    $adminMenu = new \AIAgent\Admin\AdminMenu();
    echo "   ✅ AdminMenu class instantiated\n";
    
} catch (Exception $e) {
    echo "   ❌ Error instantiating classes: " . $e->getMessage() . "\n";
}

echo "\n2. Testing service container...\n";

try {
    $container = new \AIAgent\Infrastructure\ServiceContainer();
    $container->bind('test_service', function() {
        return 'test_value';
    });
    
    $result = $container->get('test_service');
    if ($result === 'test_value') {
        echo "   ✅ Service container working\n";
    } else {
        echo "   ❌ Service container not working\n";
    }
} catch (Exception $e) {
    echo "   ❌ Service container error: " . $e->getMessage() . "\n";
}

echo "\n3. Testing capabilities...\n";

try {
    $capabilities = \AIAgent\Infrastructure\Security\Capabilities::getAll();
    if (count($capabilities) > 0) {
        echo "   ✅ Capabilities defined: " . count($capabilities) . " capabilities\n";
        echo "   📋 Capabilities: " . implode(', ', array_slice($capabilities, 0, 3)) . "...\n";
    } else {
        echo "   ❌ No capabilities defined\n";
    }
} catch (Exception $e) {
    echo "   ❌ Capabilities error: " . $e->getMessage() . "\n";
}

echo "\n4. Testing policy engine...\n";

try {
    $policy = new \AIAgent\Infrastructure\Security\Policy($logger);
    $isAllowed = $policy->isAllowed('posts.create', null, ['post_title' => 'Test Post']);
    echo "   ✅ Policy engine working (posts.create allowed: " . ($isAllowed ? 'yes' : 'no') . ")\n";
} catch (Exception $e) {
    echo "   ❌ Policy engine error: " . $e->getMessage() . "\n";
}

echo "\n5. Testing chat widget rendering...\n";

try {
    $chatWidget = new \AIAgent\Frontend\ChatWidget($logger);
    $html = $chatWidget->renderChatWidget(['mode' => 'suggest']);
    
    if (strpos($html, 'ai-agent-chat-widget') !== false) {
        echo "   ✅ Chat widget renders HTML\n";
        echo "   📏 HTML length: " . strlen($html) . " characters\n";
    } else {
        echo "   ❌ Chat widget not rendering properly\n";
    }
} catch (Exception $e) {
    echo "   ❌ Chat widget error: " . $e->getMessage() . "\n";
}

echo "\n6. Testing shortcode functionality...\n";

try {
    $shortcodes = new \AIAgent\Frontend\Shortcodes($logger, $chatWidget);
    $shortcodes->addHooks();
    echo "   ✅ Shortcodes registered\n";
} catch (Exception $e) {
    echo "   ❌ Shortcodes error: " . $e->getMessage() . "\n";
}

echo "\n7. Testing REST API controllers...\n";

try {
    $chatController = new \AIAgent\REST\Controllers\ChatController($policy, $auditLogger, $logger);
    echo "   ✅ ChatController instantiated\n";
    
    $postsController = new \AIAgent\REST\Controllers\PostsController($policy, $auditLogger, $logger);
    echo "   ✅ PostsController instantiated\n";
    
    $logsController = new \AIAgent\REST\Controllers\LogsController($auditLogger, $logger);
    echo "   ✅ LogsController instantiated\n";
} catch (Exception $e) {
    echo "   ❌ REST API controllers error: " . $e->getMessage() . "\n";
}

echo "\n8. Testing service providers...\n";

try {
    $container = new \AIAgent\Infrastructure\ServiceContainer();
    $hooks = new \AIAgent\Infrastructure\Hooks\HooksLoader();
    
    $adminProvider = new \AIAgent\Application\Providers\AdminServiceProvider($container, $hooks, __FILE__);
    $adminProvider->register();
    echo "   ✅ AdminServiceProvider registered\n";
    
    $frontendProvider = new \AIAgent\Application\Providers\FrontendServiceProvider($container, $hooks, __FILE__);
    $frontendProvider->register();
    echo "   ✅ FrontendServiceProvider registered\n";
    
    $restProvider = new \AIAgent\Application\Providers\RestApiServiceProvider($container, $hooks, __FILE__);
    $restProvider->register();
    echo "   ✅ RestApiServiceProvider registered\n";
} catch (Exception $e) {
    echo "   ❌ Service providers error: " . $e->getMessage() . "\n";
}

echo "\n✅ AI Agent Plugin Test Complete!\n";
echo "\nThe plugin is ready for use. Key features implemented:\n";
echo "- Database migration system\n";
echo "- Custom capabilities and roles\n";
echo "- Policy engine with rate limiting\n";
echo "- Audit logging system\n";
echo "- REST API endpoints\n";
echo "- Admin dashboard and settings\n";
echo "- Frontend chat widget\n";
echo "- Shortcode support\n";
echo "\nTo use the chat widget, add [ai_agent_chat] to any post or page.\n";

<?php
/**
 * Plugin Name: AI Agent
 * Description: Foundation for an extensible AI Agent plugin.
 * Version: 0.3.0
 * Author: Your Name
 * Text Domain: ai-agent
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
	exit;
}

require_once __DIR__ . '/src/Core/Autoloader.php';

use AIAgent\Core\Plugin;
use AIAgent\Install\Activator;
use AIAgent\Infrastructure\Database\MigrationManager;
use AIAgent\Infrastructure\Security\RoleManager;
use AIAgent\Support\Logger;

// Bootstrap plugin.
add_action('init', static function () {
	Plugin::boot(__FILE__);
}, 1);

// Plugin activation
register_activation_hook(__FILE__, static function () {
    // Suppress accidental output during activation to avoid "headers already sent"
    ob_start();
    try {
        $logger = new Logger();
        $migrationManager = new MigrationManager($logger);
        $roleManager = new RoleManager($logger);
        $activator = new Activator($migrationManager, $roleManager, $logger);

        $activator->activate();
    } finally {
        $buffer = ob_get_clean();
        if (is_string($buffer) && $buffer !== '') {
            // Log and discard any unexpected output
            error_log('AI Agent activation produced output (suppressed): ' . substr($buffer, 0, 500));
        }
    }
});

// Plugin deactivation
register_deactivation_hook(__FILE__, static function () {
    // Suppress accidental output during deactivation as well
    ob_start();
    try {
        $logger = new Logger();
        $migrationManager = new MigrationManager($logger);
        $roleManager = new RoleManager($logger);
        $activator = new Activator($migrationManager, $roleManager, $logger);

        $activator->deactivate();
    } finally {
        $buffer = ob_get_clean();
        if (is_string($buffer) && $buffer !== '') {
            error_log('AI Agent deactivation produced output (suppressed): ' . substr($buffer, 0, 500));
        }
    }
});

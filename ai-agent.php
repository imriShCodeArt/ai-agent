<?php
/**
 * Plugin Name: AI Agent
 * Description: Foundation for an extensible AI Agent plugin.
 * Version: 0.2.1
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
add_action('plugins_loaded', static function () {
	Plugin::boot(__FILE__);
});

// Plugin activation
register_activation_hook(__FILE__, static function () {
	$logger = new Logger();
	$migrationManager = new MigrationManager($logger);
	$roleManager = new RoleManager($logger);
	$activator = new Activator($migrationManager, $roleManager, $logger);
	
	$activator->activate();
});

// Plugin deactivation
register_deactivation_hook(__FILE__, static function () {
	$logger = new Logger();
	$migrationManager = new MigrationManager($logger);
	$roleManager = new RoleManager($logger);
	$activator = new Activator($migrationManager, $roleManager, $logger);
	
	$activator->deactivate();
});

<?php
/**
 * Uninstall script for AI Agent plugin
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

require_once __DIR__ . '/src/Core/Autoloader.php';

use AIAgent\Install\Activator;
use AIAgent\Infrastructure\Database\MigrationManager;
use AIAgent\Infrastructure\Security\RoleManager;
use AIAgent\Support\Logger;

// Run uninstall process
$logger = new Logger();
$migrationManager = new MigrationManager($logger);
$roleManager = new RoleManager($logger);
$activator = new Activator($migrationManager, $roleManager, $logger);

$activator->uninstall();
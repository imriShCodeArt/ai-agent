<?php
/**
 * Plugin Name: AI Agent
 * Description: Foundation for an extensible AI Agent plugin.
 * Version: 0.1.1
 * Author: Your Name
 * Text Domain: ai-agent
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
	exit;
}

require_once __DIR__ . '/src/Core/Autoloader.php';

use AIAgent\Core\Plugin;

// Bootstrap plugin.
add_action('plugins_loaded', static function () {
	Plugin::boot(__FILE__);
});

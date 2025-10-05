<?php
/**
 * Debug script to test admin UI registration
 * Access via: /wp-content/plugins/ai-agent/debug-admin.php
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    wp_die('You must be an administrator to access this debug page.');
}

echo "<h1>AI Agent Debug Information</h1>";

// Check if plugin is loaded
if (class_exists('AIAgent\Core\Plugin')) {
    echo "<p>✅ AI Agent plugin classes are loaded</p>";
} else {
    echo "<p>❌ AI Agent plugin classes are NOT loaded</p>";
}

// Check capabilities
$capabilities = [
    'ai_agent_manage_policies',
    'ai_agent_approve_changes', 
    'ai_agent_execute_tool',
    'ai_agent_view_logs'
];

echo "<h2>Capability Check</h2>";
foreach ($capabilities as $cap) {
    if (current_user_can($cap)) {
        echo "<p>✅ User has capability: $cap</p>";
    } else {
        echo "<p>❌ User does NOT have capability: $cap</p>";
    }
}

// Check if admin menu is registered
global $menu, $submenu;
echo "<h2>WordPress Admin Menu Check</h2>";

$aiAgentFound = false;
foreach ($menu as $item) {
    if (isset($item[2]) && $item[2] === 'ai-agent') {
        $aiAgentFound = true;
        echo "<p>✅ AI Agent main menu found: " . $item[0] . "</p>";
        break;
    }
}

if (!$aiAgentFound) {
    echo "<p>❌ AI Agent main menu NOT found</p>";
}

// Check submenus
if (isset($submenu['ai-agent'])) {
    echo "<p>✅ AI Agent submenus found:</p><ul>";
    foreach ($submenu['ai-agent'] as $submenu_item) {
        echo "<li>" . $submenu_item[0] . " (page: " . $submenu_item[2] . ")</li>";
    }
    echo "</ul>";
} else {
    echo "<p>❌ AI Agent submenus NOT found</p>";
}

// Test direct page access
echo "<h2>Direct Page Access Test</h2>";
$pages = [
    'ai-agent' => 'Dashboard',
    'ai-agent-settings' => 'Settings', 
    'ai-agent-tools' => 'Tools',
    'ai-agent-policies' => 'Policies',
    'ai-agent-reviews' => 'Reviews',
    'ai-agent-logs' => 'Audit Logs'
];

foreach ($pages as $page => $name) {
    $url = admin_url("admin.php?page=$page");
    echo "<p><a href='$url' target='_blank'>Test $name Page</a></p>";
}

// Check error log
echo "<h2>Recent Error Log Entries</h2>";
$log_file = WP_CONTENT_DIR . '/debug.log';
if (file_exists($log_file)) {
    $log_content = file_get_contents($log_file);
    $ai_agent_logs = array_filter(explode("\n", $log_content), function($line) {
        return strpos($line, 'AI Agent') !== false;
    });
    
    if (!empty($ai_agent_logs)) {
        echo "<p>Recent AI Agent log entries:</p><pre>";
        echo implode("\n", array_slice($ai_agent_logs, -10));
        echo "</pre>";
    } else {
        echo "<p>No AI Agent log entries found</p>";
    }
} else {
    echo "<p>No debug.log file found</p>";
}

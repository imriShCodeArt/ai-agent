<?php

// Load Composer autoload if available
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Minimal WordPress function stubs for unit testing context
if (!function_exists('add_action')) {
    /**
     * @param string $hook
     * @param callable $callback
     * @param int $priority
     * @param int $accepted_args
     */
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1): void {}
}

if (!function_exists('has_action')) {
    /**
     * @param string $hook
     * @param callable|false $callback
     * @return int|false
     */
    function has_action($hook, $callback = false) {
        // For testing purposes, return a priority value to simulate hook registration
        return 10;
    }
}

if (!function_exists('register_rest_route')) {
    /**
     * @param string $namespace
     * @param string $route
     * @param array $args
     */
    function register_rest_route($namespace, $route, $args = []): void {}
}

// Basic WP option stubs used in unit tests
if (!isset($GLOBALS['AI_AGENT_TEST_OPTIONS'])) { $GLOBALS['AI_AGENT_TEST_OPTIONS'] = []; }
if (!function_exists('get_option')) {
    function get_option($name, $default = false) {
        $store = &$GLOBALS['AI_AGENT_TEST_OPTIONS'];
        return array_key_exists($name, $store) ? $store[$name] : $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($name, $value) {
        $store = &$GLOBALS['AI_AGENT_TEST_OPTIONS'];
        $store[$name] = $value;
        return true;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) { return is_string($str) ? trim(strip_tags($str)) : $str; }
}

if (!function_exists('sanitize_title')) {
    function sanitize_title($str) { return strtolower(preg_replace('/[^a-z0-9-]+/i', '-', (string) $str)); }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($content) { return (string) $content; }
}

// WooCommerce-related globals used by Mappers
if (!function_exists('get_woocommerce_currency')) {
    function get_woocommerce_currency() { return 'USD'; }
}

if (!function_exists('wp_get_post_terms')) {
    function wp_get_post_terms($id, $tax, $args) { return ['Category A']; }
}

if (!class_exists('WP_Error')) {
    class WP_Error {
        private $code; private $message;
        public function __construct($code, $message) { $this->code = $code; $this->message = $message; }
        public function get_error_code() { return $this->code; }
        public function get_error_message() { return $this->message; }
    }
}

if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response {
        public function __construct($data) { $this->data = $data; }
    }
}

if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request { public function get_param($k) { return null; } }
}



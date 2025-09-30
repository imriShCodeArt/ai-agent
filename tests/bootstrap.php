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

// Minimal WP query/types stubs
if (!function_exists('get_post_type_object')) {
    function get_post_type_object($type) { return (object) ['name' => $type]; }
}

// WordPress filter stub
if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value) { return $value; }
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
if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data) { return json_encode($data); }
}
if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

// Admin/menu related stubs for rendering/admin tests
if (!function_exists('add_menu_page')) {
    function add_menu_page($page_title, $menu_title, $capability, $menu_slug, $callback = null, $icon_url = null, $position = null) {}
}
if (!function_exists('add_submenu_page')) {
    function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback = null) {}
}
if (!function_exists('wp_redirect')) {
    function wp_redirect($url) { return true; }
}
if (!function_exists('admin_url')) {
    function admin_url($path = '') { return '/wp-admin/' . ltrim((string) $path, '/'); }
}
if (!function_exists('rest_url')) {
    function rest_url($path = '') { return '/wp-json/' . ltrim((string) $path, '/'); }
}
if (!function_exists('current_user_can')) {
    function current_user_can($cap) { return isset($GLOBALS['AI_AGENT_TEST_CURRENT_USER_CAN']) ? (bool) $GLOBALS['AI_AGENT_TEST_CURRENT_USER_CAN'] : true; }
}
if (!function_exists('wp_die')) {
    function wp_die($msg) { throw new \RuntimeException((string) $msg); }
}
if (!function_exists('get_role')) {
    function get_role($role) { return (object) ['name' => $role]; }
}
if (!function_exists('get_user_by')) {
    function get_user_by($field, $value) { return (object) ['display_name' => 'User', 'ID' => 1]; }
}
if (!function_exists('get_users')) {
    function get_users() { return [(object) ['ID' => 1, 'display_name' => 'User']]; }
}
if (!function_exists('selected')) {
    function selected($a, $b) { return $a === $b ? 'selected(1)' : ''; }
}
if (!function_exists('esc_html')) {
    function esc_html($s) { return (string) $s; }
}
if (!function_exists('esc_attr')) {
    function esc_attr($s) { return (string) $s; }
}
if (!function_exists('esc_url_raw')) {
    function esc_url_raw($s) { return (string) $s; }
}
if (!function_exists('wp_nonce_field')) {
    function wp_nonce_field($action, $name) { return ''; }
}
if (!function_exists('current_time')) {
    function current_time($type) { return date('Y-m-d H:i:s'); }
}

// Provide a global $wpdb stub for most tests
if (!isset($GLOBALS['wpdb'])) {
    $GLOBALS['wpdb'] = new class {
        public string $prefix = 'wp_';
        public int $insert_id = 1;
        public string $last_error = '';
        public function insert($table, $data) { $this->insert_id = 1; return 1; }
        public function update($table, $data, $where = []) { return 1; }
        public function get_var($q){ return null; }
        public function get_results($q,$type=ARRAY_A){ return []; }
        public function query($q){ return true; }
        public function prepare($q){ return $q; }
        public function get_charset_collate(){ return ''; }
    };
}

// Ensure wpdb has update method even if pre-set elsewhere
if (isset($GLOBALS['wpdb']) && !method_exists($GLOBALS['wpdb'], 'update')) {
    $GLOBALS['wpdb'] = new class {
        public string $prefix = 'wp_';
        public int $insert_id = 1;
        public string $last_error = '';
        public function insert($table, $data) { $this->insert_id = 1; return 1; }
        public function update($table, $data, $where = []) { return 1; }
        public function get_var($q){ return null; }
        public function get_results($q,$type=ARRAY_A){ return []; }
        public function query($q){ return true; }
        public function prepare($q){ return $q; }
        public function get_charset_collate(){ return ''; }
    };
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
        private $code; private $message; private $data;
        public function __construct($code, $message = '', $data = null) { $this->code = $code; $this->message = $message; $this->data = $data; }
        public function get_error_code() { return $this->code; }
        public function get_error_message() { return $this->message; }
        public function get_error_data() { return $this->data; }
    }
}

if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response {
        public $data; public $status; public $headers;
        public function __construct($data, $status = 200, $headers = []) { $this->data = $data; $this->status = $status; $this->headers = $headers; }
    }
}

if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request {
        private $params = []; private $headers = [];
        public function __construct($params = [], $headers = []) { $this->params = $params; $this->headers = $headers; }
        public function get_param($k) { return $this->params[$k] ?? null; }
        public function get_header($k) { $k = strtolower($k); return $this->headers[$k] ?? null; }
        public function get_body() { return ''; }
        public function get_route() { return '/'; }
    }
}

if (!function_exists('wp_mail')) {
    function wp_mail($to, $subject, $message) { return true; }
}



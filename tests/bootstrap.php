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

if (!function_exists('register_rest_route')) {
    /**
     * @param string $namespace
     * @param string $route
     * @param array $args
     */
    function register_rest_route($namespace, $route, $args = []): void {}
}



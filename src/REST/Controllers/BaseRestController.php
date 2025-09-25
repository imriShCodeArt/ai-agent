<?php
namespace AIAgent\REST\Controllers;

use AIAgent\Infrastructure\Hooks\HookableInterface;

abstract class BaseRestController implements HookableInterface {
public function addHooks(): void {
add_action('rest_api_init', [ $this, 'registerRoutes' ]);
}

public function registerRoutes(): void {
// Placeholder for REST routes.
}
}
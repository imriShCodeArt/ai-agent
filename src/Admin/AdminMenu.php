<?php
namespace AIAgent\Admin;

use AIAgent\Infrastructure\Hooks\HookableInterface;

final class AdminMenu implements HookableInterface {
public function addHooks(): void {
add_action('admin_menu', [ $this, 'registerMenu' ]);
}

public function registerMenu(): void {
// Placeholder admin menu.
}
}
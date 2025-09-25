<?php
namespace AIAgent\Frontend;

use AIAgent\Infrastructure\Hooks\HookableInterface;

final class Shortcodes implements HookableInterface {
public function addHooks(): void {
add_action('init', [ $this, 'registerShortcodes' ]);
}

public function registerShortcodes(): void {
// Placeholder shortcode registration.
}
}
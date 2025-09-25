<?php
namespace AIAgent\Application\Providers;

use AIAgent\Frontend\Shortcodes;

final class FrontendServiceProvider extends AbstractServiceProvider {
public function register(): void {
$this->hooks->add(new Shortcodes());
}
}
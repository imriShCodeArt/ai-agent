<?php
namespace AIAgent\Application\Providers;

use AIAgent\Admin\AdminMenu;

final class AdminServiceProvider extends AbstractServiceProvider {
public function register(): void {
$this->hooks->add(new AdminMenu());
}
}
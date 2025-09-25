<?php
namespace AIAgent\\Application\\Providers;

use AIAgent\\REST\\Controllers\\BaseRestController;

final class RestApiServiceProvider extends AbstractServiceProvider {
public function register(): void {
$this->hooks->add(new class extends BaseRestController {});
}
}
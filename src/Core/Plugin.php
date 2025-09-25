<?php
namespace AIAgent\\Core;

use AIAgent\\Infrastructure\\Hooks\\HooksLoader;
use AIAgent\\Infrastructure\\ServiceContainer;
use AIAgent\\Application\\Providers\\AdminServiceProvider;
use AIAgent\\Application\\Providers\\FrontendServiceProvider;
use AIAgent\\Application\\Providers\\RestApiServiceProvider;
use AIAgent\\Application\\Providers\\CliServiceProvider;

final class Plugin {
private static ?self $instance = null;
private string $pluginFile;
private ServiceContainer $container;
private HooksLoader $hooks;

private function __construct(string $pluginFile) {
$this->pluginFile = $pluginFile;
$this->container = new ServiceContainer();
$this->hooks = new HooksLoader();
}

public static function boot(string $pluginFile): void {
if (self::$instance === null) {
self::$instance = new self($pluginFile);
self::$instance->registerProviders();
self::$instance->hooks->register();
}
}

private function registerProviders(): void {
$providers = [
AdminServiceProvider::class,
FrontendServiceProvider::class,
RestApiServiceProvider::class,
CliServiceProvider::class,
];
foreach ($providers as $provider) {
$instance = new $provider($this->container, $this->hooks, $this->pluginFile);
if (method_exists($instance, 'register')) {
$instance->register();
}
}
}
}
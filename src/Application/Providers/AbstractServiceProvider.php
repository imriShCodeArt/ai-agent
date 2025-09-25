<?php
namespace AIAgent\Application\Providers;

use AIAgent\Infrastructure\ServiceProviderInterface;
use AIAgent\Infrastructure\Hooks\HooksLoader;
use AIAgent\Infrastructure\ServiceContainer;

abstract class AbstractServiceProvider implements ServiceProviderInterface {
protected ServiceContainer $container;
protected HooksLoader $hooks;
protected string $pluginFile;

public function __construct(ServiceContainer $container, HooksLoader $hooks, string $pluginFile) {
$this->container = $container;
$this->hooks = $hooks;
$this->pluginFile = $pluginFile;
}
}
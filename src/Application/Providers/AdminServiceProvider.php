<?php

namespace AIAgent\Application\Providers;

use AIAgent\Infrastructure\Hooks\HookableInterface;
use AIAgent\Admin\AdminMenu;
use AIAgent\Admin\Settings;
use AIAgent\Support\Logger;

final class AdminServiceProvider extends AbstractServiceProvider implements HookableInterface
{
    public function register(): void
    {
        // Register services in container
        $this->container->singleton(Settings::class, function () {
            return new Settings($this->container->get(Logger::class));
        });

        $this->container->singleton(AdminMenu::class, function () {
            return new AdminMenu();
        });
    }

    public function registerHooks(): void
    {
        $settings = $this->container->get(Settings::class);
        $adminMenu = $this->container->get(AdminMenu::class);

        add_action('admin_menu', [$adminMenu, 'addMenuPage']);
        add_action('admin_init', [$settings, 'registerSettings']);
        add_action('admin_init', [$settings, 'addSettingsPage']);
    }

    public function addHooks(): void
    {
        $this->registerHooks();
    }
}
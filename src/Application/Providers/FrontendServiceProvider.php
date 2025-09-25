<?php

namespace AIAgent\Application\Providers;

use AIAgent\Infrastructure\Hooks\HookableInterface;
use AIAgent\Frontend\Shortcodes;
use AIAgent\Frontend\ChatWidget;
use AIAgent\Support\Logger;

final class FrontendServiceProvider extends AbstractServiceProvider implements HookableInterface
{
    public function register(): void
    {
        // Register services in container
        $this->container->singleton(ChatWidget::class, function () {
            return new ChatWidget($this->container->get(Logger::class));
        });

        $this->container->singleton(Shortcodes::class, function () {
            return new Shortcodes(
                $this->container->get(Logger::class),
                $this->container->get(ChatWidget::class)
            );
        });
    }

    public function registerHooks(): void
    {
        $shortcodes = $this->container->get(Shortcodes::class);
        $shortcodes->addHooks();
    }

    public function addHooks(): void
    {
        $this->registerHooks();
    }
}
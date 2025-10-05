<?php
namespace AIAgent\Core;

use AIAgent\Infrastructure\Hooks\HooksLoader;
use AIAgent\Infrastructure\ServiceContainer;
use AIAgent\Application\Providers\AdminServiceProvider;
use AIAgent\Application\Providers\FrontendServiceProvider;
use AIAgent\Application\Providers\RestApiServiceProvider;
use AIAgent\Application\Providers\CliServiceProvider;
use AIAgent\Application\Providers\AsyncServiceProvider;
use AIAgent\Support\Logger;

/**
 * Main plugin class responsible for bootstrapping the AI Agent plugin
 * 
 * This class follows the Singleton pattern to ensure only one instance
 * exists throughout the plugin lifecycle. It manages the service container,
 * hooks loader, and service providers.
 * 
 * @since 1.0.0
 * @package AIAgent\Core
 */
final class Plugin {
    /**
     * Singleton instance of the plugin
     * 
     * @var self|null
     */
    private static ?self $instance = null;
    
    /**
     * Path to the main plugin file
     * 
     * @var string
     */
    private string $pluginFile;
    
    /**
     * Service container for dependency injection
     * 
     * @var ServiceContainer
     */
    private ServiceContainer $container;
    
    /**
     * Hooks loader for WordPress integration
     * 
     * @var HooksLoader
     */
    private HooksLoader $hooks;

    /**
     * Private constructor to prevent direct instantiation
     * 
     * @param string $pluginFile Path to the main plugin file
     * @since 1.0.0
     */
    private function __construct(string $pluginFile) {
        $this->pluginFile = $pluginFile;
        $this->container = new ServiceContainer();
        $this->hooks = new HooksLoader();
        
        // Bind core singletons
        $this->container->singleton(Logger::class, static function () {
            return new Logger();
        });
    }

    /**
     * Boot the plugin with the given plugin file
     * 
     * This method initializes the plugin singleton and registers all
     * service providers and hooks. It should be called once during
     * plugin activation.
     * 
     * @param string $pluginFile Path to the main plugin file
     * @return void
     * @since 1.0.0
     */
    public static function boot(string $pluginFile): void {
        if (self::$instance === null) {
            error_log('AI Agent: Plugin::boot() called');
            self::$instance = new self($pluginFile);
            self::$instance->registerProviders();
            self::$instance->hooks->register();
            error_log('AI Agent: Plugin boot completed');
        }
    }

    /**
     * Register all service providers
     * 
     * This method instantiates and registers all service providers
     * that are responsible for setting up different parts of the plugin.
     * 
     * @return void
     * @since 1.0.0
     */
    private function registerProviders(): void {
        $providers = [
            AdminServiceProvider::class,
            FrontendServiceProvider::class,
            RestApiServiceProvider::class,
            CliServiceProvider::class,
            AsyncServiceProvider::class,
        ];
        
        foreach ($providers as $provider) {
            $instance = new $provider($this->container, $this->hooks, $this->pluginFile);
            if (method_exists($instance, 'register')) {
                $instance->register();
            }
            // Add hookable providers to the hooks loader
            if ($instance instanceof \AIAgent\Infrastructure\Hooks\HookableInterface) {
                $this->hooks->add($instance);
            }
        }
    }
}
<?php
namespace AIAgent\Core;

/**
 * PSR-4 autoloader for the AI Agent plugin
 * 
 * This class provides automatic class loading for the plugin following
 * the PSR-4 autoloading standard. It maps the AIAgent namespace to the
 * src directory and handles class file resolution.
 * 
 * @since 1.0.0
 * @package AIAgent\Core
 */
final class Autoloader {
    /**
     * Register the autoloader with PHP's SPL autoloader
     * 
     * This method registers the autoloader function with PHP's built-in
     * autoloader system. It should be called once during plugin initialization.
     * 
     * @return void
     * @since 1.0.0
     */
    public static function register(): void {
        spl_autoload_register([static::class, 'autoload']);
    }

    /**
     * Autoload a class by its fully qualified name
     * 
     * This method is called by PHP's autoloader system when a class
     * is not found. It converts the class name to a file path and
     * includes the file if it exists and is readable.
     * 
     * @param string $class The fully qualified class name
     * @return void
     * @since 1.0.0
     */
    public static function autoload(string $class): void {
        // Only handle classes in the AIAgent namespace
        if (strpos($class, 'AIAgent\\') !== 0) {
            return;
        }
        
        // Convert namespace to file path
        $relative = str_replace('AIAgent\\', '', $class);
        $relative = str_replace('\\', '/', $relative);
        $file = __DIR__ . '/../' . $relative . '.php';
        
        // Include the file if it exists and is readable
        if (is_readable($file)) {
            require_once $file;
        }
    }
}

Autoloader::register();
<?php
namespace AIAgent\Core;

/**
 * Simple PSR-4 autoloader for the plugin.
 */
final class Autoloader {
public static function register(): void {
spl_autoload_register([static::class, 'autoload']);
}

public static function autoload(string $class): void {
if (strpos($class, 'AIAgent\\') !== 0) {
return;
}
$relative = str_replace('AIAgent\\', '', $class);
$relative = str_replace('\\', '/', $relative);
$file = __DIR__ . '/../' . $relative . '.php';
if (is_readable($file)) {
require_once $file;
}
}
}

Autoloader::register();
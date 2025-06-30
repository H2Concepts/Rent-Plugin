<?php
declare(strict_types=1);

namespace FederwiegenVerleih;

class Autoloader
{
    public static function register(): void
    {
        spl_autoload_register([__CLASS__, 'load']);
    }

    public static function load(string $class): void
    {
        if (strpos($class, __NAMESPACE__ . '\\') !== 0) {
            return;
        }
        $relative = substr($class, strlen(__NAMESPACE__) + 1);
        $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
        if (is_readable($path)) {
            require $path;
        }
    }
}
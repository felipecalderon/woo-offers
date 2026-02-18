<?php

declare(strict_types=1);

namespace WooOffers;

final class Autoloader
{
    public static function register(): void
    {
        spl_autoload_register([self::class, 'load']);
    }

    private static function load(string $class): void
    {
        $prefix = __NAMESPACE__ . '\\';

        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            return;
        }

        $relativeClass = substr($class, strlen($prefix));
        $file = WOO_OFFERS_PATH . 'src/' . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

        if (is_readable($file)) {
            require_once $file;
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Core;

class Config
{
    private static array $config = [];
    private static bool  $loaded = false;

    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }

        foreach (glob($path . '/*.php') as $file) {
            $key = basename($file, '.php');
            self::$config[$key] = require $file;
        }

        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key);
        $value = self::$config;

        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }

    public static function set(string $key, mixed $value): void
    {
        $parts  = explode('.', $key);
        $config = &self::$config;

        foreach ($parts as $i => $part) {
            if ($i === count($parts) - 1) {
                $config[$part] = $value;
            } else {
                if (!isset($config[$part]) || !is_array($config[$part])) {
                    $config[$part] = [];
                }
                $config = &$config[$part];
            }
        }
    }

    public static function has(string $key): bool
    {
        return self::get($key) !== null;
    }

    public static function all(): array
    {
        return self::$config;
    }
}

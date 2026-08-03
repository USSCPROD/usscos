<?php

declare(strict_types=1);

namespace App\Core;

class Logger
{
    private static string $path     = '';
    private static string $level    = 'error';
    private static array  $levels   = [
        'debug'     => 100,
        'info'      => 200,
        'notice'    => 250,
        'warning'   => 300,
        'error'     => 400,
        'critical'  => 500,
        'alert'     => 550,
        'emergency' => 600,
    ];

    public static function init(): void
    {
        self::$level = Config::get('logging.level', 'error');
        self::$path  = BASE_PATH . '/storage/logs/app-' . date('Y-m-d') . '.log';

        $dir = dirname(self::$path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public static function debug(string $message, array $context = []): void
    {
        self::log('debug', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::log('critical', $message, $context);
    }

    private static function log(string $level, string $message, array $context = []): void
    {
        if (!self::shouldLog($level)) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $levelUpper = strtoupper($level);
        $contextStr = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_SLASHES);

        $line = "[{$timestamp}] [{$levelUpper}] {$message}{$contextStr}" . PHP_EOL;

        if (self::$path) {
            file_put_contents(self::$path, $line, FILE_APPEND | LOCK_EX);
        }
    }

    private static function shouldLog(string $level): bool
    {
        $minLevel     = self::$levels[self::$level]    ?? 400;
        $currentLevel = self::$levels[$level]          ?? 100;
        return $currentLevel >= $minLevel;
    }
}

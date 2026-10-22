<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Named settings, read by key.
 *
 * Values are cached for the life of the request. A single page can ask for the same
 * setting from several places - the dashboard asks for the A/R opening date once per
 * tile - and there is no reason for each to be its own query.
 */
class SettingsRepository
{
    private static array $cache = [];

    public function get(string $key, ?string $default = null): ?string
    {
        if (!array_key_exists($key, self::$cache)) {
            $row = Database::selectOne(
                'SELECT setting_value FROM settings WHERE setting_key = ?',
                [$key]
            );
            self::$cache[$key] = $row === false ? null : (string)$row['setting_value'];
        }

        $value = self::$cache[$key];

        return ($value === null || $value === '') ? $default : $value;
    }

    public function set(string $key, string $value): void
    {
        Database::statement(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
            [$key, $value]
        );

        self::$cache[$key] = $value;
    }

    /** For tests that need to re-read after writing outside this process. */
    public static function forget(): void
    {
        self::$cache = [];
    }
}

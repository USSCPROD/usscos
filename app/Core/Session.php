<?php

declare(strict_types=1);

namespace App\Core;

class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $cfg = Config::get('session');

        $sessionPath = $cfg['path'] ?? BASE_PATH . '/storage/sessions';
        if (!is_dir($sessionPath)) {
            mkdir($sessionPath, 0755, true);
        }
        session_save_path($sessionPath);

        session_set_cookie_params([
            'lifetime' => (int) ($cfg['lifetime'] ?? 120) * 60,
            'path'     => '/',
            'domain'   => '',
            'secure'   => (bool) ($cfg['secure'] ?? false),
            'httponly' => (bool) ($cfg['http_only'] ?? true),
            'samesite' => $cfg['same_site'] ?? 'Lax',
        ]);

        session_name($cfg['cookie'] ?? 'usscos_session');
        session_start();

        self::$started = true;

        // Regenerate ID periodically to prevent fixation
        if (!isset($_SESSION['_last_regenerated'])) {
            session_regenerate_id(true);
            $_SESSION['_last_regenerated'] = time();
        } elseif (time() - $_SESSION['_last_regenerated'] > 300) {
            session_regenerate_id(true);
            $_SESSION['_last_regenerated'] = time();
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::data()[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public static function hasFlash(string $key): bool
    {
        return isset($_SESSION['_flash'][$key]);
    }

    public static function flashInput(array $input): void
    {
        $_SESSION['_old_input'] = $input;
    }

    public static function oldInput(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_old_input'][$key] ?? $default;
        return $value;
    }

    public static function clearOldInput(): void
    {
        unset($_SESSION['_old_input']);
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
        $_SESSION['_last_regenerated'] = time();
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        session_destroy();
        self::$started = false;
    }

    public static function all(): array
    {
        return self::data();
    }

    public static function id(): string
    {
        return session_id();
    }

    private static function data(): array
    {
        return $_SESSION ?? [];
    }

    // CSRF token management
    public static function csrfToken(): string
    {
        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function validateCsrf(string $token): bool
    {
        return hash_equals(self::csrfToken(), $token);
    }
}

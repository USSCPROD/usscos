<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

class Auth
{
    private static ?array $user = null;

    public static function attempt(string $email, string $password, bool $remember = false): bool
    {
        $cfg  = Config::get('auth');
        $user = Database::selectOne(
            'SELECT * FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1',
            [strtolower(trim($email))]
        );

        if (!$user || !password_verify($password, $user['password'])) {
            Logger::warning('Failed login attempt', ['email' => $email]);
            return false;
        }

        if (!($user['is_active'] ?? true)) {
            return false;
        }

        self::login($user, $remember);
        return true;
    }

    public static function login(array $user, bool $remember = false): void
    {
        Session::regenerate();
        Session::set(Config::get('auth.session.key', 'auth_user'), $user['id']);

        Database::update(
            'UPDATE users SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?',
            [request()->ip(), $user['id']]
        );

        if ($remember) {
            self::setRememberToken($user['id']);
        }

        self::$user = $user;

        Logger::info('User logged in', ['user_id' => $user['id']]);
    }

    public static function logout(): void
    {
        if (self::check()) {
            $userId = self::id();
            self::clearRememberToken();
            Session::destroy();
            Session::start();
            self::$user = null;
            Logger::info('User logged out', ['user_id' => $userId]);
        }
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function guest(): bool
    {
        return !self::check();
    }

    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }

        $sessionKey = Config::get('auth.session.key', 'auth_user');
        $userId     = Session::get($sessionKey);

        if (!$userId) {
            $userId = self::checkRememberToken();
        }

        if (!$userId) {
            return null;
        }

        $user = Database::selectOne(
            'SELECT * FROM users WHERE id = ? AND is_active = 1 AND deleted_at IS NULL LIMIT 1',
            [$userId]
        );

        if (!$user) {
            Session::forget($sessionKey);
            return null;
        }

        self::$user = $user;
        return $user;
    }

    public static function id(): ?int
    {
        return self::user() ? (int) self::user()['id'] : null;
    }

    public static function hasRole(string $role): bool
    {
        $user = self::user();
        return $user && ($user['role'] ?? '') === $role;
    }

    public static function hasPermission(string $permission): bool
    {
        $user = self::user();
        if (!$user) return false;

        $permissions = json_decode($user['permissions'] ?? '[]', true);
        return in_array($permission, $permissions);
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, Config::get('auth.password.algo', PASSWORD_BCRYPT), [
            'cost' => Config::get('auth.password.cost', 12),
        ]);
    }

    private static function setRememberToken(int $userId): void
    {
        $token  = bin2hex(random_bytes(32));
        $hashed = hash('sha256', $token);
        $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));

        Database::update(
            'UPDATE users SET remember_token = ?, remember_token_expires_at = ? WHERE id = ?',
            [$hashed, $expiry, $userId]
        );

        $cookieName = Config::get('auth.remember.cookie', 'remember_token');
        setcookie($cookieName, $token, time() + (60 * 60 * 24 * 30), '/', '', false, true);
    }

    private static function checkRememberToken(): ?int
    {
        $cookieName = Config::get('auth.remember.cookie', 'remember_token');
        $token      = $_COOKIE[$cookieName] ?? null;

        if (!$token) {
            return null;
        }

        $hashed = hash('sha256', $token);
        $user   = Database::selectOne(
            'SELECT id FROM users WHERE remember_token = ? AND remember_token_expires_at > NOW() AND deleted_at IS NULL LIMIT 1',
            [$hashed]
        );

        if ($user) {
            Session::set(Config::get('auth.session.key', 'auth_user'), $user['id']);
            return (int) $user['id'];
        }

        return null;
    }

    private static function clearRememberToken(): void
    {
        $cookieName = Config::get('auth.remember.cookie', 'remember_token');

        if ($userId = self::id()) {
            Database::update(
                'UPDATE users SET remember_token = NULL, remember_token_expires_at = NULL WHERE id = ?',
                [$userId]
            );
        }

        setcookie($cookieName, '', time() - 3600, '/');
    }
}

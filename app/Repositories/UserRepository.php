<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Core\Repository;

class UserRepository extends Repository
{
    protected bool $softDeletes = true;

    protected function getTable(): string
    {
        return 'users';
    }

    public function findByEmail(string $email): array|false
    {
        return Database::selectOne(
            'SELECT * FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1',
            [strtolower(trim($email))]
        );
    }

    public function findByCompany(int $companyId, string $orderBy = 'first_name'): array
    {
        return Database::select(
            'SELECT * FROM users WHERE company_id = ? AND deleted_at IS NULL ORDER BY ' . $orderBy,
            [$companyId]
        );
    }

    public function findActiveByCompany(int $companyId): array
    {
        return Database::select(
            'SELECT * FROM users WHERE company_id = ? AND is_active = 1 AND deleted_at IS NULL ORDER BY first_name',
            [$companyId]
        );
    }

    public function updateLastLogin(int $userId, string $ip): void
    {
        Database::update(
            'UPDATE users SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?',
            [$ip, $userId]
        );
    }

    public function activate(int $userId): void
    {
        Database::update('UPDATE users SET is_active = 1 WHERE id = ?', [$userId]);
    }

    public function deactivate(int $userId): void
    {
        Database::update('UPDATE users SET is_active = 0 WHERE id = ?', [$userId]);
    }
}

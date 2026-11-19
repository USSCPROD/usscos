<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Website customer logins: the people, their sessions, password resets and throttling.
 * See database/migrations/124_customer_accounts.sql for why these are not staff users.
 */
class CustomerLoginRepository
{
    /** What a signed-in login carries: the person, and the company they act for. */
    private const WITH_CUSTOMER = "
        SELECT l.*, c.company_name AS customer_name, c.customer_type AS customer_type,
               c.parent_id AS customer_parent_id, pc.company_name AS parent_name,
               pc.customer_type AS parent_type, c.is_active AS customer_active
        FROM customer_logins l
        LEFT JOIN customers c  ON c.id = l.customer_id
        LEFT JOIN customers pc ON pc.id = c.parent_id";

    public function find(int $id): array|false
    {
        return Database::selectOne(self::WITH_CUSTOMER . " WHERE l.id = ?", [$id]);
    }

    public function findByEmail(string $email): array|false
    {
        return Database::selectOne("SELECT * FROM customer_logins WHERE email = ?", [$email]);
    }

    public function create(array $d): int
    {
        Database::query("
            INSERT INTO customer_logins
                (email, password_hash, legacy_salt, legacy_hash, legacy_iterations, name, phone, organization,
                 buyer_type, asked_trade, source, shop_role, signup_ip, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, COALESCE(?, CURRENT_TIMESTAMP))",
            [$d['email'], $d['password_hash'] ?? null, $d['legacy_salt'] ?? null, $d['legacy_hash'] ?? null,
             $d['legacy_iterations'] ?? null, $d['name'], $d['phone'] ?? '', $d['organization'] ?? '',
             $d['buyer_type'] ?? '', (int)($d['asked_trade'] ?? 0), $d['source'] ?? 'website', $d['shop_role'] ?? null,
             $d['signup_ip'] ?? null, $d['created_at'] ?? null]);

        return (int)Database::connection()->lastInsertId();
    }

    /** A new password, which also retires any hash carried over from the old shop. */
    public function setPassword(int $id, string $hash): void
    {
        Database::query("UPDATE customer_logins SET password_hash = ?, legacy_salt = NULL, legacy_hash = NULL,
                         legacy_iterations = NULL WHERE id = ?", [$hash, $id]);
    }

    public function touch(int $id): void
    {
        Database::query("UPDATE customer_logins SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?", [$id]);
    }

    // ── Sessions ──────────────────────────────────────────────────────────────

    public function createSession(string $tokenHash, int $loginId, int $seconds): void
    {
        Database::query("DELETE FROM customer_login_sessions WHERE expires_at < NOW()");
        Database::query("INSERT INTO customer_login_sessions (token_hash, login_id, expires_at)
                         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))", [$tokenHash, $loginId, $seconds]);
    }

    public function loginForSession(string $tokenHash): array|false
    {
        return Database::selectOne(self::WITH_CUSTOMER . "
            JOIN customer_login_sessions s ON s.login_id = l.id
            WHERE s.token_hash = ? AND s.expires_at > NOW()", [$tokenHash]);
    }

    public function deleteSession(string $tokenHash): void
    {
        Database::query("DELETE FROM customer_login_sessions WHERE token_hash = ?", [$tokenHash]);
    }

    /** Every session of a login but one: after a password change, other devices sign out. */
    public function deleteOtherSessions(int $loginId, string $keepHash = ''): void
    {
        Database::query("DELETE FROM customer_login_sessions WHERE login_id = ? AND token_hash <> ?", [$loginId, $keepHash]);
    }

    // ── Password resets ───────────────────────────────────────────────────────

    public function createReset(string $tokenHash, int $loginId, int $seconds): void
    {
        Database::query("DELETE FROM customer_password_resets WHERE login_id = ? OR expires_at < NOW()", [$loginId]);
        Database::query("INSERT INTO customer_password_resets (token_hash, login_id, expires_at)
                         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))", [$tokenHash, $loginId, $seconds]);
    }

    /** The login a reset link is for, or false; the link works once. */
    public function takeReset(string $tokenHash): int|false
    {
        $row = Database::selectOne("SELECT login_id FROM customer_password_resets WHERE token_hash = ? AND expires_at > NOW()", [$tokenHash]);
        Database::query("DELETE FROM customer_password_resets WHERE token_hash = ?", [$tokenHash]);

        return $row ? (int)$row['login_id'] : false;
    }

    public function resetIsValid(string $tokenHash): bool
    {
        return (bool)Database::selectOne("SELECT 1 FROM customer_password_resets WHERE token_hash = ? AND expires_at > NOW()", [$tokenHash]);
    }

    // ── Throttling ────────────────────────────────────────────────────────────

    public function recordAttempt(string $kind, string $ip, string $email = ''): void
    {
        Database::query("INSERT INTO customer_login_attempts (kind, ip, email) VALUES (?, ?, ?)", [$kind, $ip, strtolower($email)]);
    }

    public function attemptsByIp(string $kind, string $ip, int $minutes): int
    {
        return (int)Database::selectOne("SELECT COUNT(*) n FROM customer_login_attempts
            WHERE kind = ? AND ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)", [$kind, $ip, $minutes])['n'];
    }

    public function attemptsByEmail(string $kind, string $email, int $minutes): int
    {
        return (int)Database::selectOne("SELECT COUNT(*) n FROM customer_login_attempts
            WHERE kind = ? AND email = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)", [$kind, strtolower($email), $minutes])['n'];
    }

    public function clearFailures(string $ip, string $email): void
    {
        Database::query("DELETE FROM customer_login_attempts WHERE kind = 'fail' AND (ip = ? OR email = ?)", [$ip, strtolower($email)]);
        Database::query("DELETE FROM customer_login_attempts WHERE created_at < DATE_SUB(NOW(), INTERVAL 2 DAY)");
    }

    // ── Staff ─────────────────────────────────────────────────────────────────

    /** @param string $filter 'unlinked' | 'linked' | 'blocked' | 'all' */
    public function listForStaff(string $filter, string $search = '', int $limit = 200): array
    {
        $where = match ($filter) {
            'unlinked' => "l.customer_id IS NULL AND l.status = 'active'",
            'linked'   => "l.customer_id IS NOT NULL",
            'blocked'  => "l.status = 'blocked'",
            default    => "1 = 1",
        };
        $params = [];

        if ($search !== '') {
            $where .= " AND (l.email LIKE ? OR l.name LIKE ? OR l.organization LIKE ? OR c.company_name LIKE ?)";
            $like = '%' . $search . '%';
            $params = [$like, $like, $like, $like];
        }

        $params[] = $limit;

        return Database::select(self::WITH_CUSTOMER . ", (SELECT u.first_name FROM users u WHERE u.id = l.linked_by) AS linked_by_name
            WHERE {$where} ORDER BY l.asked_trade DESC, l.created_at DESC LIMIT ?", $params);
    }

    public function counts(): array
    {
        return Database::selectOne("SELECT SUM(customer_id IS NULL AND status = 'active') unlinked,
                                           SUM(customer_id IS NOT NULL) linked, SUM(status = 'blocked') blocked, COUNT(*) total
                                    FROM customer_logins") ?: [];
    }

    public function link(int $id, int $customerId, int $userId): void
    {
        Database::query("UPDATE customer_logins SET customer_id = ?, linked_by = ?, linked_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$customerId, $userId, $id]);
    }

    public function unlink(int $id): void
    {
        Database::query("UPDATE customer_logins SET customer_id = NULL, linked_by = NULL, linked_at = NULL WHERE id = ?", [$id]);
    }

    public function setStatus(int $id, string $status, ?string $note): void
    {
        Database::query("UPDATE customer_logins SET status = ?, blocked_note = ? WHERE id = ?",
            [$status === 'blocked' ? 'blocked' : 'active', $status === 'blocked' ? $note : null, $id]);

        if ($status === 'blocked') {
            Database::query("DELETE FROM customer_login_sessions WHERE login_id = ?", [$id]);
        }
    }

    /** The price tier of a customer, which staff may set while linking. */
    public function setCustomerType(int $customerId, string $type): void
    {
        Database::query("UPDATE customers SET customer_type = ? WHERE id = ?", [$type, $customerId]);
    }

    public function customerSummary(int $customerId): array|false
    {
        return Database::selectOne("SELECT c.id, c.company_name, c.customer_type, c.email, c.parent_id, p.company_name parent_name,
                                           (SELECT COUNT(*) FROM customers k WHERE k.parent_id = c.id) sub_count
                                    FROM customers c LEFT JOIN customers p ON p.id = c.parent_id WHERE c.id = ?", [$customerId]);
    }
}

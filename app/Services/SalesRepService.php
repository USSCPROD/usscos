<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Database;
use App\Repositories\AdminRepository;

/**
 * Saving a sales rep, including the login that goes with them.
 *
 * A rep and a user are separate records: most reps are outside or former reps with no
 * account at all, and the rep row carries QuickBooks history that must survive whether or
 * not anyone can log in as them. This service is the one place that keeps the two in step.
 *
 * Credentials are entered directly on the rep form rather than picked from a list of
 * existing users, so creating a rep and giving them access is a single action.
 */
class SalesRepService
{
    public function __construct(
        private AdminRepository $repo = new AdminRepository(),
    ) {
    }

    /**
     * Create or update a rep, and create, update or leave alone their login.
     *
     * @param  array    $data  the submitted form
     * @param  int|null $repId null when creating
     * @return int             the rep id
     *
     * @throws \RuntimeException with a message meant for the user
     */
    public function save(array $data, ?int $repId = null): int
    {
        $name = trim((string)($data['name'] ?? ''));

        if ($name === '') {
            throw new \RuntimeException('A rep needs a name.');
        }

        $existing = $repId !== null ? $this->repo->findSalesRep($repId) : null;
        $userId   = $existing['user_id'] ?? null;

        // Resolve the login first: if it fails we don't want a half-saved rep.
        $userId = $this->resolveLogin($data, $userId !== null ? (int)$userId : null, $name);

        $data['user_id'] = $userId ?? '';

        if ($repId === null) {
            return $this->repo->insertSalesRep($data);
        }

        $this->repo->updateSalesRep($repId, $data);

        return $repId;
    }

    /**
     * Work out what should happen to this rep's login, and do it.
     *
     * - no email given            leave any existing link alone, change nothing
     * - email, no current link    reuse an account with that email, or create one
     * - email, already linked     update that account, and its password if one was given
     *
     * Deliberately never deletes or deactivates a user: that belongs on the Users page,
     * where the consequences are spelled out.
     *
     * @return int|null the linked user id, or null if this rep has no login
     */
    private function resolveLogin(array $data, ?int $currentUserId, string $repName): ?int
    {
        $email    = strtolower(trim((string)($data['login_email'] ?? '')));
        $password = (string)($data['login_password'] ?? '');
        $minLen   = (int)Config::get('auth.password.min_length', 8);

        if ($email === '') {
            // Nothing to do. An existing link stays as it is.
            return $currentUserId;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('That login email doesn\'t look like a valid address.');
        }

        if ($password !== '' && strlen($password) < $minLen) {
            throw new \RuntimeException("The password must be at least {$minLen} characters.");
        }

        $byEmail = Database::selectOne(
            'SELECT id FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1',
            [$email]
        );
        $byEmail = $byEmail === false ? null : $byEmail;

        // Email already belongs to a different account — refuse rather than silently
        // moving the rep onto someone else's login.
        if ($byEmail !== null && $currentUserId !== null && (int)$byEmail['id'] !== $currentUserId) {
            throw new \RuntimeException(
                'That email is already used by another user. Pick a different address, or clear it to keep the current login.'
            );
        }

        [$first, $last] = $this->splitName($repName);
        $role = $this->roleFor((string)($data['rep_type'] ?? 'person'));

        // Existing link, or an existing account with this email: update it.
        $targetId = $currentUserId ?? ($byEmail !== null ? (int)$byEmail['id'] : null);

        if ($targetId !== null) {
            $sql    = 'UPDATE users SET email = ?';
            $params = [$email];

            if ($password !== '') {
                $sql     .= ', password = ?';
                $params[] = Auth::hashPassword($password);
            }

            $sql     .= ' WHERE id = ?';
            $params[] = $targetId;

            Database::statement($sql, $params);

            return $targetId;
        }

        // No account yet — one is being created, so a password is required.
        if ($password === '') {
            throw new \RuntimeException(
                "Set a password of at least {$minLen} characters to create this login, or clear the email to save the rep without one."
            );
        }

        Database::statement(
            'INSERT INTO users (company_id, first_name, last_name, email, password, role, is_active)
             VALUES (1, ?, ?, ?, ?, ?, ?)',
            [$first, $last, $email, Auth::hashPassword($password), $role, (int)(bool)($data['is_active'] ?? 1)]
        );

        $created = Database::selectOne('SELECT id FROM users WHERE email = ? LIMIT 1', [$email]);

        return $created === false ? null : (int)$created['id'];
    }

    /** Reps and distributors get the restricted roles; everyone else is internal. */
    private function roleFor(string $repType): string
    {
        return match ($repType) {
            'person'  => 'rep',
            'partner' => 'distributor',
            default   => 'employee',
        };
    }

    /** "Larry Fitzpatrick" -> ["Larry", "Fitzpatrick"]. Single names get a blank surname. */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $first = array_shift($parts) ?? $name;

        return [$first, implode(' ', $parts)];
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Web;

use App\Core\Mailer;
use App\Core\Request;
use App\Repositories\CustomerLoginRepository;
use App\Services\CustomerPricing;

/**
 * Website customer accounts: sign-up, sign-in, password resets, and what a signed-in
 * person may do. The first authenticated surface open to the internet, so:
 *
 *  - Customers are not staff users and never share their session. Their own cookie,
 *    their own table, a hash of the token stored rather than the token.
 *  - Which company a login acts for is set by staff, never inferred from an email.
 *    Unlinked, a login buys at retail and sees only its own orders.
 *  - Failures are throttled by IP and by email, and nothing says whether an email has
 *    an account except signing up with it (which the shop did too).
 *  - Every state-changing POST must come from this site (sameOrigin), on top of the
 *    cookie being SameSite=Lax.
 */
class CustomerAccounts
{
    public const COOKIE          = 'ussc_account';
    public const SESSION_SECONDS = 30 * 24 * 3600;
    public const RESET_SECONDS   = 3600;
    public const MIN_PASSWORD    = 8;

    public const BUYER_TYPES = [
        'school'      => 'School / athletic department',
        'contractor'  => 'Contractor / field painter',
        'club'        => 'Club, league or team',
        'distributor' => 'Distributor or reseller',
        'other'       => 'Other',
    ];

    private const FAILS_PER_IP     = 20;   // per 15 minutes
    private const FAILS_PER_EMAIL  = 8;    // per 15 minutes
    private const SIGNUPS_PER_IP   = 10;   // per day
    private const RESETS_PER_IP    = 10;   // per hour
    private const RESETS_PER_EMAIL = 3;    // per hour

    private static array|false|null $current = null;

    public function __construct(private CustomerLoginRepository $logins = new CustomerLoginRepository())
    {
    }

    // ── Who is signed in ──────────────────────────────────────────────────────

    /** The signed-in login, with its linked customer, or null. Cached for the request. */
    public static function current(): ?array
    {
        if (self::$current === null) {
            $token = (string)($_COOKIE[self::COOKIE] ?? '');
            self::$current = preg_match('/^[a-f0-9]{64}$/', $token)
                ? (new CustomerLoginRepository())->loginForSession(hash('sha256', $token)) : false;

            if (self::$current !== false && self::$current['status'] !== 'active') {
                self::$current = false;
            }
        }

        return self::$current ?: null;
    }

    /**
     * The price tier a login buys at: the linked customer's type, or its parent's when the
     * store itself has none. Retail when not linked, or when the company is inactive.
     */
    public static function tier(?array $login): string
    {
        if (!$login || empty($login['customer_id']) || (int)($login['customer_active'] ?? 0) !== 1) {
            return CustomerPricing::RETAIL;
        }

        $type = trim((string)($login['customer_type'] ?? '')) ?: trim((string)($login['parent_type'] ?? ''));

        return in_array($type, [CustomerPricing::DIST, CustomerPricing::STOCKING], true) ? $type : CustomerPricing::RETAIL;
    }

    /** Distributors see wholesale prices, save quotes, and order on terms. */
    public static function isTrade(?array $login): bool
    {
        return self::tier($login) !== CustomerPricing::RETAIL;
    }

    /** The company a login acts for, for showing it: its own name, under its parent's. */
    public static function companyLabel(array $login): string
    {
        if (empty($login['customer_id'])) {
            return '';
        }

        return trim((string)$login['customer_name']) . (!empty($login['parent_name']) ? ' (' . $login['parent_name'] . ')' : '');
    }

    /** A POST that changes something must come from a page on this site. */
    public static function sameOrigin(Request $request): bool
    {
        $site   = 'https://' . strtolower($request->host());
        $origin = strtolower((string)($request->header('Origin') ?? ''));

        if ($origin !== '' && $origin !== 'null') {
            return $origin === $site;
        }

        $referer = strtolower((string)($request->header('Referer') ?? ''));

        return $referer === $site || str_starts_with($referer, $site . '/');
    }

    // ── Signing up and in ─────────────────────────────────────────────────────

    /** @throws AccountError */
    public function signUp(array $in, string $ip): void
    {
        $email    = self::email((string)($in['email'] ?? ''));
        $password = (string)($in['password'] ?? '');
        $name     = self::line((string)($in['name'] ?? ''), 100);
        $phone    = self::line((string)($in['phone'] ?? ''), 40);
        $org      = self::line((string)($in['organization'] ?? ''), 150);
        $buyer    = (string)($in['buyer_type'] ?? '');

        if (mb_strlen($name) < 2) {
            throw new AccountError('Please enter your name.');
        }
        if (strlen(preg_replace('/\D/', '', $phone)) < 10) {
            throw new AccountError('Please enter a phone number with area code.');
        }
        if (mb_strlen($org) < 2) {
            throw new AccountError('Please enter your school, team or company.');
        }
        if (!isset(self::BUYER_TYPES[$buyer])) {
            throw new AccountError('Please choose what best describes you.');
        }
        self::checkPassword($password);

        if ($this->logins->attemptsByIp('signup', $ip, 24 * 60) >= self::SIGNUPS_PER_IP) {
            throw new AccountError('Too many new accounts from this connection today. Please call us.', 429);
        }
        if ($this->logins->findByEmail($email)) {
            throw new AccountError('There is already an account with that email. Please sign in, or reset your password.', 409);
        }

        $this->logins->recordAttempt('signup', $ip, $email);
        $id = $this->logins->create([
            'email' => $email, 'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'name' => $name,
            'phone' => $phone, 'organization' => $org, 'buyer_type' => $buyer,
            'asked_trade' => $buyer === 'distributor' ? 1 : 0, 'signup_ip' => $ip,
        ]);

        $this->startSession($id);
        $this->notifyStaff($id);
    }

    /** @throws AccountError */
    public function signIn(string $email, string $password, string $ip): void
    {
        $email = self::email($email);

        if ($this->logins->attemptsByIp('fail', $ip, 15) >= self::FAILS_PER_IP
            || $this->logins->attemptsByEmail('fail', $email, 15) >= self::FAILS_PER_EMAIL) {
            throw new AccountError('Too many attempts. Please try again in 15 minutes, or reset your password.', 429);
        }

        $login = $this->logins->findByEmail($email);

        if (!$login || !$this->passwordMatches($login, $password)) {
            $this->logins->recordAttempt('fail', $ip, $email);
            usleep(500_000);
            throw new AccountError("That email and password don't match.", 401);
        }

        if ($login['status'] !== 'active') {
            throw new AccountError('This account is paused. Please call us and we will sort it out.', 403);
        }

        // A password carried over from the old shop becomes a modern hash on first use.
        if ($login['password_hash'] === null || password_needs_rehash((string)$login['password_hash'], PASSWORD_DEFAULT)) {
            $this->logins->setPassword((int)$login['id'], password_hash($password, PASSWORD_DEFAULT));
        }

        $this->logins->clearFailures($ip, $email);
        $this->logins->touch((int)$login['id']);
        $this->startSession((int)$login['id']);
    }

    public function signOut(): void
    {
        $token = (string)($_COOKIE[self::COOKIE] ?? '');

        if ($token !== '') {
            $this->logins->deleteSession(hash('sha256', $token));
        }

        self::setCookie('', -3600);
        self::$current = false;
    }

    /** @throws AccountError */
    public function changePassword(array $login, string $current, string $new): void
    {
        $row = $this->logins->findByEmail((string)$login['email']);

        if (!$row || !$this->passwordMatches($row, $current)) {
            throw new AccountError('Your current password is not right.');
        }

        self::checkPassword($new);
        $this->logins->setPassword((int)$login['id'], password_hash($new, PASSWORD_DEFAULT));
        $this->logins->deleteOtherSessions((int)$login['id'], hash('sha256', (string)($_COOKIE[self::COOKIE] ?? '')));
    }

    // ── Forgotten passwords ───────────────────────────────────────────────────

    /**
     * Emails a one-time link when the address has an account. The page says the same thing
     * either way, so this form cannot be used to find out who has an account.
     *
     * @throws AccountError
     */
    public function requestReset(string $email, string $ip, string $host): void
    {
        $email = self::email($email);

        if ($this->logins->attemptsByIp('reset', $ip, 60) >= self::RESETS_PER_IP
            || $this->logins->attemptsByEmail('reset', $email, 60) >= self::RESETS_PER_EMAIL) {
            throw new AccountError("We've already sent a few reset emails. Please check your inbox and spam folder, or try again in an hour.", 429);
        }

        $this->logins->recordAttempt('reset', $ip, $email);
        $login = $this->logins->findByEmail($email);

        if (!$login || $login['status'] !== 'active') {
            return;
        }

        $token = bin2hex(random_bytes(32));
        $this->logins->createReset(hash('sha256', $token), (int)$login['id'], self::RESET_SECONDS);
        $link  = 'https://' . $host . '/account/reset?t=' . $token;
        $first = explode(' ', trim((string)$login['name']))[0] ?? '';

        $ok = (new Mailer())->send($email, 'Reset your USSC Products password', self::resetEmail($first, $link));

        if (!$ok) {
            error_log('customer password reset email failed for login ' . $login['id']);
        }
    }

    public function resetIsValid(string $token): bool
    {
        return preg_match('/^[a-f0-9]{64}$/', $token) && $this->logins->resetIsValid(hash('sha256', $token));
    }

    /** @throws AccountError */
    public function finishReset(string $token, string $new): void
    {
        self::checkPassword($new);
        $id = preg_match('/^[a-f0-9]{64}$/', $token) ? $this->logins->takeReset(hash('sha256', $token)) : false;

        if ($id === false) {
            throw new AccountError('This reset link has expired or was already used. Please ask for a new one.');
        }

        $this->logins->setPassword($id, password_hash($new, PASSWORD_DEFAULT));
        $this->logins->deleteOtherSessions($id);
        $this->startSession($id);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function passwordMatches(array $login, string $password): bool
    {
        if (!empty($login['password_hash'])) {
            return password_verify($password, (string)$login['password_hash']);
        }

        // The old stencil shop: PBKDF2-HMAC-SHA256, 32-byte key, hex.
        if (!empty($login['legacy_hash']) && !empty($login['legacy_salt']) && ctype_xdigit((string)$login['legacy_salt'])) {
            $calc = hash_pbkdf2('sha256', $password, (string)hex2bin((string)$login['legacy_salt']),
                                (int)$login['legacy_iterations'], 64);

            return hash_equals((string)$login['legacy_hash'], $calc);
        }

        return false;
    }

    private function startSession(int $loginId): void
    {
        $token = bin2hex(random_bytes(32));
        $this->logins->createSession(hash('sha256', $token), $loginId, self::SESSION_SECONDS);
        self::setCookie($token, self::SESSION_SECONDS);
        self::$current = null;
        $_COOKIE[self::COOKIE] = $token;
    }

    private static function setCookie(string $value, int $seconds): void
    {
        setcookie(self::COOKIE, $value, [
            'expires' => time() + $seconds, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax',
        ]);

        // A hint for the page script, which cannot read the session cookie: it only picks
        // "My account" over "Sign in" in the header. It grants nothing.
        setcookie('ussc_signed_in', $value !== '' ? '1' : '', [
            'expires' => time() + $seconds, 'path' => '/', 'secure' => true, 'httponly' => false, 'samesite' => 'Lax',
        ]);
    }

    /** Staff hear about each new sign-up, so linking it to its customer is not forgotten. */
    private function notifyStaff(int $id): void
    {
        $l = $this->logins->find($id);

        if (!$l) {
            return;
        }

        $intake = new \App\Services\WebsiteLeadService();
        $intake->notify($intake->routing()['stencil_email'],
            'New website account' . ($l['asked_trade'] ? ' (says distributor)' : '') . ': ' . $l['organization'],
            '<table style="width:100%;border-collapse:collapse">'
            . $intake->row('Name', (string)$l['name']) . $intake->row('Email', (string)$l['email'])
            . $intake->row('Phone', (string)$l['phone']) . $intake->row('School / company', (string)$l['organization'])
            . $intake->row('Describes themselves as', self::BUYER_TYPES[$l['buyer_type']] ?? (string)$l['buyer_type'])
            . '</table><p>Until it is linked to a customer in USSCOS (Customers → Website accounts), this account buys at retail.</p>');
    }

    /** @throws AccountError */
    private static function email(string $email): string
    {
        $email = strtolower(trim($email));

        if (strlen($email) > 200 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new AccountError('Please enter a valid email address.');
        }

        return $email;
    }

    /** @throws AccountError */
    private static function checkPassword(string $password): void
    {
        if (strlen($password) < self::MIN_PASSWORD) {
            throw new AccountError('Please use a password of at least ' . self::MIN_PASSWORD . ' characters.');
        }
        if (strlen($password) > 200) {
            throw new AccountError('That password is too long.');
        }
    }

    private static function line(string $s, int $max): string
    {
        return mb_substr(trim((string)preg_replace('/\s+/', ' ', $s)), 0, $max);
    }

    private static function resetEmail(string $first, string $link): string
    {
        $h = static fn($s) => htmlspecialchars($s, ENT_QUOTES);

        return '<div style="font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.5;color:#111827;max-width:520px">'
            . '<p>' . ($first !== '' ? 'Hi ' . $h($first) . ',' : 'Hello,') . '</p>'
            . '<p>We got a request to reset the password for your USSC Products account. Click the button to choose a new password. The link works once, for the next hour.</p>'
            . '<p style="margin:24px 0"><a href="' . $h($link) . '" style="background:#222b59;color:#fff;text-decoration:none;font-weight:bold;padding:12px 22px;border-radius:6px;display:inline-block">Choose a new password</a></p>'
            . '<p style="font-size:13px;color:#6b7280">Or copy this link into your browser:<br><a href="' . $h($link) . '" style="color:#0A3D91;word-break:break-all">' . $h($link) . '</a></p>'
            . '<p style="font-size:13px;color:#6b7280">If you didn\'t ask for this, you can ignore this email. Your password won\'t change.</p>'
            . '<p style="font-size:13px;color:#6b7280">US Specialty Coatings · 1-800-278-7473</p></div>';
    }
}

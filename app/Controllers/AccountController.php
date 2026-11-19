<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\SavedWorkRepository;
use App\Services\Stencils\DesignerError;
use App\Services\Web\AccountError;
use App\Services\Web\CartService;
use App\Services\Web\CustomerAccounts;

/**
 * The customer's account on the website: sign-up, sign-in, passwords, saved designs and
 * saved quotes. Public routes (no staff authentication); each action that needs a
 * customer checks for one itself, and every POST must come from this site.
 *
 * Input is read with raw(), not post(): Request escapes HTML in post(), which would turn a
 * password containing & into a different password.
 */
class AccountController extends Controller
{
    private CustomerAccounts $accounts;
    private SavedWorkRepository $work;

    public function __construct()
    {
        parent::__construct();
        $this->accounts = new CustomerAccounts();
        $this->work     = new SavedWorkRepository();
    }

    // ── Signing in and out ───────────────────────────────────────────────────

    public function signInPage(Request $request, Response $response): Response
    {
        if (CustomerAccounts::current()) {
            return $response->redirect(self::next($request));
        }

        return $this->page('signin', 'Sign in', $request);
    }

    public function signIn(Request $request, Response $response): Response
    {
        if (!CustomerAccounts::sameOrigin($request)) {
            return $this->page('signin', 'Sign in', $request, 'Please try again.');
        }

        $email = (string)$request->raw('email', '');
        $password = (string)$request->raw('password', '');

        try {
            $this->accounts->signIn($email, $password, $request->ip());
        } catch (AccountError $e) {
            // Not a customer: staff may sign in here with their USSCOS login, which opens
            // the designers for them. It signs them in to USSCOS itself, exactly as /login
            // does; it is not a customer account and carries no prices or saved work.
            if ($e->status() === 401 && Auth::attempt($email, $password)) {
                (new \App\Repositories\CustomerLoginRepository())->clearFailures($request->ip(), strtolower(trim($email)));
                return $response->redirect(self::next($request, '/custom'));
            }

            return $this->page('signin', 'Sign in', $request, $e->getMessage(), $e->status());
        }

        return $response->redirect(self::next($request));
    }

    public function signUpPage(Request $request, Response $response): Response
    {
        if (CustomerAccounts::current()) {
            return $response->redirect(self::next($request));
        }

        return $this->page('signup', 'Create an account', $request);
    }

    public function signUp(Request $request, Response $response): Response
    {
        if (!CustomerAccounts::sameOrigin($request)) {
            return $this->page('signup', 'Create an account', $request, 'Please try again.');
        }

        // A filled honeypot is a bot: show the page again, as if nothing happened.
        if (trim((string)$request->raw('website', '')) !== '') {
            return $this->page('signup', 'Create an account', $request);
        }

        try {
            $this->accounts->signUp([
                'email' => $request->raw('email', ''), 'password' => $request->raw('password', ''),
                'name' => $request->raw('name', ''), 'phone' => $request->raw('phone', ''),
                'organization' => $request->raw('organization', ''), 'buyer_type' => $request->raw('buyer_type', ''),
            ], $request->ip());
        } catch (AccountError $e) {
            return $this->page('signup', 'Create an account', $request, $e->getMessage(), $e->status());
        }

        return $response->redirect(self::next($request, '/account?welcome=1'));
    }

    public function signOut(Request $request, Response $response): Response
    {
        if (CustomerAccounts::sameOrigin($request)) {
            $this->accounts->signOut();
        }

        return $response->redirect('/account/sign-in?out=1');
    }

    // ── Forgotten passwords ──────────────────────────────────────────────────

    public function forgotPage(Request $request, Response $response): Response
    {
        return $this->page('forgot', 'Reset your password', $request);
    }

    public function forgot(Request $request, Response $response): Response
    {
        if (!CustomerAccounts::sameOrigin($request)) {
            return $this->page('forgot', 'Reset your password', $request, 'Please try again.');
        }

        try {
            $this->accounts->requestReset((string)$request->raw('email', ''), $request->ip(), $request->host());
        } catch (AccountError $e) {
            return $this->page('forgot', 'Reset your password', $request, $e->getMessage(), $e->status());
        }

        return $this->page('forgot_sent', 'Check your email', $request);
    }

    public function resetPage(Request $request, Response $response): Response
    {
        $token = (string)$request->raw('t', '');

        return $this->accounts->resetIsValid($token)
            ? $this->page('reset', 'Choose a new password', $request, '', 200, ['token' => $token])
            : $this->page('reset_expired', 'This link has expired', $request);
    }

    public function reset(Request $request, Response $response): Response
    {
        $token = (string)$request->raw('t', '');

        if (!CustomerAccounts::sameOrigin($request)) {
            return $this->page('reset', 'Choose a new password', $request, 'Please try again.', 200, ['token' => $token]);
        }

        try {
            $this->accounts->finishReset($token, (string)$request->raw('password', ''));
        } catch (AccountError $e) {
            return $this->page('reset', 'Choose a new password', $request, $e->getMessage(), 422, ['token' => $token]);
        }

        return $response->redirect('/account?reset=1');
    }

    // ── The account page ─────────────────────────────────────────────────────

    public function show(Request $request, Response $response): Response
    {
        $login = CustomerAccounts::current();

        if (!$login) {
            return $response->redirect('/account/sign-in?next=' . rawurlencode('/account'));
        }

        return $this->view('site.account', [
            'title'   => 'My Account',
            'nav'     => (new SiteController())->navForDesigner(),
            'login'   => $login,
            'tier'    => CustomerAccounts::tier($login),
            'trade'   => CustomerAccounts::isTrade($login),
            'company' => CustomerAccounts::companyLabel($login),
            'designs' => $this->work->designs((int)$login['id']),
            'quotes'  => CustomerAccounts::isTrade($login) || $this->work->countQuotes((int)$login['id']) > 0
                         ? $this->work->quotes((int)$login['id']) : [],
            'notice'  => match (true) {
                $request->raw('welcome') !== null => 'Welcome! Your account is ready.',
                $request->raw('reset') !== null   => 'Your password is changed, and you are signed in.',
                $request->raw('pw') !== null      => 'Your password is changed. Other devices have been signed out.',
                default => '',
            },
            'error'   => (string)($request->raw('error') ?? ''),
        ]);
    }

    public function password(Request $request, Response $response): Response
    {
        $login = CustomerAccounts::current();

        if (!$login || !CustomerAccounts::sameOrigin($request)) {
            return $response->redirect('/account/sign-in');
        }

        try {
            $this->accounts->changePassword($login, (string)$request->raw('current', ''), (string)$request->raw('password', ''));
        } catch (AccountError $e) {
            return $response->redirect('/account?error=' . rawurlencode($e->getMessage()) . '#password');
        }

        return $response->redirect('/account?pw=1');
    }

    // ── Saved designs ────────────────────────────────────────────────────────

    /** {id?, kind, name, data} → {id}. The design is priced first, so only a real design saves. */
    public function saveDesign(Request $request, Response $response): Response
    {
        $login = $this->jsonLogin($request);

        if ($login === null) {
            return $response->json(['error' => 'Please sign in to save designs.'], 401);
        }

        $in   = $this->body();
        $kind = (string)($in['kind'] ?? '');
        $data = is_array($in['data'] ?? null) ? $in['data'] : [];
        $name = mb_substr(trim((string)preg_replace('/\s+/', ' ', (string)($in['name'] ?? ''))), 0, 120) ?: 'My design';
        $id   = (int)($in['id'] ?? 0);

        if (!in_array($kind, ['text', 'endzone', 'midfield'], true)) {
            return $response->json(['error' => 'That design cannot be saved.'], 422);
        }

        $keys = ['text' => ['text', 'size', 'thickness'], 'endzone' => ['text', 'size', 'style', 'format', 'fill', 'line'],
                 'midfield' => ['logo', 'field', 'width']][$kind];
        $data = ['kind' => $kind] + array_map(static fn($v) => mb_substr((string)$v, 0, 200), array_intersect_key($data, array_flip($keys)));

        try {
            $line = (new CartService())->price([$data + ['qty' => 1]])['lines'][0];
        } catch (DesignerError $e) {
            return $response->json(['error' => $e->getMessage()], 422);
        }

        $loginId = (int)$login['id'];

        if ($id > 0 && $this->work->design($loginId, $id)) {
            $this->work->updateDesign($loginId, $id, $name, $data, mb_substr($line['name'], 0, 255), (float)$line['unit']);
        } else {
            if ($this->work->countDesigns($loginId) >= SavedWorkRepository::MAX_DESIGNS) {
                return $response->json(['error' => 'You have saved the most designs an account can hold. Please delete some first.'], 422);
            }
            $id = $this->work->createDesign($loginId, $kind, $name, $data, mb_substr($line['name'], 0, 255), (float)$line['unit']);
        }

        return $response->json(['id' => $id]);
    }

    public function deleteDesign(Request $request, Response $response, string $id): Response
    {
        $login = CustomerAccounts::current();

        if ($login && CustomerAccounts::sameOrigin($request)) {
            $this->work->deleteDesign((int)$login['id'], (int)$id);
        }

        return $response->redirect('/account#designs');
    }

    // ── Saved quotes ─────────────────────────────────────────────────────────

    /** {name, items} → {id, url}. For distributor accounts, as on the old shop. */
    public function saveQuote(Request $request, Response $response): Response
    {
        $login = $this->jsonLogin($request);

        if ($login === null || !CustomerAccounts::isTrade($login)) {
            return $response->json(['error' => 'Saved quotes are for distributor accounts.'], 403);
        }

        $in   = $this->body();
        $name = mb_substr(trim((string)preg_replace('/\s+/', ' ', (string)($in['name'] ?? ''))), 0, 120) ?: 'Quote';

        try {
            $priced = (new CartService())->price(is_array($in['items'] ?? null) ? $in['items'] : [], CustomerAccounts::tier($login));
        } catch (DesignerError $e) {
            return $response->json(['error' => $e->getMessage()], 422);
        }

        if ($this->work->countQuotes((int)$login['id']) >= SavedWorkRepository::MAX_QUOTES) {
            return $response->json(['error' => 'You have saved the most quotes an account can hold. Please delete some first.'], 422);
        }

        $items = self::cleanItems($in['items']);
        $id    = $this->work->createQuote((int)$login['id'], $name, $items, (float)$priced['subtotal']);

        return $response->json(['id' => $id, 'url' => '/account/quotes/' . $id]);
    }

    /** A saved quote, priced again now: a quote never sells at an old price. */
    public function showQuote(Request $request, Response $response, string $id): Response
    {
        $login = CustomerAccounts::current();

        if (!$login) {
            return $response->redirect('/account/sign-in?next=' . rawurlencode('/account/quotes/' . (int)$id));
        }

        $quote = $this->work->quote((int)$login['id'], (int)$id);

        if (!$quote) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        $items = json_decode((string)$quote['items'], true) ?: [];
        $priced = null;
        $problem = '';

        try {
            $priced = (new CartService())->price($items, CustomerAccounts::tier($login));
        } catch (DesignerError $e) {
            $problem = $e->getMessage();
        }

        return $this->view('site.account_quote', [
            'title'   => $quote['name'],
            'nav'     => (new SiteController())->navForDesigner(),
            'quote'   => $quote,
            'items'   => $items,
            'priced'  => $priced,
            'problem' => $problem,
        ]);
    }

    public function deleteQuote(Request $request, Response $response, string $id): Response
    {
        $login = CustomerAccounts::current();

        if ($login && CustomerAccounts::sameOrigin($request)) {
            $this->work->deleteQuote((int)$login['id'], (int)$id);
        }

        return $response->redirect('/account#quotes');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function page(string $mode, string $title, Request $request, string $error = '', int $status = 200, array $extra = []): Response
    {
        return $this->view('site.account_auth', [
            'title'  => $title,
            'nav'    => (new SiteController())->navForDesigner(),
            'mode'   => $mode,
            'error'  => $error,
            'next'   => self::next($request, ''),
            'old'    => ['email' => (string)$request->raw('email', ''), 'name' => (string)$request->raw('name', ''),
                         'phone' => (string)$request->raw('phone', ''), 'organization' => (string)$request->raw('organization', ''),
                         'buyer_type' => (string)$request->raw('buyer_type', '')],
            'out'    => $request->raw('out') !== null,
            'staff'  => CustomerAccounts::current() === null && Auth::check() ? (string)(Auth::user()['first_name'] ?? 'staff') : '',
            'types'  => CustomerAccounts::BUYER_TYPES,
        ] + $extra, $status);
    }

    /** Where to go after signing in: a path on this site only, never another site. */
    private static function next(Request $request, string $default = '/account'): string
    {
        $next = (string)($request->raw('next') ?? '');

        return preg_match('~^/(?![/\\\\])[^\s]*$~', $next) && !str_starts_with($next, '/account/sign') ? $next : $default;
    }

    private function jsonLogin(Request $request): ?array
    {
        $login = CustomerAccounts::current();

        return $login && CustomerAccounts::sameOrigin($request) ? $login : null;
    }

    /** The JSON request body. Not json(): the base Controller has a json() of its own. */
    private function body(): array
    {
        $raw = (string)file_get_contents('php://input');

        return strlen($raw) > 200_000 ? [] : (json_decode($raw, true) ?: []);
    }

    /** Cart items as a quote keeps them: only the fields a cart item has. */
    private static function cleanItems(array $items): array
    {
        $keys = array_flip(['kind', 'sku', 'qty', 'text', 'size', 'thickness', 'style', 'format', 'fill', 'line', 'logo', 'field', 'width']);

        return array_values(array_map(static fn($i) => array_intersect_key((array)$i, $keys),
            array_slice(array_filter($items, 'is_array'), 0, CartService::MAX_LINES)));
    }
}

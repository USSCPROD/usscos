<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\CustomerLoginRepository;
use App\Services\AccessScope;
use App\Services\CustomerPricing;

/**
 * Staff: website customer accounts. Each sign-up is a person; staff decide which customer
 * record they act for, which turns on that customer's price tier (and, next, its order
 * history). Never automatic, because an email in USSCOS does not identify one customer.
 *
 * Linking to a parent (Sports Inc) covers its sub-stores; linking to a sub-store covers
 * only that store. Reps cannot reach this: who a stranger on the internet may act for is
 * not a rep's call.
 */
class CustomerLoginController extends Controller
{
    private const TIERS = [CustomerPricing::RETAIL, CustomerPricing::DIST, CustomerPricing::STOCKING];

    private CustomerLoginRepository $logins;

    public function __construct()
    {
        parent::__construct();
        $this->logins = new CustomerLoginRepository();
    }

    public function index(Request $request, Response $response): Response
    {
        if (AccessScope::isRestricted()) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        $filter = in_array($request->query('show'), ['unlinked', 'linked', 'blocked', 'all'], true) ? (string)$request->query('show') : 'unlinked';
        $search = trim((string)$request->raw('q', ''));

        return $this->view('website_accounts.index', [
            'title'  => 'Website accounts',
            'logins' => $this->logins->listForStaff($filter, mb_substr($search, 0, 100)),
            'counts' => $this->logins->counts(),
            'filter' => $filter,
            'search' => $search,
            'tiers'  => self::TIERS,
            'types'  => \App\Services\Web\CustomerAccounts::BUYER_TYPES,
        ]);
    }

    /** customer_id, and optionally tier: set the customer's price tier while linking. */
    public function link(Request $request, Response $response, string $id): Response
    {
        if (AccessScope::isRestricted()) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        $login    = $this->logins->find((int)$id);
        $customer = $this->logins->customerSummary((int)$request->raw('customer_id', 0));

        if (!$login || !$customer) {
            Session::flash('error', 'Choose a customer from the list to link this account to.');
            return $response->redirect('/website-accounts');
        }

        $this->logins->link((int)$id, (int)$customer['id'], (int)Auth::id());

        $tier = (string)$request->raw('tier', '');

        if (in_array($tier, self::TIERS, true) && $tier !== (string)$customer['customer_type']) {
            $this->logins->setCustomerType((int)$customer['id'], $tier);
        }

        Session::flash('success', $login['email'] . ' now orders as ' . $customer['company_name']
            . ((int)$customer['sub_count'] > 0 ? ', and sees its ' . (int)$customer['sub_count'] . ' sub-accounts' : '')
            . (in_array($tier, self::TIERS, true) ? ', at ' . $tier . ' prices.' : '.'));

        return $response->redirect('/website-accounts?show=' . rawurlencode((string)$request->raw('show', 'unlinked')));
    }

    public function unlink(Request $request, Response $response, string $id): Response
    {
        if (!AccessScope::isRestricted()) {
            $this->logins->unlink((int)$id);
            Session::flash('success', 'Unlinked. That account now buys at retail and sees only its own orders.');
        }

        return $response->redirect('/website-accounts?show=linked');
    }

    /** status: blocked | active, note */
    public function status(Request $request, Response $response, string $id): Response
    {
        if (!AccessScope::isRestricted()) {
            $status = $request->raw('status') === 'blocked' ? 'blocked' : 'active';
            $this->logins->setStatus((int)$id, $status, mb_substr(trim((string)$request->raw('note', '')), 0, 255) ?: null);
            Session::flash('success', $status === 'blocked' ? 'Account paused and signed out.' : 'Account restored.');
        }

        return $response->redirect('/website-accounts?show=' . ($request->raw('status') === 'blocked' ? 'blocked' : 'all'));
    }
}

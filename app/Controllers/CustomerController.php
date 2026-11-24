<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Session;
use App\Repositories\CustomerPriceRepository;
use App\Repositories\CustomerRepository;
use App\Services\Activity;
use App\Services\CustomerService;

class CustomerController extends Controller
{
    private CustomerService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new CustomerService();
    }

    public function index(Request $request, Response $response): Response
    {
        $page    = max(1, (int)($request->query('page') ?? 1));
        $perPage = 50;
        $search  = trim($request->query('q') ?? '');
        $filter  = $request->query('filter') ?? 'all';
        $rep     = trim((string)($request->query('rep') ?? ''));

        $data = $this->service->list($page, $perPage, $search, $filter, $rep);

        return $this->view('customers.index', [
            'title' => 'Customers',
            ...$data,
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        return $this->view('customers.form', [
            'title'      => 'Add Customer',
            'old'        => [],
            'duplicates' => [],
            'reps'       => $this->service->repOptions(),
        ]);
    }

    /**
     * Create a customer, pausing on a likely duplicate.
     *
     * The first submit reports any matches and creates nothing. Submitting again with
     * `confirm_duplicate` goes ahead — the point is to make re-adding an existing company
     * a deliberate act, not to make it impossible.
     */
    public function store(Request $request, Response $response): Response
    {
        $input = $_POST;
        $force = ($input['confirm_duplicate'] ?? '') === '1';

        try {
            $result = $this->service->create($input, $force);
        } catch (\RuntimeException $e) {
            \App\Core\Session::flash('error', $e->getMessage());

            return $this->view('customers.form', [
                'title'      => 'Add Customer',
                'old'        => $input,
                'duplicates' => [],
                'reps'       => $this->service->repOptions(),
            ]);
        }

        if ($result['id'] === null) {
            return $this->view('customers.form', [
                'title'      => 'Add Customer',
                'old'        => $input,
                'duplicates' => $result['duplicates'],
                'reps'       => $this->service->repOptions(),
            ]);
        }

        \App\Core\Session::flash('success', 'Customer created.');

        return $response->redirect('/customers/' . $result['id']);
    }

    public function show(Request $request, Response $response, string $id = '0'): Response
    {

        try {
            $data = $this->service->show((int)$id);
        } catch (\RuntimeException) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        return $this->view('customers.show', [
            'title' => $data['customer']['company_name'],
            ...$data,
        ]);
    }

    public function edit(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $data = $this->service->edit((int)$id);
        } catch (\RuntimeException) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        return $this->view('customers.edit', [
            'title' => 'Edit ' . $data['customer']['company_name'],
            ...$data,
        ]);
    }

    public function update(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $this->service->update((int)$id, $_POST);
        } catch (\RuntimeException) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        return $response->redirect('/customers/' . (int)$id);
    }

    public function autocomplete(Request $request, Response $response): Response
    {
        $term    = trim($request->query('q') ?? '');
        $results = $term ? $this->service->autocomplete($term) : [];
        return $this->json($results);
    }

    public function apiShow(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $data = $this->service->show((int)$id);
        } catch (\RuntimeException) {
            return $this->json([]);
        }
        return $this->json($data['customer']);
    }

    /**
     * GET /customers/{id}/pricing — the prices agreed with this account.
     *
     * Its own page rather than another tab on the customer record. A price list is
     * reference material somebody works through, not a thing they glance at, and the
     * customer page already carries seven tabs.
     */
    public function pricing(Request $request, Response $response, string $id = '0'): Response
    {
        $customer = (new CustomerRepository())->findWithDetails((int)$id);
        if (!$customer) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        return $this->view('customers.pricing', [
            'title'    => 'Pricing — ' . $customer['company_name'],
            'customer' => $customer,
            'prices'   => (new CustomerPriceRepository())->forCustomer((int)$id),
        ]);
    }

    /**
     * POST /customers/{id}/pricing — agree a price.
     *
     * Logged to the activity timeline. An agreed price is a money term: who set it, when,
     * and what it was before are exactly the questions asked six months later when an
     * invoice looks wrong, and the row itself only holds the current figure.
     */
    public function storePrice(Request $request, Response $response, string $id = '0'): Response
    {
        $customer = (new CustomerRepository())->findWithDetails((int)$id);
        if (!$customer) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        $productId = (int)($_POST['product_id'] ?? 0);
        $minQty    = max(1.0, (float)str_replace(',', '', (string)($_POST['min_qty'] ?? '1')));
        $price     = round((float)str_replace(',', '', (string)($_POST['unit_price'] ?? '0')), 4);
        $note      = trim((string)($_POST['note'] ?? '')) ?: null;

        $product = $productId > 0
            ? Database::selectOne('SELECT id, sku, name, price FROM products WHERE id = ?', [$productId])
            : false;

        if (!$product) {
            Session::flash('error', 'Pick a product from the list before saving a price.');
            return $response->redirect('/customers/' . (int)$id . '/pricing');
        }
        if ($price <= 0) {
            // Zero is refused rather than stored. A product priced at nothing is almost
            // always a typo, and the one time it is not, a 100% discount belongs in a
            // conversation rather than in a price list.
            Session::flash('error', 'Give a price above zero. To stop using an agreed price, remove it.');
            return $response->redirect('/customers/' . (int)$id . '/pricing');
        }

        $repo     = new CustomerPriceRepository();
        $existing = $repo->priceFor((int)$id, $productId, $minQty);
        $wasExact = $existing !== null && (float)$existing['min_qty'] === $minQty;

        $repo->upsert((int)$id, $productId, $minQty, $price, $note, Auth::id());

        Activity::log(
            'customer.price_agreed',
            ($wasExact ? 'Agreed price changed' : 'Agreed price set') . ' for ' . $product['sku']
                . ' at ' . money($price)
                . ($minQty > 1 ? ' for ' . \App\Services\CustomerPricing::qty($minQty) . '+' : '')
                . ($wasExact ? ', was ' . money((float)$existing['unit_price']) : '')
                . ' (list ' . money((float)$product['price']) . ')'
                . ($note !== null ? ' - ' . $note : '') . '.',
            ['subject_type' => 'Customer', 'subject_id' => (int)$id, 'customer_id' => (int)$id]
        );

        Session::flash('success', 'Price agreed for ' . $product['sku'] . '.');
        return $response->redirect('/customers/' . (int)$id . '/pricing');
    }

    /** POST /customers/{id}/pricing/{priceId}/delete — stop honouring an agreed price. */
    public function deletePrice(Request $request, Response $response, string $id = '0', string $priceId = '0'): Response
    {
        $repo  = new CustomerPriceRepository();
        $price = $repo->find((int)$priceId);

        if (!$price || (int)$price['customer_id'] !== (int)$id) {
            // Checked against the customer in the URL, not just the id: without it a
            // guessed price id would delete another account's terms.
            Session::flash('error', 'That price does not belong to this customer.');
            return $response->redirect('/customers/' . (int)$id . '/pricing');
        }

        $repo->delete((int)$priceId);

        Activity::log(
            'customer.price_removed',
            'Agreed price removed for ' . $price['sku'] . ' (was ' . money((float)$price['unit_price'])
                . '); this account now pays its tier price.',
            ['subject_type' => 'Customer', 'subject_id' => (int)$id, 'customer_id' => (int)$id]
        );

        Session::flash('success', 'Agreed price removed.');
        return $response->redirect('/customers/' . (int)$id . '/pricing');
    }

    public function storeNote(Request $request, Response $response, string $id = '0'): Response
    {
        $body = trim($_POST['body'] ?? '');
        $type = $_POST['note_type'] ?? 'note';

        if ($body === '') {
            Session::flash('error', 'Note cannot be empty.');
            return $response->redirect('/customers/' . (int)$id . '#notes');
        }

        $allowedTypes = ['note', 'call', 'email', 'meeting'];
        if (!in_array($type, $allowedTypes)) {
            $type = 'note';
        }

        $repo = new CustomerRepository();
        $repo->addNote((int)$id, (int)Auth::user()['id'], $type, $body);

        return $response->redirect('/customers/' . (int)$id . '#notes');
    }
}

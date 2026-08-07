<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\CustomerRepository;
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

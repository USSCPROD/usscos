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

        $data = $this->service->list($page, $perPage, $search, $filter);

        return $this->view('customers.index', [
            'title' => 'Customers',
            ...$data,
        ]);
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

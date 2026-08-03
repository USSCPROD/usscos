<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\VendorRepository;

class VendorController extends Controller
{
    private VendorRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new VendorRepository();
    }

    public function index(Request $request, Response $response): Response
    {
        $search = trim($_GET['search'] ?? '');
        $page   = max(1, (int)($_GET['page'] ?? 1));

        return $this->view('vendors.index', [
            'title'     => 'Vendors',
            'paginator' => $this->repo->paginate($page, 50, $search),
            'search'    => $search,
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        return $this->view('vendors.form', [
            'title'        => 'Add Vendor',
            'item'         => null,
            'paymentTerms' => $this->repo->getPaymentTerms(),
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        if (trim($_POST['company_name'] ?? '') === '') {
            Session::flash('error', 'Company name is required.');
            return $response->redirect('/vendors/create');
        }

        $id = $this->repo->insert($_POST);
        Session::flash('success', 'Vendor created.');
        return $response->redirect('/vendors/' . $id . '/edit');
    }

    public function edit(Request $request, Response $response, string $id = '0'): Response
    {
        $item = $this->repo->findById((int)$id);
        if (!$item) return $this->view('errors.404', ['title' => 'Not Found'], 404);

        return $this->view('vendors.form', [
            'title'        => 'Edit Vendor — ' . $item['company_name'],
            'item'         => $item,
            'paymentTerms' => $this->repo->getPaymentTerms(),
        ]);
    }

    public function update(Request $request, Response $response, string $id = '0'): Response
    {
        $item = $this->repo->findById((int)$id);
        if (!$item) return $this->view('errors.404', ['title' => 'Not Found'], 404);

        $this->repo->update((int)$id, $_POST);
        Session::flash('success', 'Vendor updated.');
        return $response->redirect('/vendors/' . (int)$id . '/edit');
    }
}

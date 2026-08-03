<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\PaymentService;

class PaymentController extends Controller
{
    private PaymentService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new PaymentService();
    }

    public function edit(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $data = $this->service->editForm((int)$id);
        } catch (\RuntimeException) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        return $this->view('payments.edit', [
            'title' => 'Edit Payment — ' . $data['payment']['company_name'],
            ...$data,
        ]);
    }

    public function update(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $data = $this->service->editForm((int)$id);
            $this->service->update((int)$id, $_POST);
        } catch (\RuntimeException) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        return $response->redirect('/customers/' . (int)$data['payment']['customer_id']);
    }

    public function create(Request $request, Response $response, string $id = '0'): Response
    {
        $preselected = (int)($request->query('invoice') ?? 0);

        try {
            $data = $this->service->createCustomerForm((int)$id, $preselected ?: null);
        } catch (\RuntimeException) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        return $this->view('payments.create', [
            'title' => 'Receive Payment — ' . $data['customer']['company_name'],
            ...$data,
        ]);
    }

    public function store(Request $request, Response $response, string $id = '0'): Response
    {
        $user = Auth::user();
        $this->service->storeCustomerPayment((int)$user['id'], (int)$id, $_POST);
        return $response->redirect('/customers/' . (int)$id);
    }
}

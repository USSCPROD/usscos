<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\ReturnService;

/** Customer returns — what came back, what can be sold again, and what we owe for it. */
class ReturnController extends Controller
{
    private ReturnService $returns;

    public function __construct()
    {
        parent::__construct();
        $this->returns = new ReturnService();
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->view('inventory.returns', [
            'title'   => 'Returns',
            'returns' => $this->returns->all(),
            'credits' => $this->returns->creditsOwed(),
            'reasons' => ReturnService::REASONS,
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        try {
            $id = $this->returns->start($_POST);

            return $response->redirect('/inventory/returns/' . $id);
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());

            return $response->redirect('/inventory/returns');
        }
    }

    public function show(Request $request, Response $response, string $id = '0'): Response
    {
        $return = $this->returns->get((int)$id);

        if ($return === null) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        return $this->view('inventory.return_show', [
            'title'      => 'Return ' . $return['return_number'],
            'return'     => $return,
            'lines'      => $this->returns->lines((int)$id),
            'locations'  => $this->returns->locations(),
            'conditions' => ReturnService::CONDITIONS,
            'invoices'   => $this->returns->invoicesFor((int)$return['customer_id']),
        ]);
    }

    public function addLine(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $this->returns->addLine((int)$id, $_POST);
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        return $response->redirect('/inventory/returns/' . (int)$id);
    }

    public function removeLine(Request $request, Response $response, string $id = '0', string $lineId = '0'): Response
    {
        try {
            $this->returns->removeLine((int)$id, (int)$lineId);
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        return $response->redirect('/inventory/returns/' . (int)$id);
    }

    public function receive(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $r = $this->returns->receive((int)$id);
            $n = fn(float $v) => rtrim(rtrim(number_format($v, 2), '0'), '.');

            Session::flash('success', $r['not_restocked'] > 0
                ? sprintf(
                    '%s back on the shelf. %s not restocked — it left inventory when it was sold and is not going back on sale, so nothing else needs to move.',
                    $n($r['restocked']),
                    $n($r['not_restocked'])
                )
                : $n($r['restocked']) . ' back on the shelf.');
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        return $response->redirect('/inventory/returns/' . (int)$id);
    }

    public function creditIssued(Request $request, Response $response, string $id = '0'): Response
    {
        $this->returns->markCreditIssued((int)$id);
        Session::flash('success', 'Marked as credited.');

        return $response->redirect('/inventory/returns/' . (int)$id);
    }

    /** Raise the credit memo in USSCOS rather than the bookkeeper doing it in QuickBooks. */
    public function raiseCredit(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $creditId = (new \App\Services\InvoiceService())
                ->createCreditFromReturn((int)$id, \App\Core\Auth::id());

            Session::flash('success', 'Credit memo raised.');

            return $response->redirect('/invoices/' . $creditId);
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());

            return $response->redirect('/inventory/returns/' . (int)$id);
        }
    }

    public function cancel(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $this->returns->cancel((int)$id);
            Session::flash('success', 'Return canceled.');
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        return $response->redirect('/inventory/returns');
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\TransferService;

/** Stock moving between buildings — loading a truck, and unloading it at the far end. */
class TransferController extends Controller
{
    private TransferService $transfers;

    public function __construct()
    {
        parent::__construct();
        $this->transfers = new TransferService();
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->view('inventory.transfers', [
            'title'     => 'Transfers',
            'transfers' => $this->transfers->all(),
            'inTransit' => $this->transfers->inTransit(),
            'locations' => $this->transfers->locations(),
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        try {
            $id = $this->transfers->start($_POST);

            return $response->redirect('/inventory/transfers/' . $id);
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());

            return $response->redirect('/inventory/transfers');
        }
    }

    public function show(Request $request, Response $response, string $id = '0'): Response
    {
        $transfer = $this->transfers->get((int)$id);

        if ($transfer === null) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        return $this->view('inventory.transfer_show', [
            'title'    => 'Transfer ' . $transfer['transfer_number'],
            'transfer' => $transfer,
            'lines'    => $this->transfers->lines((int)$id),
        ]);
    }

    /** Scan an item onto the truck, or off it at the other end. */
    public function scan(Request $request, Response $response, string $id = '0'): Response
    {
        $code = (string)$request->post('code', '');
        $qty  = (float)$request->post('qty', 1);

        try {
            $result = ($request->post('direction') === 'in')
                ? $this->transfers->receiveItem((int)$id, $code, $qty)
                : $this->transfers->addItem((int)$id, $code, $qty);

            return $response->json(['ok' => true] + $result);
        } catch (\RuntimeException $e) {
            return $response->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    public function removeLine(Request $request, Response $response, string $id = '0', string $lineId = '0'): Response
    {
        try {
            $this->transfers->removeItem((int)$id, (int)$lineId);
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        return $response->redirect('/inventory/transfers/' . (int)$id);
    }

    public function send(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $this->transfers->send((int)$id);
            Session::flash('success', 'Sent. The stock is on its way and counts at neither end until it arrives.');
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        return $response->redirect('/inventory/transfers/' . (int)$id);
    }

    public function close(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $r = $this->transfers->close((int)$id, trim((string)$request->post('note', '')) ?: null);

            if ($r['missing']) {
                Session::flash('error', sprintf(
                    '%d line%s did not arrive in full. The transfer is marked short so the difference stays visible.',
                    count($r['missing']),
                    count($r['missing']) === 1 ? '' : 's'
                ));
            } else {
                Session::flash('success', 'All of it arrived. Transfer closed.');
            }
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        return $response->redirect('/inventory/transfers/' . (int)$id);
    }

    public function cancel(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $this->transfers->cancel((int)$id);
            Session::flash('success', 'Transfer canceled.');
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        return $response->redirect('/inventory/transfers');
    }
}

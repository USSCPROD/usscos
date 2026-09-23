<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\ReceivingService;

/**
 * Booking goods in — the screen where stock first enters USSCOS.
 *
 * Built for the same handheld as picking: the scan box holds focus, the answer comes back
 * without a page reload, and the running list shows what has been booked in so far so the
 * receiver can see their own work.
 */
class ReceivingController extends Controller
{
    private ReceivingService $receiving;

    public function __construct()
    {
        parent::__construct();
        $this->receiving = new ReceivingService();
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->view('receiving.index', [
            'title'     => 'Receiving',
            'locations' => $this->receiving->locations(),
            'recent'    => $this->receiving->recentReceipts(),
        ]);
    }

    /**
     * Resolve a scan and suggest a quantity, without recording anything.
     *
     * Separate from the receive step on purpose: the receiver sees what the system thinks
     * arrived and confirms or corrects it before any stock moves.
     */
    public function lookup(Request $request, Response $response): Response
    {
        $code = (string)$request->post('code', '');
        $unit = (string)$request->post('unit', 'unit');

        return $response->json($this->receiving->lookup($code, $unit));
    }

    public function store(Request $request, Response $response): Response
    {
        try {
            $result = $this->receiving->receive($_POST);
            Session::flash('success', sprintf(
                'Booked in %s.',
                rtrim(rtrim(number_format($result['qty'], 2), '0'), '.')
            ));
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        return $response->redirect('/receiving');
    }
}

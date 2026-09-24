<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AdjustmentService;
use App\Services\InventoryService;

/**
 * Correcting the count, and the list of counts that need correcting.
 *
 * Restricted to the same roles that could already adjust inventory — this is the one screen
 * that can change stock without anything physical happening, so it is not for everyone.
 */
class AdjustmentController extends Controller
{
    private AdjustmentService $adjustments;
    private InventoryService $inventory;

    public function __construct()
    {
        parent::__construct();
        $this->adjustments = new AdjustmentService();
        $this->inventory   = new InventoryService();
    }

    public function index(Request $request, Response $response): Response
    {
        if (!$this->permitted()) {
            Session::flash('error', 'You do not have permission to adjust inventory.');

            return $response->redirect('/inventory');
        }

        return $this->view('inventory.adjustments', [
            'title'     => 'Stock Adjustments',
            'locations' => $this->adjustments->locations(),
            'reasons'   => AdjustmentService::REASONS,
            'recent'    => $this->adjustments->recent(),
            'negatives' => $this->adjustments->negativeStock(),
        ]);
    }

    /** Resolve a scan and report where that product currently sits. */
    public function lookup(Request $request, Response $response): Response
    {
        if (!$this->permitted()) {
            return $response->json(['found' => false, 'message' => 'Not permitted.'], 403);
        }

        $product = $this->adjustments->lookup((string)$request->post('code', ''));

        if ($product === null) {
            return $response->json([
                'found'   => false,
                'message' => 'Not recognised: ' . $request->post('code', ''),
            ]);
        }

        return $response->json([
            'found'     => true,
            'product'   => $product,
            'locations' => $this->adjustments->stockByLocation((int)$product['id']),
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        if (!$this->permitted()) {
            Session::flash('error', 'You do not have permission to adjust inventory.');

            return $response->redirect('/inventory');
        }

        try {
            $r = $this->adjustments->adjust($_POST);
            $n = fn(float $v) => rtrim(rtrim(number_format($v, 2), '0'), '.');

            Session::flash('success', sprintf(
                'Corrected from %s to %s (%s%s).',
                $n($r['before']),
                $n($r['after']),
                $r['delta'] > 0 ? '+' : '',
                $n($r['delta'])
            ));
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        return $response->redirect('/inventory/adjustments');
    }

    private function permitted(): bool
    {
        $user = Auth::user();

        return $user !== null && $this->inventory->canAdjust($user['role'] ?? '');
    }
}

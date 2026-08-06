<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\QbExportRepository;

/**
 * Machine-to-machine endpoints for the QuickBooks bridge.
 *
 * All routes sit behind the apikey middleware and outside the session auth group —
 * the bridge runs unattended on the Windows box that hosts QuickBooks and writes
 * through QODBC. See docs/QUICKBOOKS_SYNC.md for the Windows side.
 */
class QbSyncController extends Controller
{
    private QbExportRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new QbExportRepository();
    }

    /** GET /api/qb/status — quick health check and pending counts. */
    public function status(Request $request, Response $response): Response
    {
        return $response->json([
            'ok'      => true,
            'time'    => date('c'),
            'pending' => $this->repo->pendingCounts(),
        ]);
    }

    /**
     * GET /api/qb/pending?type=invoices&limit=100
     *
     * Returns records not yet confirmed in QuickBooks. Safe to call repeatedly — a
     * record stays in the response until it is acknowledged, so an interrupted run
     * simply picks it up next time.
     */
    public function pending(Request $request, Response $response): Response
    {
        $type  = (string)($request->query('type') ?? 'invoices');
        $limit = (int)($request->query('limit') ?? 100);
        $batch = 'b' . date('YmdHis') . '-' . bin2hex(random_bytes(3));

        [$records, $logType] = match ($type) {
            'invoices'     => [$this->repo->pendingInvoices($limit),    'invoice'],
            'sales_orders' => [$this->repo->pendingSalesOrders($limit), 'sales_order'],
            'payments'     => [$this->repo->pendingPayments($limit),    'payment'],
            default        => [null, null],
        };

        if ($records === null) {
            return $response->json([
                'error' => "Unknown type '{$type}'. Use invoices, sales_orders or payments.",
            ], 400);
        }

        $this->repo->logSent($logType, array_column($records, 'id'), $batch);

        return $response->json([
            'batch_id' => $batch,
            'type'     => $type,
            'count'    => count($records),
            'records'  => $records,
        ]);
    }

    /**
     * POST /api/qb/ack
     *
     * Body: {"batch_id":"…","results":[{"type":"invoice","id":42,"ok":true,"qb_txn_id":"ABC-123"}]}
     *
     * Only an explicit ok:true stamps a record as exported, so a crash between write and
     * ack leaves it pending rather than silently "done". The bridge must check QuickBooks
     * for the RefNumber before inserting, which makes the retry idempotent.
     */
    public function ack(Request $request, Response $response): Response
    {
        $raw  = file_get_contents('php://input') ?: '';
        $body = json_decode($raw, true);

        if (!is_array($body) || !isset($body['results']) || !is_array($body['results'])) {
            return $response->json(['error' => 'Expected JSON with a results array.'], 400);
        }

        $batchId = isset($body['batch_id']) ? (string)$body['batch_id'] : null;
        $applied = 0;
        $failed  = 0;
        $rejected = [];

        foreach ($body['results'] as $r) {
            $type = (string)($r['type'] ?? '');
            $id   = (int)($r['id'] ?? 0);
            $ok   = (bool)($r['ok'] ?? false);

            if ($id <= 0 || $type === '') {
                $rejected[] = $r;
                continue;
            }

            $done = $this->repo->acknowledge(
                $type,
                $id,
                $ok,
                isset($r['qb_txn_id']) ? (string)$r['qb_txn_id'] : null,
                isset($r['message'])   ? (string)$r['message']   : null,
                $batchId
            );

            if (!$done)      { $rejected[] = $r; }
            elseif ($ok)     { $applied++; }
            else             { $failed++; }
        }

        if ($failed > 0 || $rejected) {
            Logger::error('QuickBooks sync reported problems', [
                'batch'    => $batchId,
                'failed'   => $failed,
                'rejected' => count($rejected),
            ]);
        }

        return $response->json([
            'acknowledged' => $applied,
            'failed'       => $failed,
            'rejected'     => $rejected,
            'pending'      => $this->repo->pendingCounts(),
        ]);
    }
}

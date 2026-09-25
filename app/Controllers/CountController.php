<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\CountService;

/** Cycle counts — counting blind, reviewing the variances, then applying them. */
class CountController extends Controller
{
    private CountService $counts;

    public function __construct()
    {
        parent::__construct();
        $this->counts = new CountService();
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->view('inventory.counts', [
            'title'     => 'Cycle Counts',
            'counts'    => $this->counts->all(),
            'locations' => $this->counts->locations(),
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        try {
            $r = $this->counts->open($_POST);

            Session::flash($r['lines'] > 0 ? 'success' : 'error', $r['lines'] > 0
                ? $r['lines'] . ' item' . ($r['lines'] === 1 ? '' : 's') . ' on the sheet. Count them without looking anything up.'
                : 'Nothing is recorded at that location yet — you can still count whatever you find there.');

            return $response->redirect('/inventory/counts/' . $r['id']);
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());

            return $response->redirect('/inventory/counts');
        }
    }

    public function show(Request $request, Response $response, string $id = '0'): Response
    {
        $count = $this->counts->get((int)$id);

        if ($count === null) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        $counting = $count['status'] === 'counting';

        return $this->view('inventory.count_show', [
            'title' => 'Count ' . $count['count_number'],
            'count' => $count,
            // The counting screen never receives the expected quantities. Not hidden with
            // CSS — not sent at all, so they cannot be read off the page source either.
            'lines' => $counting ? $this->counts->countingLines((int)$id) : $this->counts->reviewLines((int)$id),
        ]);
    }

    public function record(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $r = $this->counts->count(
                (int)$id,
                (string)$request->post('code', ''),
                (string)$request->post('qty', ''),
                trim((string)$request->post('note', '')) ?: null
            );

            return $response->json(['ok' => true] + $r);
        } catch (\RuntimeException $e) {
            return $response->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    public function submit(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $r = $this->counts->submit((int)$id);

            Session::flash('success', $r['uncounted'] > 0
                ? sprintf('%d counted, %d not counted. Uncounted lines are left alone when this is applied.', $r['counted'], $r['uncounted'])
                : 'All counted. Check the variances before applying.');
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        return $response->redirect('/inventory/counts/' . (int)$id);
    }

    public function reopen(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $this->counts->reopen((int)$id);
            Session::flash('success', 'Back to counting.');
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        return $response->redirect('/inventory/counts/' . (int)$id);
    }

    public function apply(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $r = $this->counts->apply((int)$id);
            $n = rtrim(rtrim(number_format($r['net'], 2), '0'), '.');

            Session::flash('success', sprintf(
                '%d correction%s posted (net %s%s). %d line%s needed nothing.',
                $r['adjusted'],
                $r['adjusted'] === 1 ? '' : 's',
                $r['net'] > 0 ? '+' : '',
                $n,
                $r['skipped'],
                $r['skipped'] === 1 ? '' : 's'
            ));
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        return $response->redirect('/inventory/counts/' . (int)$id);
    }

    public function cancel(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $this->counts->cancel((int)$id);
            Session::flash('success', 'Count canceled.');
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        return $response->redirect('/inventory/counts');
    }
}

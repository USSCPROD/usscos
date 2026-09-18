<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\ArtworkService;

/**
 * The Digital Job Binder — everything about a job, hung off its sales order.
 *
 * Artwork first: proofs and mockups with full revision history and an approval trail.
 * Production notes, QA and photos will join it here.
 */
class JobBinderController extends Controller
{
    private ArtworkService $artwork;

    public function __construct()
    {
        parent::__construct();
        $this->artwork = new ArtworkService();
    }

    /** Add a new artwork item, with its first revision. */
    public function storeArtwork(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $this->artwork->create((int)$id, $_POST, $_FILES['artwork_file'] ?? null);
            Session::flash('success', 'Artwork added.');
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        return $response->redirect('/sales-orders/' . (int)$id . '#artwork');
    }

    /** Upload a new version of existing artwork. */
    public function storeRevision(Request $request, Response $response, string $id = '0', string $artworkId = '0'): Response
    {
        try {
            $this->artwork->addRevision((int)$artworkId, $_POST, $_FILES['artwork_file'] ?? null);
            Session::flash('success', 'New revision uploaded.');
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        return $response->redirect('/sales-orders/' . (int)$id . '#artwork');
    }

    /** Record that a specific revision was approved or rejected. */
    public function decide(Request $request, Response $response, string $id = '0', string $revisionId = '0'): Response
    {
        $status = (string)$request->post('decision', '');

        try {
            $this->artwork->decide((int)$revisionId, $status, [
                'source' => $request->post('source'),
                'name'   => $request->post('decided_name'),
                'note'   => $request->post('decision_note'),
            ]);
            Session::flash('success', $status === 'approved' ? 'Approval recorded.' : 'Rejection recorded.');
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        return $response->redirect('/sales-orders/' . (int)$id . '#artwork');
    }

    /** Hide an artwork item. Revisions and their approvals are kept. */
    public function removeArtwork(Request $request, Response $response, string $id = '0', string $artworkId = '0'): Response
    {
        $this->artwork->remove((int)$artworkId);
        Session::flash('success', 'Artwork removed from the binder. Its history is kept.');

        return $response->redirect('/sales-orders/' . (int)$id . '#artwork');
    }
}

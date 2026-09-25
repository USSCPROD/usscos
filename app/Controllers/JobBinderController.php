<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\ArtworkService;
use App\Services\JobDocumentService;
use App\Services\JobBinderService;

/**
 * The Digital Job Binder — everything about a job, hung off its sales order.
 *
 * Artwork: proofs and mockups with full revision history and an approval trail.
 * Documents: the paperwork on the job, the customer's purchase order above all.
 *
 * Both hang off the sales order rather than off its status, so the binder stays with the
 * job for good — invoicing does not close it, and an invoice links back to its order.
 */
class JobBinderController extends Controller
{
    private ArtworkService $artwork;
    private JobDocumentService $documents;
    private JobBinderService $binder;

    public function __construct()
    {
        parent::__construct();
        $this->artwork   = new ArtworkService();
        $this->documents = new JobDocumentService();
        $this->binder    = new JobBinderService();
    }

    /** A note on the job — what happened, for whoever asks in eight months. */
    public function storeNote(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $this->binder->addNote((int)$id, $_POST);
            Session::flash('success', 'Note added to the job.');
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        return $response->redirect('/sales-orders/' . (int)$id . '#notes');
    }

    /** One QA answer. */
    public function storeQa(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $this->binder->recordResult((int)$id, $_POST);
            Session::flash('success', 'Recorded.');
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        return $response->redirect('/sales-orders/' . (int)$id . '#qa');
    }

    /** File a document against the job — a customer PO, a BOL, a signed proof. */
    public function storeDocument(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $this->documents->add((int)$id, $_POST, $_FILES['document_file'] ?? null);
            Session::flash('success', 'Filed on the job binder.');
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        return $response->redirect('/sales-orders/' . (int)$id . '#documents');
    }

    /** Take a document off the binder. The file and the record are kept. */
    public function removeDocument(Request $request, Response $response, string $id = '0', string $documentId = '0'): Response
    {
        $this->documents->remove((int)$documentId);
        Session::flash('success', 'Removed from the binder.');

        return $response->redirect('/sales-orders/' . (int)$id . '#documents');
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

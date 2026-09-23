<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Repositories\ArtworkRepository;

/**
 * Artwork handling for the Digital Job Binder.
 *
 * Uploading a new version never replaces the old one. Every revision is kept with its
 * own approval state, so a job's history stays readable years later — which is the whole
 * reason the binder exists.
 */
class ArtworkService
{
    /** Proofs and mockups. Deliberately narrow: this is artwork, not a document dump. */
    private const ALLOWED_EXT = ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ai', 'eps', 'dxf', 'dwg'];

    private const MAX_BYTES = 31457280;   // 30 MB — vector art and print-res proofs get large

    public function __construct(
        private ArtworkRepository $repo = new ArtworkRepository(),
        private JobFileStore $files = new JobFileStore(),
    ) {
    }

    public function forSalesOrder(int $salesOrderId): array
    {
        return $this->repo->forSalesOrder($salesOrderId);
    }

    public function counts(int $salesOrderId): array
    {
        return $this->repo->countForSalesOrder($salesOrderId);
    }

    /**
     * Create an artwork item and store its first revision.
     *
     * @throws \RuntimeException with a message intended for the user
     */
    public function create(int $salesOrderId, array $input, ?array $file): int
    {
        $title = trim((string)($input['title'] ?? ''));

        if ($title === '') {
            throw new \RuntimeException('Give the artwork a name, e.g. "48in Walking Man".');
        }

        $stored = $this->storeUpload($file, $salesOrderId);

        $artworkId = $this->repo->createArtwork(
            $salesOrderId,
            $title,
            trim((string)($input['notes'] ?? '')) ?: null,
            Auth::id()
        );

        $this->repo->addRevision(
            $artworkId,
            1,
            $stored,
            trim((string)($input['revision_notes'] ?? '')) ?: null,
            Auth::id()
        );

        return $artworkId;
    }

    /**
     * Add a new revision to existing artwork.
     *
     * The previous revision keeps whatever approval it had. Superseding is a fact worth
     * recording, not a reason to erase what came before.
     */
    public function addRevision(int $artworkId, array $input, ?array $file): int
    {
        $artwork = $this->repo->findArtwork($artworkId);

        if ($artwork === null) {
            throw new \RuntimeException('That artwork no longer exists.');
        }

        $stored = $this->storeUpload($file, (int)$artwork['sales_order_id']);
        $next   = $this->repo->nextRevisionNo($artworkId);

        return $this->repo->addRevision(
            $artworkId,
            $next,
            $stored,
            trim((string)($input['revision_notes'] ?? '')) ?: null,
            Auth::id()
        );
    }

    /**
     * Record that a revision was approved or rejected.
     *
     * `decided_name` matters: approval normally arrives by email from a named person at
     * the customer, and recording only the USSCOS user who ticked the box would lose the
     * one detail worth having in a dispute.
     */
    public function decide(int $revisionId, string $status, array $input): void
    {
        if (!in_array($status, ['approved', 'rejected'], true)) {
            throw new \RuntimeException('Unknown decision.');
        }

        $revision = $this->repo->findRevision($revisionId);

        if ($revision === null) {
            throw new \RuntimeException('That revision no longer exists.');
        }

        $sources = ['internal', 'customer_email', 'customer_phone', 'customer_portal'];
        $source  = in_array($input['source'] ?? '', $sources, true) ? $input['source'] : 'internal';

        if ($source !== 'internal' && trim((string)($input['name'] ?? '')) === '') {
            throw new \RuntimeException('Who at the customer gave this decision? Add their name.');
        }

        $this->repo->decide($revisionId, $status, [
            'source'  => $source,
            'user_id' => Auth::id(),
            'name'    => trim((string)($input['name'] ?? '')) ?: null,
            'note'    => trim((string)($input['note'] ?? '')) ?: null,
        ]);
    }

    public function remove(int $artworkId): void
    {
        // Deactivated, never deleted — the approval trail must survive.
        $this->repo->deactivateArtwork($artworkId);
    }

    /** Storing the file itself is shared with job documents — see JobFileStore. */
    private function storeUpload(?array $file, int $salesOrderId): array
    {
        return $this->files->store($file, $salesOrderId, self::ALLOWED_EXT, self::MAX_BYTES, 'artwork');
    }
}

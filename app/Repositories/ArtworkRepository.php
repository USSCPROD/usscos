<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Artwork and its revisions for the Digital Job Binder.
 *
 * An artwork item belongs to one sales order and has many revisions. The revision
 * carries the approval, because "which version did they sign off" is the question the
 * binder exists to answer.
 */
class ArtworkRepository
{
    /**
     * Every artwork item on a job, each with its revisions newest first.
     *
     * Two queries rather than one joined result, so a design with no revisions yet still
     * appears — otherwise a just-created item would vanish from the page.
     */
    public function forSalesOrder(int $salesOrderId): array
    {
        $items = Database::select("
            SELECT a.*,
                   TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) AS created_by_name
            FROM job_artwork a
            LEFT JOIN users u ON u.id = a.created_by
            WHERE a.sales_order_id = ? AND a.is_active = 1
            ORDER BY a.created_at, a.id
        ", [$salesOrderId]);

        if ($items === []) {
            return [];
        }

        foreach ($items as &$item) {
            $item['revisions'] = $this->revisionsFor((int)$item['id']);
            $item['latest']    = $item['revisions'][0] ?? null;

            // The approved revision may not be the latest — someone can upload a new
            // version after approval, and the page must show both facts.
            $item['approved'] = null;
            foreach ($item['revisions'] as $r) {
                if ($r['status'] === 'approved') {
                    $item['approved'] = $r;
                    break;
                }
            }
        }

        return $items;
    }

    /** Revisions for one artwork item, newest first. */
    public function revisionsFor(int $artworkId): array
    {
        return Database::select("
            SELECT r.*,
                   TRIM(CONCAT(COALESCE(up.first_name,''), ' ', COALESCE(up.last_name,''))) AS uploaded_by_name,
                   TRIM(CONCAT(COALESCE(ud.first_name,''), ' ', COALESCE(ud.last_name,''))) AS decided_by_name
            FROM job_artwork_revisions r
            LEFT JOIN users up ON up.id = r.uploaded_by
            LEFT JOIN users ud ON ud.id = r.decided_by
            WHERE r.artwork_id = ?
            ORDER BY r.revision_no DESC
        ", [$artworkId]);
    }

    public function findArtwork(int $id): ?array
    {
        $row = Database::selectOne("SELECT * FROM job_artwork WHERE id = ?", [$id]);

        return $row === false ? null : $row;
    }

    public function findRevision(int $id): ?array
    {
        $row = Database::selectOne("
            SELECT r.*, a.sales_order_id
            FROM job_artwork_revisions r
            JOIN job_artwork a ON a.id = r.artwork_id
            WHERE r.id = ?
        ", [$id]);

        return $row === false ? null : $row;
    }

    public function createArtwork(int $salesOrderId, string $title, ?string $notes, ?int $userId): int
    {
        $pdo = Database::connection();
        $pdo->prepare("
            INSERT INTO job_artwork (sales_order_id, title, notes, created_by)
            VALUES (?, ?, ?, ?)
        ")->execute([$salesOrderId, $title, $notes, $userId]);

        return (int)$pdo->lastInsertId();
    }

    /** The next revision number for an item. Starts at 1. */
    public function nextRevisionNo(int $artworkId): int
    {
        $row = Database::selectOne(
            "SELECT COALESCE(MAX(revision_no), 0) + 1 AS n FROM job_artwork_revisions WHERE artwork_id = ?",
            [$artworkId]
        );

        return (int)($row['n'] ?? 1);
    }

    public function addRevision(int $artworkId, int $revisionNo, array $file, ?string $notes, ?int $userId): int
    {
        $pdo = Database::connection();
        $pdo->prepare("
            INSERT INTO job_artwork_revisions
                (artwork_id, revision_no, file_path, file_name, file_size, mime_type, notes, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $artworkId,
            $revisionNo,
            $file['path'],
            $file['name'],
            $file['size'] ?? null,
            $file['mime'] ?? null,
            $notes,
            $userId,
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Record an approval or rejection against one revision.
     *
     * Earlier approved revisions are left exactly as they were. Superseding an approval
     * by overwriting it would destroy the history the binder is for — the record should
     * show that revision 2 was approved and revision 3 later replaced it.
     */
    public function decide(int $revisionId, string $status, array $decision): void
    {
        Database::statement("
            UPDATE job_artwork_revisions
            SET status          = ?,
                decision_source = ?,
                decided_by      = ?,
                decided_name    = ?,
                decision_note   = ?,
                decided_at      = NOW()
            WHERE id = ?
        ", [
            $status,
            $decision['source'] ?? null,
            $decision['user_id'] ?? null,
            $decision['name'] ?? null,
            $decision['note'] ?? null,
            $revisionId,
        ]);
    }

    /** Hide an artwork item without destroying its revisions or their approval trail. */
    public function deactivateArtwork(int $id): void
    {
        Database::statement("UPDATE job_artwork SET is_active = 0 WHERE id = ?", [$id]);
    }

    /** Counts for the binder tab label, so the page can show "Artwork (3)". */
    public function countForSalesOrder(int $salesOrderId): array
    {
        $row = Database::selectOne("
            SELECT COUNT(DISTINCT a.id) AS items,
                   COUNT(r.id)          AS revisions,
                   SUM(r.status = 'approved') AS approved,
                   SUM(r.status = 'pending')  AS pending
            FROM job_artwork a
            LEFT JOIN job_artwork_revisions r ON r.artwork_id = a.id
            WHERE a.sales_order_id = ? AND a.is_active = 1
        ", [$salesOrderId]);

        return [
            'items'     => (int)($row['items'] ?? 0),
            'revisions' => (int)($row['revisions'] ?? 0),
            'approved'  => (int)($row['approved'] ?? 0),
            'pending'   => (int)($row['pending'] ?? 0),
        ];
    }
}

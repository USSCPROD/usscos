<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/** Paperwork filed against a job — customer POs first. */
class JobDocumentRepository
{
    /** Everything on a job's binder, newest first within each kind. */
    public function forSalesOrder(int $salesOrderId): array
    {
        return Database::select("
            SELECT d.*,
                   TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))) AS uploaded_by_name
            FROM job_documents d
            LEFT JOIN users u ON u.id = d.uploaded_by
            WHERE d.sales_order_id = ? AND d.is_active = 1
            ORDER BY d.doc_type = 'customer_po' DESC, d.created_at DESC, d.id DESC
        ", [$salesOrderId]);
    }

    public function create(int $salesOrderId, array $d): int
    {
        Database::statement("
            INSERT INTO job_documents
                (sales_order_id, doc_type, title, reference_num,
                 file_path, file_name, file_size, mime_type, notes, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $salesOrderId,
            $d['doc_type'],
            $d['title']         ?? null,
            $d['reference_num'] ?? null,
            $d['file_path'],
            $d['file_name'],
            $d['file_size'] ?? null,
            $d['mime_type'] ?? null,
            $d['notes']     ?? null,
            $d['uploaded_by'] ?? null,
        ]);

        return (int)Database::connection()->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $row = Database::selectOne("SELECT * FROM job_documents WHERE id = ?", [$id]);

        return $row === false ? null : $row;
    }

    /**
     * Take a document off the binder.
     *
     * Deactivated rather than deleted, and the file is left on disk. Somebody removing
     * the wrong PO should be recoverable — this is a filing cabinet, and things filed in
     * it are meant to survive.
     */
    public function deactivate(int $id): void
    {
        Database::statement("UPDATE job_documents SET is_active = 0 WHERE id = ?", [$id]);
    }
}

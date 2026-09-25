<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/** Notes and QA results on a job. */
class JobBinderRepository
{
    // ------------------------------------------------------------------ notes

    public function notes(int $salesOrderId): array
    {
        return Database::select("
            SELECT n.*,
                   TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) AS author
            FROM job_notes n
            LEFT JOIN users u ON u.id = n.created_by
            WHERE n.sales_order_id = ?
            ORDER BY n.created_at DESC, n.id DESC
        ", [$salesOrderId]);
    }

    public function addNote(int $salesOrderId, string $type, string $body, ?int $userId): int
    {
        Database::statement(
            "INSERT INTO job_notes (sales_order_id, note_type, body, created_by) VALUES (?, ?, ?, ?)",
            [$salesOrderId, $type, $body, $userId]
        );

        return (int)Database::connection()->lastInsertId();
    }

    // --------------------------------------------------------------------- QA

    /** The checks in use, with this job's answer where there is one. */
    public function checklist(int $salesOrderId): array
    {
        return Database::select("
            SELECT c.id, c.label, c.help, c.is_required,
                   r.result, r.note, r.checked_at,
                   TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) AS checked_by_name
            FROM qa_checks c
            LEFT JOIN job_qa_results r ON r.qa_check_id = c.id AND r.sales_order_id = ?
            LEFT JOIN users u          ON u.id = r.checked_by
            WHERE c.is_active = 1
            ORDER BY c.sort_order, c.id
        ", [$salesOrderId]);
    }

    /** Answering the same check again replaces the answer — a QA result is current, not a log. */
    public function recordResult(int $salesOrderId, int $checkId, string $result, ?string $note, ?int $userId): void
    {
        Database::statement("
            INSERT INTO job_qa_results (sales_order_id, qa_check_id, result, note, checked_by, checked_at)
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                result = VALUES(result), note = VALUES(note),
                checked_by = VALUES(checked_by), checked_at = NOW()
        ", [$salesOrderId, $checkId, $result, $note, $userId]);
    }

    public function findCheck(int $id): ?array
    {
        $row = Database::selectOne("SELECT * FROM qa_checks WHERE id = ?", [$id]);

        return $row === false ? null : $row;
    }

    public function activeCheckCount(): int
    {
        $row = Database::selectOne("SELECT COUNT(*) AS n FROM qa_checks WHERE is_active = 1");

        return (int)($row['n'] ?? 0);
    }

    // ------------------------------------------------------- managing the checks

    public function allChecks(): array
    {
        return Database::select("
            SELECT c.*, (SELECT COUNT(*) FROM job_qa_results r WHERE r.qa_check_id = c.id) AS times_answered
            FROM qa_checks c
            ORDER BY c.is_active DESC, c.sort_order, c.id
        ");
    }

    public function createCheck(string $label, ?string $help, bool $required): int
    {
        $row = Database::selectOne("SELECT COALESCE(MAX(sort_order), 0) AS m FROM qa_checks");

        Database::statement(
            "INSERT INTO qa_checks (label, help, is_required, sort_order) VALUES (?, ?, ?, ?)",
            [$label, $help, $required ? 1 : 0, (int)($row['m'] ?? 0) + 10]
        );

        return (int)Database::connection()->lastInsertId();
    }

    /**
     * Retire a check rather than delete it.
     *
     * Results already recorded against it are evidence of what was checked on jobs that
     * have shipped, and deleting the check would take them with it.
     */
    public function deactivateCheck(int $id): void
    {
        Database::statement("UPDATE qa_checks SET is_active = 0 WHERE id = ?", [$id]);
    }

    public function reactivateCheck(int $id): void
    {
        Database::statement("UPDATE qa_checks SET is_active = 1 WHERE id = ?", [$id]);
    }
}

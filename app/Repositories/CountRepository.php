<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/** Cycle counts and the lines on them. */
class CountRepository
{
    public function nextNumber(): string
    {
        $row = Database::selectOne("SELECT count_number FROM stock_counts ORDER BY id DESC LIMIT 1");
        $n   = $row === false ? 0 : (int)preg_replace('/\D/', '', (string)$row['count_number']);

        return 'CC-' . str_pad((string)($n + 1), 5, '0', STR_PAD_LEFT);
    }

    public function create(int $locationId, string $type, ?string $notes, ?int $userId): int
    {
        Database::statement("
            INSERT INTO stock_counts (count_number, location_id, count_type, notes, created_by)
            VALUES (?, ?, ?, ?, ?)
        ", [$this->nextNumber(), $locationId, $type, $notes, $userId]);

        return (int)Database::connection()->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $row = Database::selectOne("
            SELECT c.*, l.code AS location_code, l.name AS location_name,
                   TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) AS applied_by_name
            FROM stock_counts c
            JOIN stock_locations l ON l.id = c.location_id
            LEFT JOIN users u ON u.id = c.applied_by
            WHERE c.id = ?
        ", [$id]);

        return $row === false ? null : $row;
    }

    /**
     * Seed a sheet with what the system believes is at this location.
     *
     * Products with a zero balance are included when they have ever been there, because
     * "the system says none and there are twelve" is the finding worth having.
     */
    public function seedFromLocation(int $countId, int $locationId): int
    {
        Database::statement("
            INSERT IGNORE INTO stock_count_lines (count_id, product_id)
            SELECT ?, ps.product_id
            FROM product_stock ps
            JOIN products p ON p.id = ps.product_id
            WHERE ps.location_id = ? AND p.is_active = 1
        ", [$countId, $locationId]);

        $row = Database::selectOne("SELECT COUNT(*) AS n FROM stock_count_lines WHERE count_id = ?", [$countId]);

        return (int)($row['n'] ?? 0);
    }

    /** Lines for counting — deliberately without the expected quantity. */
    public function linesForCounting(int $countId): array
    {
        return Database::select("
            SELECT cl.id, cl.product_id, cl.counted_qty, cl.counted_at, cl.note,
                   p.sku, p.name AS product_name, p.uom_code
            FROM stock_count_lines cl
            JOIN products p ON p.id = cl.product_id
            WHERE cl.count_id = ?
            ORDER BY cl.counted_at IS NOT NULL, p.name
        ", [$countId]);
    }

    /** Lines with the variance, for the review step. */
    public function linesForReview(int $countId): array
    {
        return Database::select("
            SELECT cl.*, p.sku, p.name AS product_name, p.uom_code,
                   (cl.counted_qty - cl.expected_qty) AS variance,
                   TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) AS counted_by_name
            FROM stock_count_lines cl
            JOIN products p ON p.id = cl.product_id
            LEFT JOIN users u ON u.id = cl.counted_by
            WHERE cl.count_id = ?
            ORDER BY ABS(COALESCE(cl.counted_qty, 0) - COALESCE(cl.expected_qty, 0)) DESC, p.name
        ", [$countId]);
    }

    public function findLine(int $countId, int $productId): ?array
    {
        $row = Database::selectOne(
            "SELECT * FROM stock_count_lines WHERE count_id = ? AND product_id = ?",
            [$countId, $productId]
        );

        return $row === false ? null : $row;
    }

    public function addLine(int $countId, int $productId): int
    {
        Database::statement(
            "INSERT IGNORE INTO stock_count_lines (count_id, product_id) VALUES (?, ?)",
            [$countId, $productId]
        );

        $row = Database::selectOne(
            "SELECT id FROM stock_count_lines WHERE count_id = ? AND product_id = ?",
            [$countId, $productId]
        );

        return (int)($row['id'] ?? 0);
    }

    public function recordCount(int $lineId, float $qty, float $expected, ?int $userId, ?string $note): void
    {
        Database::statement("
            UPDATE stock_count_lines
            SET counted_qty = ?, expected_qty = ?, counted_by = ?, counted_at = NOW(), note = ?
            WHERE id = ?
        ", [$qty, $expected, $userId, $note, $lineId]);
    }

    public function setStatus(int $id, string $status): void
    {
        Database::statement("UPDATE stock_counts SET status = ? WHERE id = ?", [$status, $id]);
    }

    public function markApplied(int $id, ?int $userId): void
    {
        Database::statement("
            UPDATE stock_counts SET status = 'applied', applied_by = ?, applied_at = NOW() WHERE id = ?
        ", [$userId, $id]);
    }

    public function all(int $limit = 50): array
    {
        return Database::select("
            SELECT c.*, l.code AS location_code,
                   (SELECT COUNT(*) FROM stock_count_lines cl WHERE cl.count_id = c.id) AS line_count,
                   (SELECT COUNT(*) FROM stock_count_lines cl WHERE cl.count_id = c.id AND cl.counted_qty IS NOT NULL) AS counted,
                   (SELECT COUNT(*) FROM stock_count_lines cl WHERE cl.count_id = c.id
                      AND cl.counted_qty IS NOT NULL AND ABS(cl.counted_qty - cl.expected_qty) > 0.0001) AS variances
            FROM stock_counts c
            JOIN stock_locations l ON l.id = c.location_id
            ORDER BY FIELD(c.status, 'counting', 'review', 'applied', 'cancelled'), c.id DESC
            LIMIT {$limit}
        ");
    }

    /**
     * Products at a location that have not been counted for a while.
     *
     * The point of cycle counting is that everything comes round eventually, so what
     * matters is the oldest, and anything never counted at all sorts first.
     */
    public function staleAt(int $locationId, int $days = 90, int $limit = 25): array
    {
        return Database::select("
            SELECT p.id, p.sku, p.name, ps.qty_on_hand, ps.counted_at
            FROM product_stock ps
            JOIN products p ON p.id = ps.product_id
            WHERE ps.location_id = ?
              AND p.is_active = 1
              AND (ps.counted_at IS NULL OR ps.counted_at < DATE_SUB(NOW(), INTERVAL ? DAY))
            ORDER BY ps.counted_at IS NOT NULL, ps.counted_at, ABS(ps.qty_on_hand) DESC
            LIMIT {$limit}
        ", [$locationId, $days]);
    }
}

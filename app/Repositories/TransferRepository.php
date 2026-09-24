<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/** Transfers between warehouses, and the lines on them. */
class TransferRepository
{
    public function nextNumber(): string
    {
        $row = Database::selectOne(
            "SELECT transfer_number FROM stock_transfers ORDER BY id DESC LIMIT 1"
        );

        $n = $row === false ? 0 : (int)preg_replace('/\D/', '', (string)$row['transfer_number']);

        return 'TR-' . str_pad((string)($n + 1), 5, '0', STR_PAD_LEFT);
    }

    public function create(int $fromId, int $toId, ?string $notes, ?int $userId): int
    {
        Database::statement("
            INSERT INTO stock_transfers (transfer_number, from_location_id, to_location_id, notes, created_by)
            VALUES (?, ?, ?, ?, ?)
        ", [$this->nextNumber(), $fromId, $toId, $notes, $userId]);

        return (int)Database::connection()->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $row = Database::selectOne("
            SELECT t.*,
                   lf.code AS from_code, lf.name AS from_name,
                   lt.code AS to_code,   lt.name AS to_name,
                   TRIM(CONCAT(COALESCE(us.first_name,''), ' ', COALESCE(us.last_name,''))) AS sent_by_name,
                   TRIM(CONCAT(COALESCE(ur.first_name,''), ' ', COALESCE(ur.last_name,''))) AS received_by_name
            FROM stock_transfers t
            JOIN stock_locations lf ON lf.id = t.from_location_id
            JOIN stock_locations lt ON lt.id = t.to_location_id
            LEFT JOIN users us ON us.id = t.sent_by
            LEFT JOIN users ur ON ur.id = t.received_by
            WHERE t.id = ?
        ", [$id]);

        return $row === false ? null : $row;
    }

    public function lines(int $transferId): array
    {
        return Database::select("
            SELECT l.*, p.sku, p.name AS product_name, p.units_per_case
            FROM stock_transfer_lines l
            JOIN products p ON p.id = l.product_id
            WHERE l.transfer_id = ?
            ORDER BY p.name
        ", [$transferId]);
    }

    /** Add to a line, or create it. Scanning the same item twice adds up rather than replacing. */
    public function addToLine(int $transferId, int $productId, float $qty): void
    {
        Database::statement("
            INSERT INTO stock_transfer_lines (transfer_id, product_id, qty_sent)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE qty_sent = qty_sent + VALUES(qty_sent)
        ", [$transferId, $productId, $qty]);
    }

    public function setLineSent(int $lineId, float $qty): void
    {
        Database::statement("UPDATE stock_transfer_lines SET qty_sent = ? WHERE id = ?", [$qty, $lineId]);
    }

    public function removeLine(int $lineId): void
    {
        Database::statement("DELETE FROM stock_transfer_lines WHERE id = ?", [$lineId]);
    }

    public function addReceived(int $lineId, float $qty): void
    {
        Database::statement(
            "UPDATE stock_transfer_lines SET qty_received = qty_received + ? WHERE id = ?",
            [$qty, $lineId]
        );
    }

    public function setLineNote(int $lineId, ?string $note): void
    {
        Database::statement("UPDATE stock_transfer_lines SET note = ? WHERE id = ?", [$note, $lineId]);
    }

    public function findLine(int $transferId, int $productId): ?array
    {
        $row = Database::selectOne(
            "SELECT * FROM stock_transfer_lines WHERE transfer_id = ? AND product_id = ?",
            [$transferId, $productId]
        );

        return $row === false ? null : $row;
    }

    public function markSent(int $id, ?int $userId): void
    {
        Database::statement("
            UPDATE stock_transfers SET status = 'in_transit', sent_by = ?, sent_at = NOW() WHERE id = ?
        ", [$userId, $id]);
    }

    public function markReceived(int $id, ?int $userId, bool $short): void
    {
        Database::statement("
            UPDATE stock_transfers
            SET status = ?, received_by = ?, received_at = NOW()
            WHERE id = ?
        ", [$short ? 'short' : 'received', $userId, $id]);
    }

    public function setStatus(int $id, string $status): void
    {
        Database::statement("UPDATE stock_transfers SET status = ? WHERE id = ?", [$status, $id]);
    }

    public function all(int $limit = 50): array
    {
        return Database::select("
            SELECT t.*,
                   lf.code AS from_code, lt.code AS to_code,
                   (SELECT COUNT(*) FROM stock_transfer_lines l WHERE l.transfer_id = t.id) AS line_count,
                   (SELECT COALESCE(SUM(l.qty_sent), 0) FROM stock_transfer_lines l WHERE l.transfer_id = t.id) AS qty_sent,
                   (SELECT COALESCE(SUM(l.qty_received), 0) FROM stock_transfer_lines l WHERE l.transfer_id = t.id) AS qty_received
            FROM stock_transfers t
            JOIN stock_locations lf ON lf.id = t.from_location_id
            JOIN stock_locations lt ON lt.id = t.to_location_id
            ORDER BY FIELD(t.status, 'in_transit', 'short', 'draft', 'received', 'cancelled'), t.id DESC
            LIMIT {$limit}
        ");
    }

    /**
     * What is on a truck right now.
     *
     * Stock that has left one building and not arrived at the other belongs to neither, so
     * the on-hand total genuinely dips while it travels. This is what makes that
     * explainable rather than alarming.
     */
    public function inTransitSummary(): array
    {
        return Database::select("
            SELECT p.id AS product_id, p.sku, p.name AS product_name,
                   SUM(l.qty_sent - l.qty_received) AS qty,
                   COUNT(DISTINCT t.id) AS transfers
            FROM stock_transfer_lines l
            JOIN stock_transfers t ON t.id = l.transfer_id
            JOIN products p ON p.id = l.product_id
            WHERE t.status = 'in_transit' AND l.qty_sent > l.qty_received
            GROUP BY p.id, p.sku, p.name
            ORDER BY qty DESC
        ");
    }
}

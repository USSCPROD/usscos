<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Stock held per product per location, and the movements that change it.
 *
 * Every change to stock goes through `applyMovement()`. That is the point: the current
 * count is wrong precisely because things move without being recorded, so there is one
 * function that does both — write the movement and update the balance — inside one
 * database transaction. Neither can happen without the other.
 *
 * `products.qty_on_hand` stays as the total across locations and is **recomputed** from
 * `product_stock` rather than incremented. Incrementing drifts the moment anything is
 * missed; recomputing makes the total true by definition.
 */
class StockRepository
{
    /**
     * Record a movement and update the balances it affects.
     *
     * @param array{
     *   product_id:int, qty:float, type:string,
     *   from_location_id?:?int, to_location_id?:?int,
     *   qty_expected?:?float,
     *   reference_type?:?string, reference_id?:?int, reference_num?:?string,
     *   notes?:?string, user_id?:?int, date?:?string
     * } $m
     *
     * @return int the inventory_transactions id
     * @throws \RuntimeException if the movement makes no sense
     */
    public function applyMovement(array $m): int
    {
        $productId = (int)$m['product_id'];
        $qty       = (float)$m['qty'];
        $from      = isset($m['from_location_id']) ? (int)$m['from_location_id'] : null;
        $to        = isset($m['to_location_id'])   ? (int)$m['to_location_id']   : null;

        if ($qty <= 0) {
            throw new \RuntimeException('Quantity must be greater than zero.');
        }
        if ($from === null && $to === null) {
            throw new \RuntimeException('A movement needs a source, a destination, or both.');
        }

        $pdo = Database::connection();
        $ownTransaction = !$pdo->inTransaction();

        if ($ownTransaction) {
            $pdo->beginTransaction();
        }

        try {
            if ($from !== null) {
                $this->addToLocation($productId, $from, -$qty);
            }
            if ($to !== null) {
                $this->addToLocation($productId, $to, $qty);
            }

            $total = $this->recomputeTotal($productId);

            $stmt = $pdo->prepare("
                INSERT INTO inventory_transactions
                    (product_id, from_location_id, to_location_id, transaction_type,
                     reference_type, reference_id, reference_num,
                     transaction_date, qty, qty_expected, qty_on_hand_after, notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $productId,
                $from,
                $to,
                $m['type'],
                $m['reference_type'] ?? null,
                $m['reference_id']   ?? null,
                $m['reference_num']  ?? null,
                $m['date'] ?? date('Y-m-d'),
                // Signed from the company's point of view: in is positive, out is negative,
                // a transfer nets to zero because nothing was gained or lost.
                ($to !== null ? $qty : 0) - ($from !== null ? $qty : 0),
                // What the system offered before the receiver corrected it. Repeated
                // corrections on one product are how a wrong pack quantity gets noticed.
                $m['qty_expected'] ?? null,
                $total,
                $m['notes'] ?? null,
                $m['user_id'] ?? null,
            ]);

            $id = (int)$pdo->lastInsertId();

            if ($ownTransaction) {
                $pdo->commit();
            }

            return $id;
        } catch (\Throwable $e) {
            if ($ownTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Add (or subtract) at one location, creating the row if stock has never been there.
     *
     * Negative balances are allowed deliberately. Refusing them sounds safer but pushes
     * people to work around the system when reality and the count disagree — and a
     * visible negative is a far better signal than a silent refusal.
     */
    private function addToLocation(int $productId, int $locationId, float $delta): void
    {
        Database::statement("
            INSERT INTO product_stock (product_id, location_id, qty_on_hand)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE qty_on_hand = qty_on_hand + VALUES(qty_on_hand)
        ", [$productId, $locationId, $delta]);
    }

    /** Set products.qty_on_hand to the sum across locations. */
    private function recomputeTotal(int $productId): float
    {
        $row = Database::selectOne(
            "SELECT COALESCE(SUM(qty_on_hand), 0) AS total FROM product_stock WHERE product_id = ?",
            [$productId]
        );
        $total = (float)($row['total'] ?? 0);

        Database::statement("UPDATE products SET qty_on_hand = ? WHERE id = ?", [$total, $productId]);

        return $total;
    }

    /** Where a product is, and how much is at each place. */
    public function stockByLocation(int $productId): array
    {
        return Database::select("
            SELECT ps.location_id, ps.qty_on_hand, ps.counted_at,
                   l.code, l.name, l.location_type,
                   p.code AS parent_code
            FROM product_stock ps
            JOIN stock_locations l ON l.id = ps.location_id
            LEFT JOIN stock_locations p ON p.id = l.parent_id
            WHERE ps.product_id = ? AND ps.qty_on_hand <> 0
            ORDER BY l.sort_order, l.code
        ", [$productId]);
    }

    /**
     * Resolve a scanned or typed code to a product.
     *
     * Case barcode first, then unit barcode, then SKU — the same order the picking screen
     * uses, so a scanner behaves identically wherever it is pointed. `is_case` matters on
     * receipt: a GTIN-14 is the outer carton.
     */
    public function resolveScan(string $code): ?array
    {
        $code = trim($code);

        if ($code === '') {
            return null;
        }

        foreach ([['gtin14', true], ['gtin12', false], ['sku', false]] as [$column, $isCase]) {
            $row = Database::selectOne(
                "SELECT id, sku, name, uom_code, units_per_case, pallet_qty, pallet_qty_varies, qty_on_hand
                 FROM products WHERE {$column} = ? LIMIT 1",
                [$code]
            );

            if ($row !== false) {
                return ['product' => $row, 'is_case' => $isCase];
            }
        }

        return null;
    }

    /** Recent receipts, for the running list on the receiving screen. */
    public function recentMovements(string $type, int $limit = 25): array
    {
        return Database::select("
            SELECT t.id, t.qty, t.transaction_date, t.created_at, t.notes, t.reference_num,
                   p.sku, p.name AS product_name,
                   lt.code AS to_code, lf.code AS from_code,
                   TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) AS user_name
            FROM inventory_transactions t
            JOIN products p ON p.id = t.product_id
            LEFT JOIN stock_locations lt ON lt.id = t.to_location_id
            LEFT JOIN stock_locations lf ON lf.id = t.from_location_id
            LEFT JOIN users u ON u.id = t.created_by
            WHERE t.transaction_type = ?
            ORDER BY t.id DESC
            LIMIT {$limit}
        ", [$type]);
    }

    /** Active locations that can hold stock, for the destination picker. */
    public function storableLocations(): array
    {
        return Database::select("
            SELECT l.id, l.code, l.name, l.location_type, p.code AS parent_code
            FROM stock_locations l
            LEFT JOIN stock_locations p ON p.id = l.parent_id
            WHERE l.is_active = 1
            ORDER BY COALESCE(p.sort_order, l.sort_order), l.sort_order, l.code
        ");
    }
}

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
                    (product_id, from_location_id, to_location_id, transaction_type, reason_code,
                     reference_type, reference_id, reference_num,
                     transaction_date, qty, qty_expected, qty_on_hand_after, notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $productId,
                $from,
                $to,
                $m['type'],
                $m['reason_code']    ?? null,
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

    /**
     * Take stock out for a shipment, working out where it came from.
     *
     * Picking does not record a location — the picker scans the product, not the shelf —
     * so consumption has to decide for itself. It draws from the biggest holding first,
     * which keeps stock consolidated rather than leaving a trail of ones and twos across
     * the building, and splits across locations when no single one covers the line.
     *
     * If the count says there is not enough, the shortfall still comes out, against the
     * location that held the most (or the default warehouse when the product is recorded
     * nowhere at all). The paint physically left the building; refusing to record that
     * would keep the number tidy and wrong. A negative balance is a visible question.
     *
     * @return list<array{location_id:int, qty:float, was_short:bool}> what came from where
     */
    public function consume(int $productId, float $qty, array $m = []): array
    {
        if ($qty <= 0) {
            return [];
        }

        $pdo = Database::connection();
        $ownTransaction = !$pdo->inTransaction();

        if ($ownTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $holdings = Database::select("
                SELECT ps.location_id, ps.qty_on_hand
                FROM product_stock ps
                JOIN stock_locations l ON l.id = ps.location_id
                WHERE ps.product_id = ? AND ps.qty_on_hand > 0 AND l.is_active = 1
                ORDER BY ps.qty_on_hand DESC, ps.location_id
            ", [$productId]);

            $remaining   = $qty;
            $allocations = [];

            foreach ($holdings as $h) {
                if ($remaining <= 0) {
                    break;
                }

                $take = min($remaining, (float)$h['qty_on_hand']);
                $allocations[] = [
                    'location_id' => (int)$h['location_id'],
                    'qty'         => $take,
                    'was_short'   => false,
                ];
                $remaining -= $take;
            }

            // More went out than the system thought we had.
            if ($remaining > 0.0001) {
                $fallback = $holdings
                    ? (int)$holdings[0]['location_id']
                    : $this->defaultWarehouseId();

                if ($fallback === null) {
                    throw new \RuntimeException(
                        'No stock location to ship from — add a warehouse under Admin > Locations.'
                    );
                }

                $allocations[] = [
                    'location_id' => $fallback,
                    'qty'         => $remaining,
                    'was_short'   => true,
                ];
            }

            foreach ($allocations as $a) {
                $note = $m['notes'] ?? null;

                if ($a['was_short']) {
                    $short = 'Shipped ' . rtrim(rtrim(number_format($a['qty'], 2), '0'), '.')
                           . ' more than the count showed here';
                    $note = $note ? $note . '. ' . $short : $short;
                }

                $this->applyMovement([
                    'product_id'       => $productId,
                    'qty'              => $a['qty'],
                    'type'             => $m['type'] ?? 'sale',
                    'from_location_id' => $a['location_id'],
                    'reference_type'   => $m['reference_type'] ?? null,
                    'reference_id'     => $m['reference_id']   ?? null,
                    'reference_num'    => $m['reference_num']  ?? null,
                    'notes'            => $note,
                    'user_id'          => $m['user_id'] ?? null,
                    'date'             => $m['date']    ?? null,
                ]);
            }

            if ($ownTransaction) {
                $pdo->commit();
            }

            return $allocations;
        } catch (\Throwable $e) {
            if ($ownTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * How much has already moved for one invoice, per product.
     *
     * Signed the way the transaction log stores it, so a negative number means that much
     * has gone out. Lets a second look at the same invoice work out the difference rather
     * than moving the stock all over again.
     *
     * @return array<int,float> net signed quantity, keyed by product id
     */
    public function netMovedForInvoice(int $invoiceId): array
    {
        $rows = Database::select("
            SELECT product_id, SUM(qty) AS net
            FROM inventory_transactions
            WHERE reference_type = 'invoice' AND reference_id = ?
            GROUP BY product_id
        ", [$invoiceId]);

        $net = [];
        foreach ($rows as $r) {
            $net[(int)$r['product_id']] = (float)$r['net'];
        }

        return $net;
    }

    /**
     * Put stock back, where an invoice has been corrected downwards.
     *
     * It goes back to the locations this invoice took it from, most recent first, because
     * that is where it physically is — somebody carried it back to the shelf it came off.
     * Only if the original movements are gone does it fall back to the main warehouse.
     *
     * @return list<array{location_id:int, qty:float}>
     */
    public function restore(int $productId, float $qty, array $m = []): array
    {
        if ($qty <= 0) {
            return [];
        }

        $sources = Database::select("
            SELECT from_location_id, SUM(-qty) AS taken
            FROM inventory_transactions
            WHERE reference_type = 'invoice' AND reference_id = ?
              AND product_id = ? AND from_location_id IS NOT NULL AND qty < 0
            GROUP BY from_location_id
            ORDER BY MAX(id) DESC
        ", [$m['reference_id'] ?? 0, $productId]);

        $remaining = $qty;
        $puts      = [];

        foreach ($sources as $s) {
            if ($remaining <= 0) {
                break;
            }

            $put = min($remaining, (float)$s['taken']);
            $puts[] = ['location_id' => (int)$s['from_location_id'], 'qty' => $put];
            $remaining -= $put;
        }

        if ($remaining > 0.0001) {
            $fallback = $this->defaultWarehouseId();

            if ($fallback === null) {
                throw new \RuntimeException(
                    'No stock location to return to — add a warehouse under Admin > Locations.'
                );
            }

            $puts[] = ['location_id' => $fallback, 'qty' => $remaining];
        }

        foreach ($puts as $p) {
            $this->applyMovement([
                'product_id'     => $productId,
                'qty'            => $p['qty'],
                // Not a customer return — nothing came back from a customer. The invoice
                // was corrected, so this is an adjustment that happens to reference it.
                'type'           => 'adjustment',
                'to_location_id' => $p['location_id'],
                'reference_type' => 'invoice',
                'reference_id'   => $m['reference_id']  ?? null,
                'reference_num'  => $m['reference_num'] ?? null,
                'notes'          => $m['notes'] ?? 'Invoice reduced — stock put back',
                'user_id'        => $m['user_id'] ?? null,
            ]);
        }

        return $puts;
    }

    /** The warehouse to fall back on when a product is recorded at no location at all. */
    public function defaultWarehouseId(): ?int
    {
        $row = Database::selectOne("
            SELECT id FROM stock_locations
            WHERE is_active = 1 AND location_type = 'warehouse' AND parent_id IS NULL
            ORDER BY sort_order, id
            LIMIT 1
        ");

        return $row === false ? null : (int)$row['id'];
    }

    /**
     * Products whose stock is actually counted, out of the given ids.
     *
     * Freight, setup charges and discounts sit on orders as line items but have no
     * physical existence, so shipping one must not move stock.
     *
     * @param  list<int> $productIds
     * @return array<int,bool> keyed by product id
     */
    public function stockedFlags(array $productIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $productIds)));

        if (!$ids) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $rows = Database::select(
            "SELECT id, item_type FROM products WHERE id IN ({$placeholders})",
            $ids
        );

        $flags = [];
        foreach ($rows as $r) {
            $flags[(int)$r['id']] = in_array($r['item_type'], ['inventory_part', 'inventory_assembly'], true);
        }

        return $flags;
    }

    /**
     * Correct the stock at one location by a signed amount.
     *
     * Runs through applyMovement like everything else, so an adjustment is logged and the
     * balance moves together. A positive delta goes in, a negative one comes out — the
     * direction is the sign, because "add 5" and "remove 5" are the same event.
     *
     * @return int the transaction id
     */
    public function adjustAt(int $productId, int $locationId, float $delta, array $m = []): int
    {
        if (abs($delta) < 0.0001) {
            throw new \RuntimeException('That would not change anything.');
        }

        return $this->applyMovement([
            'product_id'       => $productId,
            'qty'              => abs($delta),
            'type'             => 'adjustment',
            'reason_code'      => $m['reason_code'] ?? null,
            'from_location_id' => $delta < 0 ? $locationId : null,
            'to_location_id'   => $delta > 0 ? $locationId : null,
            'reference_num'    => $m['reference_num'] ?? null,
            'notes'            => $m['notes'] ?? null,
            'user_id'          => $m['user_id'] ?? null,
        ]);
    }

    /** What the system currently thinks is at one location. */
    public function qtyAt(int $productId, int $locationId): float
    {
        $row = Database::selectOne(
            "SELECT qty_on_hand FROM product_stock WHERE product_id = ? AND location_id = ?",
            [$productId, $locationId]
        );

        return $row === false ? 0.0 : (float)$row['qty_on_hand'];
    }

    /** Record that this product was physically counted here, and by whom. */
    public function markCounted(int $productId, int $locationId, ?int $userId): void
    {
        Database::statement("
            INSERT INTO product_stock (product_id, location_id, qty_on_hand, counted_at, counted_by)
            VALUES (?, ?, 0, NOW(), ?)
            ON DUPLICATE KEY UPDATE counted_at = NOW(), counted_by = VALUES(counted_by)
        ", [$productId, $locationId, $userId]);
    }

    /**
     * Stock sitting below zero.
     *
     * Negatives are allowed deliberately — refusing them pushes people to work around the
     * system — but a negative that nobody looks at is just a wrong number. This is the
     * worklist that turns it back into a question.
     */
    public function negativeStock(): array
    {
        return Database::select("
            SELECT ps.product_id, ps.qty_on_hand, ps.counted_at,
                   p.sku, p.name AS product_name,
                   l.id AS location_id, l.code AS location_code, l.name AS location_name,
                   (SELECT MAX(t.created_at) FROM inventory_transactions t
                     WHERE t.product_id = ps.product_id
                       AND (t.from_location_id = ps.location_id OR t.to_location_id = ps.location_id)
                   ) AS last_movement
            FROM product_stock ps
            JOIN products p        ON p.id = ps.product_id
            JOIN stock_locations l ON l.id = ps.location_id
            WHERE ps.qty_on_hand < 0
            ORDER BY ps.qty_on_hand
        ");
    }

    /** Recent adjustments, with their reasons, for the running list. */
    public function recentAdjustments(int $limit = 25): array
    {
        return Database::select("
            SELECT t.id, t.qty, t.reason_code, t.notes, t.created_at, t.qty_on_hand_after,
                   p.sku, p.name AS product_name,
                   COALESCE(lf.code, lt.code) AS location_code,
                   TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) AS user_name
            FROM inventory_transactions t
            JOIN products p ON p.id = t.product_id
            LEFT JOIN stock_locations lf ON lf.id = t.from_location_id
            LEFT JOIN stock_locations lt ON lt.id = t.to_location_id
            LEFT JOIN users u ON u.id = t.created_by
            WHERE t.transaction_type = 'adjustment'
            ORDER BY t.id DESC
            LIMIT {$limit}
        ");
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

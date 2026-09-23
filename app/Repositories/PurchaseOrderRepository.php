<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class PurchaseOrderRepository
{
    public function paginate(int $page, int $perPage, string $search = '', string $status = ''): array
    {
        $conditions = [];
        $params     = [];

        if ($search !== '') {
            $conditions[] = '(po.po_number LIKE ? OR v.company_name LIKE ? OR po.vendor_ref LIKE ?)';
            $s = '%' . $search . '%';
            array_push($params, $s, $s, $s);
        }

        if ($status !== '') {
            $conditions[] = 'po.status = ?';
            $params[]     = $status;
        }

        $where  = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $offset = ($page - 1) * $perPage;

        $total = (int)(Database::selectOne(
            "SELECT COUNT(*) AS total
             FROM purchase_orders po
             JOIN vendors v ON v.id = po.vendor_id
             $where", $params
        )['total'] ?? 0);

        $rows = Database::select(
            "SELECT po.id, po.po_number, po.status, po.order_date, po.expected_date,
                    po.total_amount, po.vendor_ref,
                    v.company_name AS vendor_name,
                    (SELECT COUNT(*) FROM purchase_order_lines WHERE po_id = po.id) AS line_count,
                    (SELECT SUM(qty_ordered - qty_received) FROM purchase_order_lines WHERE po_id = po.id) AS qty_outstanding
             FROM purchase_orders po
             JOIN vendors v ON v.id = po.vendor_id
             $where
             ORDER BY po.order_date DESC, po.id DESC
             LIMIT $perPage OFFSET $offset",
            $params
        );

        return [
            'data'         => $rows,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int)ceil($total / $perPage),
            'from'         => $offset + 1,
            'to'           => min($offset + $perPage, $total),
        ];
    }

    public function findById(int $id): array|false
    {
        return Database::selectOne(
            "SELECT po.*, v.company_name AS vendor_name, v.email AS vendor_email,
                    v.phone AS vendor_phone, v.address_1 AS vendor_address_1,
                    v.address_2 AS vendor_address_2, v.city AS vendor_city,
                    v.state AS vendor_state, v.zip AS vendor_zip,
                    v.account_number AS vendor_account_number
             FROM purchase_orders po
             JOIN vendors v ON v.id = po.vendor_id
             WHERE po.id = ? LIMIT 1",
            [$id]
        ) ?: false;
    }

    public function getLines(int $poId): array
    {
        return Database::select(
            "SELECT pol.*, p.sku, p.name AS product_name, p.uom_code
             FROM purchase_order_lines pol
             LEFT JOIN products p ON p.id = pol.product_id
             WHERE pol.po_id = ?
             ORDER BY pol.sort_order ASC, pol.id ASC",
            [$poId]
        );
    }

    /** Lines of a PO, for receiving against. Alias kept explicit for readability. */
    public function getLinesById(int $poId): array
    {
        return $this->getLines($poId);
    }

    /**
     * Add to what has been received on a line.
     *
     * Deliberately NOT capped at the quantity ordered. receiveLine() used to cap, so a
     * delivery larger than the PO was silently trimmed to fit — the paperwork winning
     * over what was actually on the pallet. What arrived is what is recorded, and the
     * difference goes on the variance queue for somebody to resolve.
     */
    public function addReceivedQty(int $lineId, float $qty): void
    {
        Database::statement(
            "UPDATE purchase_order_lines SET qty_received = qty_received + ? WHERE id = ?",
            [$qty, $lineId]
        );
    }

    /** Recalculate a PO's status after receiving. */
    public function recalcStatusFor(int $poId): void
    {
        $this->recalcStatus(Database::connection(), $poId);
    }

    /**
     * PO lines where what arrived does not match what was ordered, unreviewed.
     *
     * Only lines that have actually had something received against them: a PO nobody has
     * started receiving is merely outstanding, not a discrepancy.
     */
    public function openVariances(): array
    {
        return Database::select("
            SELECT pol.id, pol.po_id, pol.qty_ordered, pol.qty_received, pol.description,
                   pol.qty_received - pol.qty_ordered AS difference,
                   po.po_number, po.status AS po_status,
                   v.name AS vendor_name,
                   p.sku, p.name AS product_name, p.uom_code,
                   (SELECT MAX(t.created_at) FROM inventory_transactions t
                     WHERE t.reference_type = 'purchase_order' AND t.reference_id = po.id
                       AND t.product_id = pol.product_id) AS last_received_at
            FROM purchase_order_lines pol
            JOIN purchase_orders po ON po.id = pol.po_id
            LEFT JOIN vendors  v ON v.id = po.vendor_id
            LEFT JOIN products p ON p.id = pol.product_id
            WHERE pol.qty_received > 0
              AND ABS(pol.qty_received - pol.qty_ordered) > 0.0001
              AND pol.variance_ack_at IS NULL
              AND po.status <> 'cancelled'
            ORDER BY po.po_number, pol.sort_order, pol.id
        ");
    }

    /**
     * Set a line's ordered quantity to what actually arrived, and close the variance.
     *
     * This is the normal outcome with TCC: a batch yields what it yields, so the PO
     * follows the receipt. The line total is recalculated, because the PO is what gets
     * billed and billing 600 cases when 570 were made would be wrong.
     */
    public function setOrderedToReceived(int $lineId, int $userId, string $note): void
    {
        Database::statement("
            UPDATE purchase_order_lines
            SET qty_ordered     = qty_received,
                line_total      = ROUND(qty_received * unit_cost, 2),
                variance_note   = ?,
                variance_ack_by = ?,
                variance_ack_at = NOW()
            WHERE id = ?
        ", [$note, $userId, $lineId]);

        $this->refreshTotalsAfterVariance($lineId);
    }

    /** Close a variance without changing the PO — the difference is expected. */
    public function acknowledgeVariance(int $lineId, int $userId, string $note): void
    {
        Database::statement("
            UPDATE purchase_order_lines
            SET variance_note = ?, variance_ack_by = ?, variance_ack_at = NOW()
            WHERE id = ?
        ", [$note, $userId, $lineId]);
    }

    /** Re-add the PO header totals from its lines, after a line quantity changed. */
    private function refreshTotalsAfterVariance(int $lineId): void
    {
        $line = Database::selectOne("SELECT po_id FROM purchase_order_lines WHERE id = ?", [$lineId]);

        if ($line === false) {
            return;
        }

        $poId = (int)$line['po_id'];

        Database::statement("
            UPDATE purchase_orders po
            SET po.subtotal     = (SELECT COALESCE(SUM(line_total), 0) FROM purchase_order_lines WHERE po_id = po.id),
                po.total_amount = (SELECT COALESCE(SUM(line_total), 0) FROM purchase_order_lines WHERE po_id = po.id)
                                  + po.tax_amount + po.shipping_cost
            WHERE po.id = ?
        ", [$poId]);

        $this->recalcStatus(Database::connection(), $poId);
    }

    public function nextPoNumber(): string
    {
        $row = Database::selectOne(
            "SELECT po_number FROM purchase_orders ORDER BY id DESC LIMIT 1"
        );

        if (!$row) return 'PO-00001';

        preg_match('/(\d+)$/', $row['po_number'], $m);
        $next = isset($m[1]) ? (int)$m[1] + 1 : 1;
        return 'PO-' . str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    }

    public function insert(array $data, array $lines, int $userId): int
    {
        $pdo = Database::connection();

        [$subtotal, $total] = $this->calcTotals($lines, $data);

        $pdo->prepare("
            INSERT INTO purchase_orders
                (po_number, vendor_id, status, order_date, expected_date,
                 ship_to_name, ship_to_address_1, ship_to_address_2,
                 ship_to_city, ship_to_state, ship_to_zip,
                 subtotal, tax_amount, shipping_cost, total_amount,
                 vendor_ref, memo, internal_notes, created_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ")->execute([
            $data['po_number'],
            (int)$data['vendor_id'],
            $data['status'] ?? 'draft',
            $data['order_date'],
            ($data['expected_date'] ?? '') ?: null,
            trim($data['ship_to_name'] ?? '') ?: null,
            trim($data['ship_to_address_1'] ?? '') ?: null,
            trim($data['ship_to_address_2'] ?? '') ?: null,
            trim($data['ship_to_city'] ?? '') ?: null,
            trim($data['ship_to_state'] ?? '') ?: null,
            trim($data['ship_to_zip'] ?? '') ?: null,
            $subtotal,
            (float)($data['tax_amount'] ?? 0),
            (float)($data['shipping_cost'] ?? 0),
            $total,
            trim($data['vendor_ref'] ?? '') ?: null,
            trim($data['memo'] ?? '') ?: null,
            trim($data['internal_notes'] ?? '') ?: null,
            $userId,
        ]);

        $poId = (int)$pdo->lastInsertId();
        $this->insertLines($pdo, $poId, $lines);

        return $poId;
    }

    public function update(int $id, array $data, array $lines): void
    {
        $pdo = Database::connection();

        [$subtotal, $total] = $this->calcTotals($lines, $data);

        $pdo->prepare("
            UPDATE purchase_orders SET
                vendor_id          = ?,
                status             = ?,
                order_date         = ?,
                expected_date      = ?,
                ship_to_name       = ?,
                ship_to_address_1  = ?,
                ship_to_address_2  = ?,
                ship_to_city       = ?,
                ship_to_state      = ?,
                ship_to_zip        = ?,
                subtotal           = ?,
                tax_amount         = ?,
                shipping_cost      = ?,
                total_amount       = ?,
                vendor_ref         = ?,
                memo               = ?,
                internal_notes     = ?
            WHERE id = ?
        ")->execute([
            (int)$data['vendor_id'],
            $data['status'],
            $data['order_date'],
            ($data['expected_date'] ?? '') ?: null,
            trim($data['ship_to_name'] ?? '') ?: null,
            trim($data['ship_to_address_1'] ?? '') ?: null,
            trim($data['ship_to_address_2'] ?? '') ?: null,
            trim($data['ship_to_city'] ?? '') ?: null,
            trim($data['ship_to_state'] ?? '') ?: null,
            trim($data['ship_to_zip'] ?? '') ?: null,
            $subtotal,
            (float)($data['tax_amount'] ?? 0),
            (float)($data['shipping_cost'] ?? 0),
            $total,
            trim($data['vendor_ref'] ?? '') ?: null,
            trim($data['memo'] ?? '') ?: null,
            trim($data['internal_notes'] ?? '') ?: null,
            $id,
        ]);

        // Replace lines
        $pdo->prepare("DELETE FROM purchase_order_lines WHERE po_id = ?")->execute([$id]);
        $this->insertLines($pdo, $id, $lines);
    }

    public function updateStatus(int $id, string $status): void
    {
        Database::connection()->prepare(
            "UPDATE purchase_orders SET status = ? WHERE id = ?"
        )->execute([$status, $id]);
    }

    // -------------------------------------------------------------------------

    private function getPoNumber(int $poId): string
    {
        return Database::selectOne(
            "SELECT po_number FROM purchase_orders WHERE id = ? LIMIT 1", [$poId]
        )['po_number'] ?? "#{$poId}";
    }

    private function calcTotals(array $lines, array $data): array
    {
        $subtotal = 0.0;
        foreach ($lines as $line) {
            $subtotal += (float)($line['qty_ordered'] ?? 0) * (float)($line['unit_cost'] ?? 0);
        }
        $total = $subtotal + (float)($data['tax_amount'] ?? 0) + (float)($data['shipping_cost'] ?? 0);
        return [round($subtotal, 2), round($total, 2)];
    }

    private function insertLines(\PDO $pdo, int $poId, array $lines): void
    {
        $stmt = $pdo->prepare("
            INSERT INTO purchase_order_lines
                (po_id, product_id, sort_order, description, qty_ordered, unit_cost, line_total)
            VALUES (?,?,?,?,?,?,?)
        ");

        foreach ($lines as $i => $line) {
            $qty   = (float)($line['qty_ordered'] ?? 0);
            $cost  = (float)($line['unit_cost'] ?? 0);
            if ($qty <= 0 && empty($line['description'])) continue;

            $stmt->execute([
                $poId,
                ($line['product_id'] ?? '') !== '' ? (int)$line['product_id'] : null,
                $i,
                trim($line['description'] ?? '') ?: null,
                $qty,
                $cost,
                round($qty * $cost, 2),
            ]);
        }
    }

    private function recalcStatus(\PDO $pdo, int $poId): void
    {
        $row = Database::selectOne(
            "SELECT
                SUM(qty_ordered) AS total_ordered,
                SUM(qty_received) AS total_received
             FROM purchase_order_lines WHERE po_id = ?",
            [$poId]
        );

        $ordered  = (float)($row['total_ordered'] ?? 0);
        $received = (float)($row['total_received'] ?? 0);

        if ($ordered <= 0) return;

        if ($received <= 0) {
            $status = 'sent';
        } elseif ($received < $ordered) {
            $status = 'partial';
        } else {
            $status = 'received';
        }

        $pdo->prepare("UPDATE purchase_orders SET status = ?, received_date = IF(? = 'received', CURDATE(), received_date) WHERE id = ?")
            ->execute([$status, $status, $poId]);
    }
}

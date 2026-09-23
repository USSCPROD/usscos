<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class SalesOrderRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function paginate(int $page, int $perPage, string $search, string $status, string $sort): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where  = ['1=1'];

        // Reps and distributors see only documents belonging to their own customers.
        [$scopeSql, $scopeParams] = \App\Services\AccessScope::customerOwnedConditionNamed('so');
        if ($scopeSql !== '') {
            $where[] = $scopeSql;
            $params  = array_merge($params, $scopeParams);
        }

        if ($search !== '') {
            $where[]       = '(so.so_number LIKE :s OR c.company_name LIKE :s2 OR so.po_number LIKE :s3)';
            $params[':s']  = "%{$search}%";
            $params[':s2'] = "%{$search}%";
            $params[':s3'] = "%{$search}%";
        }

        if ($status === 'open') {
            $where[] = "so.status NOT IN ('invoiced', 'cancelled')";
        } elseif ($status !== 'all') {
            $where[]           = 'so.status = :status';
            $params[':status'] = $status;
        }

        $orderMap = [
            'date_desc'   => 'so.order_date DESC',
            'date_asc'    => 'so.order_date ASC',
            'number_desc' => 'so.so_number DESC',
            'total_desc'  => 'so.total_amount DESC',
        ];
        $orderBy  = $orderMap[$sort] ?? 'so.order_date DESC';
        $whereStr = implode(' AND ', $where);

        $countStmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM sales_orders so JOIN customers c ON c.id = so.customer_id WHERE {$whereStr}"
        );
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sql = "
            SELECT so.id, so.so_number, so.order_date, so.requested_ship_date, so.status,
                   so.po_number, so.total_amount,
                   c.id AS customer_id, c.company_name,
                   u.first_name AS rep_first, u.last_name AS rep_last
            FROM sales_orders so
            JOIN customers c ON c.id = so.customer_id
            LEFT JOIN users u ON u.id = so.rep_id
            WHERE {$whereStr}
            ORDER BY {$orderBy}
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  \PDO::PARAM_INT);
        $stmt->execute();

        $lastPage = max(1, (int)ceil($total / $perPage));

        return [
            'data'         => $stmt->fetchAll(\PDO::FETCH_ASSOC),
            'total'        => $total,
            'current_page' => $page,
            'last_page'    => $lastPage,
            'per_page'     => $perPage,
            'from'         => $total > 0 ? $offset + 1 : 0,
            'to'           => min($offset + $perPage, $total),
        ];
    }

    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT so.*,
                   c.company_name, c.email, c.phone,
                   c.bill_address_1, c.bill_city, c.bill_state, c.bill_zip,
                   sv.name AS ship_via_name,
                   tr.name AS tax_rate_name, tr.rate AS tax_rate_pct,
                   cm.message AS customer_message,
                   u.first_name AS rep_first, u.last_name AS rep_last,
                   cb.first_name AS created_first, cb.last_name AS created_last
            FROM sales_orders so
            JOIN customers c ON c.id = so.customer_id
            LEFT JOIN ship_via sv ON sv.id = so.ship_via_id
            LEFT JOIN tax_rates tr ON tr.id = so.tax_rate_id
            LEFT JOIN customer_messages cm ON cm.id = so.customer_message_id
            LEFT JOIN users u ON u.id = so.rep_id
            LEFT JOIN users cb ON cb.id = so.created_by
            WHERE so.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        // A guessed id must not open another rep's order — treated as not found.
        if (!\App\Services\AccessScope::canSeeCustomer((int)$row['customer_id'])) {
            return null;
        }

        return $row;
    }

    public function getLineItems(int $soId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT li.*,
                   p.sku, p.name AS product_name,
                   u.code AS uom_code
            FROM sales_order_line_items li
            LEFT JOIN products p ON p.id = li.product_id
            LEFT JOIN units_of_measure u ON u.id = li.uom_id
            WHERE li.sales_order_id = :id
            ORDER BY li.sort_order, li.id
        ");
        $stmt->execute([':id' => $soId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function nextSoNumber(): string
    {
        $stmt = $this->pdo->query("SELECT MAX(CAST(so_number AS UNSIGNED)) FROM sales_orders");
        $max  = (int)$stmt->fetchColumn();
        return (string)($max + 1);
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO sales_orders
                (so_number, customer_id, created_by, rep_id, status, order_date,
                 requested_ship_date, po_number, ship_via_id, tax_rate_id,
                 customer_message_id, ship_name, ship_address_1, ship_address_2,
                 ship_city, ship_state, ship_zip, ship_phone,
                 subtotal, discount_amount, tax_amount, total_amount,
                 memo, internal_notes)
            VALUES
                (:so_number, :customer_id, :created_by, :rep_id, :status, :order_date,
                 :requested_ship_date, :po_number, :ship_via_id, :tax_rate_id,
                 :customer_message_id, :ship_name, :ship_address_1, :ship_address_2,
                 :ship_city, :ship_state, :ship_zip, :ship_phone,
                 :subtotal, :discount_amount, :tax_amount, :total_amount,
                 :memo, :internal_notes)
        ");
        $stmt->execute($data);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int|string $id, array $data): int
    {
        $stmt = $this->pdo->prepare("
            UPDATE sales_orders SET
                status              = :status,
                created_by          = :created_by,
                order_date          = :order_date,
                requested_ship_date = :requested_ship_date,
                po_number           = :po_number,
                rep_id              = :rep_id,
                ship_via_id         = :ship_via_id,
                tax_rate_id         = :tax_rate_id,
                customer_message_id = :customer_message_id,
                ship_name           = :ship_name,
                ship_address_1      = :ship_address_1,
                ship_address_2      = :ship_address_2,
                ship_city           = :ship_city,
                ship_state          = :ship_state,
                ship_zip            = :ship_zip,
                ship_phone          = :ship_phone,
                subtotal            = :subtotal,
                discount_amount     = :discount_amount,
                tax_amount          = :tax_amount,
                total_amount        = :total_amount,
                memo                = :memo,
                internal_notes      = :internal_notes
            WHERE id = :id
        ");
        $data[':id'] = $id;
        $stmt->execute($data);
        return $stmt->rowCount();
    }

    public function replaceLineItems(int $soId, array $lines): void
    {
        $del = $this->pdo->prepare("DELETE FROM sales_order_line_items WHERE sales_order_id = :id");
        $del->execute([':id' => $soId]);

        if (empty($lines)) return;

        $ins = $this->pdo->prepare("
            INSERT INTO sales_order_line_items
                (sales_order_id, product_id, quickbooks_item, description,
                 qty_ordered, uom_id, unit_price, discount_pct, taxable, line_total, sort_order)
            VALUES
                (:so_id, :product_id, :quickbooks_item, :description,
                 :qty_ordered, :uom_id, :unit_price, :discount_pct, :taxable, :line_total, :sort_order)
        ");

        foreach ($lines as $i => $line) {
            $ins->execute([
                ':so_id'          => $soId,
                ':product_id'     => $line['product_id']     ?: null,
                ':quickbooks_item'=> $line['quickbooks_item'] ?: null,
                ':description'    => $line['description']    ?: null,
                ':qty_ordered'    => $line['qty_ordered']    ?? 1,
                ':uom_id'         => $line['uom_id']         ?: null,
                ':unit_price'     => $line['unit_price']     ?? 0,
                ':discount_pct'   => $line['discount_pct']   ?? 0,
                ':taxable'        => (int)($line['taxable']  ?? 0),
                ':line_total'     => $line['line_total']     ?? 0,
                ':sort_order'     => $i + 1,
            ]);
        }
    }

    /**
     * Record that a quantity has now physically left the building.
     *
     * qty_shipped existed since migration 022 and was never written to, which meant a
     * partially shipped order could be shipped again and would re-invoice — and now
     * re-deduct — quantities that had already gone. Shipping works from
     * picked-minus-shipped, so this has to be kept up to date.
     */
    public function addShippedQty(int $lineId, float $qty): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE sales_order_line_items SET qty_shipped = qty_shipped + :qty WHERE id = :id"
        );
        $stmt->execute([':qty' => $qty, ':id' => $lineId]);
    }

    public function setStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare("UPDATE sales_orders SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $id]);
    }

    public function collectPayment(int $id, string $method, string $reference, float $amount, int $userId): void
    {
        // Update the SO
        $stmt = $this->pdo->prepare("
            UPDATE sales_orders SET
                status             = 'paid',
                payment_method     = :method,
                payment_reference  = :reference,
                payment_amount     = :amount,
                paid_at            = NOW()
            WHERE id = :id
        ");
        $stmt->execute([':method' => $method, ':reference' => $reference, ':amount' => $amount, ':id' => $id]);

        // Get the SO's customer_id
        $soStmt = $this->pdo->prepare("SELECT customer_id FROM sales_orders WHERE id = :id");
        $soStmt->execute([':id' => $id]);
        $customerId = (int)$soStmt->fetchColumn();

        // Write to payments table
        $payStmt = $this->pdo->prepare("
            INSERT INTO payments
                (customer_id, sales_order_id, payment_date, payment_method, reference_number, amount, created_by)
            VALUES
                (:customer_id, :so_id, CURDATE(), :method, :reference, :amount, :user_id)
        ");
        $payStmt->execute([
            ':customer_id' => $customerId,
            ':so_id'       => $id,
            ':method'      => $method,
            ':reference'   => $reference ?: null,
            ':amount'      => $amount,
            ':user_id'     => $userId,
        ]);
    }

    public function delete(int $id): int
    {
        $stmt = $this->pdo->prepare("DELETE FROM sales_orders WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount();
    }

    public function getShippingQueue(): array
    {
        return $this->pdo->query("
            SELECT so.id, so.so_number, so.order_date, so.requested_ship_date, so.status,
                   so.pick_status, so.pick_note,
                   so.po_number, so.total_amount, so.ship_via_id,
                   so.ship_address_1, so.ship_city, so.ship_state, so.ship_zip,
                   c.id AS customer_id, c.company_name, c.phone,
                   sv.name AS ship_via_name,
                   u.first_name AS rep_first, u.last_name AS rep_last
            FROM sales_orders so
            JOIN customers c ON c.id = so.customer_id
            LEFT JOIN ship_via sv ON sv.id = so.ship_via_id
            LEFT JOIN users u ON u.id = so.rep_id
            WHERE so.status IN ('confirmed', 'processing', 'partially_shipped', 'paid')
            ORDER BY so.requested_ship_date ASC, so.order_date ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);
    }

    // ------------------------------------------------------------- pick and verify

    /** Lines for the picking screen, with what has been picked so far. */
    public function getPickLines(int $salesOrderId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT li.id, li.product_id, li.description, li.quickbooks_item,
                   li.qty_ordered, li.qty_picked, li.qty_shipped, li.pick_note,
                   p.sku, p.name AS product_name, p.gtin12, p.gtin14,
                   p.units_per_case, p.uom_code
            FROM sales_order_line_items li
            LEFT JOIN products p ON p.id = li.product_id
            WHERE li.sales_order_id = ?
            ORDER BY li.sort_order, li.id
        ");
        $stmt->execute([$salesOrderId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Resolve a scanned code to a product.
     *
     * Tries the case barcode first, then the unit barcode, then the SKU — a scanner in
     * keyboard mode and someone typing a SKU by hand arrive here identically, which is
     * the fallback when a label is scuffed.
     *
     * `is_case` matters: a GTIN-14 is the outer carton, so one scan means a whole case
     * of units, not one unit.
     *
     * @return array{product:array,is_case:bool}|null
     */
    public function resolveScan(string $code): ?array
    {
        $code = trim($code);

        if ($code === '') {
            return null;
        }

        foreach ([['gtin14', true], ['gtin12', false], ['sku', false]] as [$column, $isCase]) {
            $stmt = $this->pdo->prepare(
                "SELECT id, sku, name, gtin12, gtin14, units_per_case, uom_code
                 FROM products WHERE {$column} = ? LIMIT 1"
            );
            $stmt->execute([$code]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($row) {
                return ['product' => $row, 'is_case' => $isCase];
            }
        }

        return null;
    }

    /** Set the picked quantity on a line, clamped to zero and below. */
    public function setPicked(int $lineId, float $qty, ?string $note = null): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE sales_order_line_items SET qty_picked = ?, pick_note = ? WHERE id = ?"
        );
        $stmt->execute([max(0, $qty), $note, $lineId]);
    }

    /**
     * Recalculate the order's pick status from its lines.
     *
     * `short` wins over `ready`: if anything is missing the order is short even when
     * every other line is complete, because that is the state someone has to act on.
     */
    public function refreshPickStatus(int $salesOrderId, ?int $userId = null): string
    {
        $lines = $this->getPickLines($salesOrderId);

        $anyPicked = $anyShort = false;
        $allDone   = true;

        foreach ($lines as $l) {
            $ordered = (float)$l['qty_ordered'];
            $picked  = (float)$l['qty_picked'];

            if ($picked > 0)               { $anyPicked = true; }
            if ($picked < $ordered)        { $allDone   = false; }
            if (!empty($l['pick_note']))   { $anyShort  = true; }
        }

        $status = match (true) {
            $anyShort             => 'short',
            $allDone && $anyPicked => 'ready',
            $anyPicked            => 'in_progress',
            default               => 'not_started',
        };

        $stmt = $this->pdo->prepare(
            "UPDATE sales_orders
             SET pick_status = ?,
                 picked_by = COALESCE(?, picked_by),
                 picked_at = CASE WHEN ? IN ('ready','short') THEN NOW() ELSE picked_at END
             WHERE id = ?"
        );
        $stmt->execute([$status, $userId, $status, $salesOrderId]);

        return $status;
    }

    /** Record what the shipping team says is missing, against the whole order. */
    public function reportShort(int $salesOrderId, string $note, ?int $userId = null): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE sales_orders SET pick_status = 'short', pick_note = ?, picked_by = COALESCE(?, picked_by), picked_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$note, $userId, $salesOrderId]);
    }

    public function getShipViaOptions(): array
    {
        return $this->pdo->query("SELECT id, name FROM ship_via WHERE is_active = 1 ORDER BY sort_order, name")
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getTaxRates(): array
    {
        return $this->pdo->query("SELECT id, name, rate FROM tax_rates WHERE is_active = 1 ORDER BY name")
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getCustomerMessages(): array
    {
        return $this->pdo->query("SELECT id, message FROM customer_messages WHERE is_active = 1 ORDER BY sort_order")
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getReps(): array
    {
        return $this->pdo->query("SELECT id, first_name, last_name, rep_code FROM users WHERE role = 'rep' AND is_active = 1 ORDER BY last_name")
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getActiveUsers(): array
    {
        return $this->pdo->query("SELECT id, first_name, last_name, role FROM users WHERE is_active = 1 ORDER BY last_name, first_name")
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getSummaryStats(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                COUNT(*) AS total_orders,
                SUM(CASE WHEN status IN ('draft','confirmed','processing','partially_shipped','paid') THEN 1 ELSE 0 END) AS open_count,
                SUM(CASE WHEN status IN ('draft','confirmed','processing','partially_shipped','paid') THEN total_amount ELSE 0 END) AS open_value,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS draft_count,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) AS paid_count
            FROM sales_orders
        ");
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}

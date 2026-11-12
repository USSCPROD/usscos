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
            // Drafts come out here too. The header counts open orders and this
            // lists them; if the two disagree, one of them is lying. A draft is
            // reachable through its own tab and through the Drafts card.
            $where[] = "so.status NOT IN ('draft', 'invoiced', 'cancelled')";
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
                   -- The order page needs the mode to decide whether a freight carrier
                   -- is even a question, and the carrier's name to show once locked.
                   COALESCE(sv.ship_mode, 'other') AS ship_mode,
                   fc.name AS freight_carrier_name,
                   tr.name AS tax_rate_name, tr.rate AS tax_rate_pct,
                   cm.message AS customer_message,
                   u.first_name AS rep_first, u.last_name AS rep_last,
                   cb.first_name AS created_first, cb.last_name AS created_last,
                   packer.first_name AS packed_first, packer.last_name AS packed_last
            FROM sales_orders so
            JOIN customers c ON c.id = so.customer_id
            LEFT JOIN ship_via sv ON sv.id = so.ship_via_id
            LEFT JOIN freight_carriers fc ON fc.id = so.freight_carrier_id
            LEFT JOIN tax_rates tr ON tr.id = so.tax_rate_id
            LEFT JOIN customer_messages cm ON cm.id = so.customer_message_id
            LEFT JOIN users u ON u.id = so.rep_id
            LEFT JOIN users cb ON cb.id = so.created_by
            LEFT JOIN users packer ON packer.id = so.packed_by
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

    /** The so_number for an id. The two differ, and it is the number people say out loud. */
    public function soNumber(int $id): string
    {
        $row = Database::selectOne('SELECT so_number FROM sales_orders WHERE id = ?', [$id]);

        return $row === false ? (string)$id : (string)$row['so_number'];
    }

    public function nextSoNumber(): string
    {
        $stmt = $this->pdo->query("SELECT MAX(CAST(so_number AS UNSIGNED)) FROM sales_orders");
        $max  = (int)$stmt->fetchColumn();
        return (string)($max + 1);
    }

    /**
     * Every column this insert binds, with what it means when a caller says nothing.
     *
     * Named parameters have to be supplied in full — PDO rejects the whole statement
     * with "Invalid parameter number" if one is missing, which is a confusing way to be
     * told that somebody added a column. That is exactly what happened: `channel` and
     * the marketplace fields went into the insert and `QuoteService::convertToSO` was
     * never updated, so converting an accepted quote into a sales order failed for
     * everyone until it was walked through on 12 Nov 2026. Adding
     * `freight_carrier_id` the same day made it seven missing instead of six.
     *
     * Defaulting here means a caller supplies what it knows about, a new column does
     * not break the callers that do not care about it, and the fallback for each one is
     * written down where the insert is rather than copied into every caller.
     */
    private const CREATE_DEFAULTS = [
        ':rep_id'                    => null,
        ':requested_ship_date'       => null,
        ':po_number'                 => null,
        ':ship_via_id'               => null,
        ':freight_carrier_id'        => null,
        ':tax_rate_id'               => null,
        ':customer_message_id'       => null,
        ':ship_name'                 => null,
        ':ship_address_1'            => null,
        ':ship_address_2'            => null,
        ':ship_city'                 => null,
        ':ship_state'                => null,
        ':ship_zip'                  => null,
        ':ship_phone'                => null,
        ':discount_amount'           => 0,
        ':tax_amount'                => 0,
        ':memo'                      => null,
        ':internal_notes'            => null,
        // An order with no stated channel came in the ordinary way: somebody keyed it.
        ':channel'                   => 'direct',
        ':channel_reference'         => null,
        ':marketplace_tax_collected' => 0,
        ':marketplace_fee'           => 0,
        ':marketplace_fee_source'    => null,
    ];

    public function create(array $data): int
    {
        $data = array_merge(self::CREATE_DEFAULTS, $data);

        $stmt = $this->pdo->prepare("
            INSERT INTO sales_orders
                (so_number, customer_id, created_by, rep_id, status, order_date,
                 requested_ship_date, po_number, ship_via_id, freight_carrier_id, tax_rate_id,
                 customer_message_id, ship_name, ship_address_1, ship_address_2,
                 ship_city, ship_state, ship_zip, ship_phone,
                 subtotal, discount_amount, tax_amount, total_amount,
                 memo, internal_notes,
                 channel, channel_reference, marketplace_tax_collected,
                 marketplace_fee, marketplace_fee_source)
            VALUES
                (:so_number, :customer_id, :created_by, :rep_id, :status, :order_date,
                 :requested_ship_date, :po_number, :ship_via_id, :freight_carrier_id, :tax_rate_id,
                 :customer_message_id, :ship_name, :ship_address_1, :ship_address_2,
                 :ship_city, :ship_state, :ship_zip, :ship_phone,
                 :subtotal, :discount_amount, :tax_amount, :total_amount,
                 :memo, :internal_notes,
                 :channel, :channel_reference, :marketplace_tax_collected,
                 :marketplace_fee, :marketplace_fee_source)
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
                freight_carrier_id  = :freight_carrier_id,
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
                internal_notes      = :internal_notes,
                channel             = :channel,
                channel_reference   = :channel_reference,
                marketplace_tax_collected = :marketplace_tax_collected,
                marketplace_fee          = :marketplace_fee,
                marketplace_fee_source   = :marketplace_fee_source
            WHERE id = :id
        ");
        $data[':id'] = $id;
        $stmt->execute($data);
        return $stmt->rowCount();
    }

    /**
     * Rewrite an order's lines, KEEPING what the warehouse has already done to them.
     *
     * This deletes and re-inserts, and for a long time the insert did not carry
     * qty_picked, qty_packed or qty_shipped - so every one reset to zero. Editing a
     * sales order quietly threw away the pick, and pick_status was left saying 'ready'
     * over lines with nothing picked. That is how SO 22 came to show a Picked badge and
     * an empty pack screen.
     *
     * The lost pick was the visible half. The dangerous half was qty_shipped:
     * shipAndInvoice bills qty_picked minus qty_shipped, so an order that had already
     * shipped and was then edited would look unshipped and BILL THE SAME GOODS AGAIN.
     *
     * Progress is carried across by product, because the incoming lines have no ids to
     * match on - an edit form sends what the order should now contain, not which row was
     * which. Where a product appears more than once the figures are handed out in order,
     * which is a guess, but a guess that keeps the total right beats a certainty of zero.
     * A line whose product was changed outright keeps nothing, which is correct: it is a
     * different thing to pick.
     */
    public function replaceLineItems(int $soId, array $lines): void
    {
        // What has already happened to these lines, before they are thrown away.
        $carry = [];

        foreach (Database::select(
            "SELECT product_id, description, qty_ordered, qty_picked, qty_packed, qty_shipped,
                    pick_note, pack_note
             FROM sales_order_line_items WHERE sales_order_id = ? ORDER BY sort_order, id",
            [$soId]
        ) as $old) {
            if ((float)$old['qty_picked'] <= 0 && (float)$old['qty_packed'] <= 0
                && (float)$old['qty_shipped'] <= 0 && ($old['pick_note'] ?? '') === ''
                && ($old['pack_note'] ?? '') === '') {
                continue;
            }

            $key = $old['product_id'] !== null
                ? 'p' . (int)$old['product_id']
                : 'd' . md5((string)$old['description']);

            $carry[$key][] = $old;
        }

        // A second copy that is not consumed as lines are matched, so the diff can still
        // see what a surviving line used to look like.
        $before = $carry;

        // A line that has shipped cannot simply be removed. The goods are with the
        // customer and an invoice bills them: taking the line off the order would leave
        // stock deducted, revenue booked and nothing recording why. That is a credit
        // memo, which USSCOS has, and not an edit.
        $keptKeys = [];

        foreach ($lines as $line) {
            $keptKeys[!empty($line['product_id'])
                ? 'p' . (int)$line['product_id']
                : 'd' . md5((string)($line['description'] ?? ''))] = true;
        }

        foreach ($carry as $key => $olds) {
            foreach ($olds as $old) {
                if ((float)$old['qty_shipped'] > 0 && !isset($keptKeys[$key])) {
                    throw new \RuntimeException(sprintf(
                        '%s has already shipped on this order, so it cannot be removed. '
                        . 'Raise a credit memo instead.',
                        $old['description'] ?: 'That line'
                    ));
                }
            }
        }

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

            // Hand back whatever this product had already had done to it. Capped at what
            // is now ordered, so cutting a line from 10 to 4 cannot leave 10 picked
            // against it - which would read as over-picked for ever.
            $key = !empty($line['product_id'])
                ? 'p' . (int)$line['product_id']
                : 'd' . md5((string)($line['description'] ?? ''));

            if (empty($carry[$key])) {
                continue;
            }

            $old     = array_shift($carry[$key]);
            $ordered = (float)($line['qty_ordered'] ?? 0);

            Database::statement(
                "UPDATE sales_order_line_items
                 SET qty_picked = ?, qty_packed = ?, qty_shipped = ?, pick_note = ?, pack_note = ?
                 WHERE id = ?",
                [
                    min($ordered, (float)$old['qty_picked']),
                    min($ordered, (float)$old['qty_packed']),
                    // NOT capped. qty_shipped records a physical event - the goods left
                    // the building and an invoice billed them. Trimming it to a reduced
                    // order quantity would rewrite that: cutting a line from 3 to 1
                    // after 3 shipped would leave the order claiming 1 went out while
                    // stock, the invoice and the customer's delivery all say 3. If the
                    // two now disagree, the order is what is wrong, and it should show
                    // the contradiction rather than hide it.
                    (float)$old['qty_shipped'],
                    $old['pick_note'] ?: null,
                    $old['pack_note'] ?: null,
                    (int)$this->pdo->lastInsertId(),
                ]
            );
        }

        // Anything still in $carry was picked and is no longer on the order, and anything
        // cut below what was picked leaves stock on a bench. Say so, in words, for
        // whoever next walks to that bay.
        $this->notePickedChange($soId, $carry, $lines, $before);

        // Status has to follow the quantities. Leaving it alone is exactly how an order
        // ends up wearing a Picked badge over an empty pack screen.
        $this->refreshPickStatus($soId);
    }

    /**
     * Leave a note for the warehouse about an order they had already picked.
     *
     * Plain text on purpose. The reader is a person holding a pallet, and "DSW cut from
     * 10 to 4 — 6 to go back" is what they need, not a structured diff they have to
     * interpret. Silence is the thing to avoid: the order looks tidy, the shelf does
     * not, and the difference turns up weeks later as a count that will not reconcile.
     *
     * @param array $carry   picked lines left over — these are no longer on the order
     * @param array $lines   what the order now contains
     * @param array $before  what it contained, keyed as $carry is
     */
    private function notePickedChange(int $soId, array $carry, array $lines, array $before): void
    {
        $notes = [];

        // Removed outright, with a pick against them.
        foreach ($carry as $leftover) {
            foreach ($leftover as $old) {
                if ((float)$old['qty_picked'] > 0) {
                    $notes[] = sprintf('%s removed — %s picked, put it back',
                        self::shortName($old['description']),
                        self::trimNum($old['qty_picked']));
                }
            }
        }

        // Cut below what was already picked.
        foreach ($lines as $line) {
            $key = !empty($line['product_id'])
                ? 'p' . (int)$line['product_id']
                : 'd' . md5((string)($line['description'] ?? ''));

            if (!isset($before[$key])) {
                continue;
            }

            $wasPicked  = (float)$before[$key][0]['qty_picked'];
            $wasOrdered = (float)$before[$key][0]['qty_ordered'];
            $nowOrdered = (float)($line['qty_ordered'] ?? 0);

            if ($wasPicked > $nowOrdered && $wasPicked > 0) {
                $notes[] = sprintf('%s cut from %s to %s — %s to go back',
                    self::shortName($line['description'] ?? $before[$key][0]['description']),
                    self::trimNum($wasOrdered), self::trimNum($nowOrdered),
                    self::trimNum($wasPicked - $nowOrdered));
            }
        }

        if ($notes === []) {
            return;
        }

        Database::statement(
            "UPDATE sales_orders SET picked_change_note = ?, picked_change_at = NOW() WHERE id = ?",
            [substr(implode('. ', $notes), 0, 600), $soId]
        );
    }

    /** Clear the warning once somebody has dealt with it. */
    public function acknowledgePickedChange(int $soId): void
    {
        Database::statement(
            "UPDATE sales_orders SET picked_change_note = NULL, picked_change_at = NULL WHERE id = ?",
            [$soId]
        );
    }

    private static function shortName(?string $d): string
    {
        $d = trim((string)$d);
        return $d === '' ? 'A line' : (strlen($d) > 40 ? substr($d, 0, 40) . '…' : $d);
    }

    private static function trimNum($n): string
    {
        return rtrim(rtrim(number_format((float)$n, 2), '0'), '.');
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

    public function setPoNumber(int $id, string $poNumber): void
    {
        $stmt = $this->pdo->prepare("UPDATE sales_orders SET po_number = :po WHERE id = :id");
        $stmt->execute([':po' => $poNumber, ':id' => $id]);
    }

    public function setStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare("UPDATE sales_orders SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $id]);
    }

    public function collectPayment(int $id, string $method, string $reference, float $amount, int $userId): void
    {
        $this->pdo->beginTransaction();
        try {
            if (FulfillmentRepository::installed()) {
                (new FulfillmentRepository())->order($id,true);
                if (FulfillmentRepository::orderIsManaged($id)) {
                    throw new \RuntimeException('Use the fulfillment invoice to record payment.');
                }
            }
            $this->collectPaymentWithin($id,$method,$reference,$amount,$userId);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            throw $e;
        }
    }

    private function collectPaymentWithin(int $id, string $method, string $reference, float $amount, int $userId): void
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
                   so.pick_status, so.pick_note, so.pack_status,
                   so.po_number, so.total_amount, so.ship_via_id,
                   so.ship_address_1, so.ship_city, so.ship_state, so.ship_zip,
                   c.id AS customer_id, c.company_name, c.phone,
                   sv.name AS ship_via_name, sv.ship_mode,
                   u.first_name AS rep_first, u.last_name AS rep_last,
                   -- What the row's own action should be, rather than a button that
                   -- sends somebody to a list their order is not on.
                   (SELECT i.id FROM invoices i WHERE i.sales_order_id = so.id
                     ORDER BY i.id DESC LIMIT 1)             AS invoice_id,
                   (SELECT i.invoice_number FROM invoices i WHERE i.sales_order_id = so.id
                     ORDER BY i.id DESC LIMIT 1)             AS invoice_number,
                   (SELECT COALESCE(SUM(li.qty_picked - COALESCE(li.qty_shipped,0)), 0)
                      FROM sales_order_line_items li WHERE li.sales_order_id = so.id) AS qty_to_ship,
                   (SELECT COALESCE(SUM(li.qty_picked - COALESCE(li.qty_packed,0)), 0)
                      FROM sales_order_line_items li WHERE li.sales_order_id = so.id) AS qty_unpacked
            FROM sales_orders so
            JOIN customers c ON c.id = so.customer_id
            LEFT JOIN ship_via sv ON sv.id = so.ship_via_id
            LEFT JOIN users u ON u.id = so.rep_id
            WHERE so.approved_at IS NOT NULL AND (
                so.status IN ('confirmed', 'processing', 'partially_shipped', 'paid')

                -- Released but not yet labelled. Invoicing an order moves it to
                -- 'invoiced', which used to drop it straight out of this queue - and the
                -- label is made AFTER the invoice, so the work vanished at exactly the
                -- point somebody still had a parcel to send. There is no screen to go
                -- back to and nothing to search for, because as far as shipping is
                -- concerned the order no longer exists.
                --
                -- It leaves again once a tracking number exists, which is the real
                -- finish line: the parcel has a label on it.
                OR (
                    so.status = 'invoiced'
                    AND EXISTS (SELECT 1 FROM invoices i WHERE i.sales_order_id = so.id)
                    AND NOT EXISTS (
                        SELECT 1 FROM shipment_tracking t
                        JOIN invoices i2 ON i2.id = t.invoice_id
                        WHERE i2.sales_order_id = so.id
                    )
                    -- ...unless nothing is going to be labelled. A customer collection
                    -- and a run on our own truck never get a tracking number, so waiting
                    -- for one kept them in the queue for ever. For those the invoice is
                    -- the finish line.
                    AND COALESCE(sv.ship_mode, 'other') NOT IN ('pickup', 'delivery')
                )
            )
            ORDER BY so.requested_ship_date ASC, so.order_date ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);
    }

    // ------------------------------------------------------------- pick and verify

    /** Lines for the picking screen, with what has been picked so far. */
    /**
     * Everything still owed across every open order, totalled by product.
     *
     * The warehouse walks the building once rather than once per order. "47 cases of
     * DSW" is one trip to one bay; the same 47 cases spread over twelve order sheets is
     * twelve trips past the same shelf.
     *
     * Each product carries the orders that want it, so the split is already done by the
     * time somebody is standing at the bay with a pallet. Working it out there, from
     * memory, is where the wrong paint ends up on the wrong order.
     *
     * OUTSTANDING, NOT ORDERED. The figure is qty_ordered minus whatever is already
     * picked, so a part-picked line shows only the remainder and a finished one drops off
     * the list. Re-running it after a pass is how the warehouse knows what is left.
     *
     * @return list<array> one row per product, each with an `orders` list
     */
    /**
     * @param string $bench 'parcel', 'freight', 'pickup', 'other' — or '' for all
     */
    public function pullList(string $bench = ''): array
    {
        // 'other' also catches an order whose carrier was never set, which belongs to
        // nobody's bench until somebody says how it ships.
        $benchWhere  = '';
        $benchParams = [];

        if ($bench !== '') {
            $benchWhere  = $bench === 'other'
                ? "AND (so.ship_via_id IS NULL OR COALESCE(sv.ship_mode,'other') = 'other')"
                : "AND so.ship_via_id IS NOT NULL AND sv.ship_mode = ?";
            $benchParams = $bench === 'other' ? [] : [$bench];
        }

        $rows = Database::select("
            SELECT li.product_id,
                   p.sku,
                   COALESCE(p.name, li.description)            AS product_name,
                   p.units_per_case,
                   p.uom_code,
                   so.id        AS sales_order_id,
                   so.so_number,
                   so.priority,
                   so.ship_by,
                   so.picked_change_note,
                   c.company_name,
                   li.id        AS line_id,
                   li.qty_ordered,
                   li.qty_picked,
                   (li.qty_ordered - COALESCE(li.qty_picked, 0)) AS qty_outstanding,
                   COALESCE(p.qty_on_hand, 0)                   AS qty_on_hand,
                   COALESCE(sv.ship_mode, 'other')              AS ship_mode
            FROM sales_order_line_items li
            JOIN sales_orders so ON so.id = li.sales_order_id
            JOIN customers c     ON c.id  = so.customer_id
            LEFT JOIN ship_via sv ON sv.id = so.ship_via_id
            LEFT JOIN products p ON p.id  = li.product_id
            WHERE so.status IN ('confirmed','processing','partially_shipped')
              AND so.approved_at IS NOT NULL
              AND (li.qty_ordered - COALESCE(li.qty_picked, 0)) > 0
              AND NOT " . self::NOT_GOODS . "
              {$benchWhere}
            ORDER BY p.sku, so.ship_by IS NULL, so.ship_by, so.id
        ", $benchParams);

        $byProduct = [];

        foreach ($rows as $r) {
            // Lines with no product still have to appear. A free-text line is a real
            // thing somebody has to go and find, and silently dropping it would send a
            // short order out with nobody having noticed.
            $key = $r['product_id'] !== null ? 'p' . $r['product_id'] : 'd' . md5((string)$r['product_name']);

            if (!isset($byProduct[$key])) {
                $byProduct[$key] = [
                    'product_id'     => $r['product_id'] !== null ? (int)$r['product_id'] : null,
                    'sku'            => $r['sku'],
                    'product_name'   => $r['product_name'],
                    'units_per_case' => $r['units_per_case'],
                    'uom_code'       => $r['uom_code'],
                    'qty_on_hand'    => (float)$r['qty_on_hand'],
                    'total_needed'   => 0.0,
                    'order_count'    => 0,
                    'most_urgent'    => null,
                    'orders'         => [],
                ];
            }

            $byProduct[$key]['total_needed'] += (float)$r['qty_outstanding'];
            $byProduct[$key]['order_count']++;
            $byProduct[$key]['orders'][] = [
                'sales_order_id' => (int)$r['sales_order_id'],
                'so_number'      => $r['so_number'],
                'company_name'   => $r['company_name'],
                'line_id'        => (int)$r['line_id'],
                'qty'            => (float)$r['qty_outstanding'],
                'qty_ordered'    => (float)$r['qty_ordered'],
                'qty_picked'     => (float)$r['qty_picked'],
                'priority'       => $r['priority'],
                'ship_by'        => $r['ship_by'],
                'picked_change_note' => $r['picked_change_note'],
            ];

            if ($r['ship_by'] !== null
                && ($byProduct[$key]['most_urgent'] === null || $r['ship_by'] < $byProduct[$key]['most_urgent'])) {
                $byProduct[$key]['most_urgent'] = $r['ship_by'];
            }
        }

        // Short of stock first. That is the decision the warehouse actually faces - if
        // there is not enough to go round, somebody has to choose who waits, and burying
        // it halfway down an alphabetical list means the choice gets made by accident.
        usort($byProduct, function ($a, $b) {
            $aShort = $a['qty_on_hand'] < $a['total_needed'];
            $bShort = $b['qty_on_hand'] < $b['total_needed'];

            if ($aShort !== $bShort) {
                return $aShort ? -1 : 1;
            }

            return strcmp((string)$a['sku'], (string)$b['sku']);
        });

        return array_values($byProduct);
    }

    /**
     * Orders changed after the warehouse had picked them.
     *
     * Kept apart from the pull list rather than hung off it, because the orders that most
     * need saying are the ones that have dropped off it: remove a picked line and the
     * rest of the order may be complete, so it has no outstanding work and vanishes from
     * the list — taking the warning with it. The paint is still on the bench.
     *
     * @return list<array>
     */
    public function pickedChanges(): array
    {
        return Database::select("
            SELECT so.id, so.so_number, so.picked_change_note, so.picked_change_at,
                   c.company_name
            FROM sales_orders so
            JOIN customers c ON c.id = so.customer_id
            WHERE so.picked_change_note IS NOT NULL
            ORDER BY so.picked_change_at DESC
        ");
    }

    /**
     * The shipping clerk's queue — every open order, and how close it is to shippable.
     *
     * Her decision is full, partial, or hold, and it rests on one comparison: what was
     * ordered against what is actually on the shelf for it. So both figures are here,
     * per order, rather than requiring her to open each one to find out.
     *
     * lines_short counts lines where the pick is incomplete AND there is not enough on
     * hand to finish it. A line that is merely unpicked is not short - somebody just has
     * not walked to the bay yet - and conflating the two would have her holding orders
     * that could ship today.
     *
     * @return list<array>
     */
    /**
     * Orders the clerk has not yet let out to the warehouse — gate one.
     *
     * Confirmed by sales and nothing more. The warehouse cannot see these at all, so
     * the only way an order becomes work is somebody here deciding it should.
     *
     * @return array<int,array<string,mixed>>
     */
    public function approvalQueue(): array
    {
        return Database::select("
            SELECT so.id, so.so_number, so.order_date, so.ship_by, so.priority,
                   so.po_number, so.total_amount, so.memo, so.pick_note,
                   so.ship_via_id, so.ship_city, so.ship_state,
                   c.id AS customer_id, c.company_name,
                   sv.name AS ship_via_name,
                   COALESCE(sv.ship_mode, 'other') AS ship_mode,

                   COUNT(li.id)            AS line_count,
                   SUM(li.qty_ordered)     AS qty_ordered,

                   -- What the system thinks can be filled today. Advisory only: the
                   -- counts are not trusted yet, which is exactly why the quantity
                   -- decision belongs at gate two, after somebody has walked the floor.
                   SUM(CASE
                         WHEN COALESCE(p.qty_on_hand, 0) < li.qty_ordered
                         THEN 1 ELSE 0 END) AS lines_tight

            FROM sales_orders so
            JOIN customers c      ON c.id = so.customer_id
            LEFT JOIN ship_via sv ON sv.id = so.ship_via_id
            LEFT JOIN sales_order_line_items li ON li.sales_order_id = so.id
            LEFT JOIN products p  ON p.id = li.product_id
            WHERE so.approved_at IS NULL
              AND so.status IN ('confirmed', 'paid')
            GROUP BY so.id
            ORDER BY FIELD(so.priority,'urgent','high','normal'),
                     so.ship_by IS NULL, so.ship_by, so.id
        ");
    }

    /**
     * Why this order may not be invoiced yet, or null when it may.
     *
     * Packing is the scan-to-verify step, and it was advisory: an order could go
     * straight from Picked to invoiced with the box never checked, which is the error
     * the whole bench exists to catch.
     *
     * The test is the quantities, not the status word. pack_status is not reset when an
     * order part-ships, so an order packed once and then picked again would still read
     * 'verified' while carrying goods nobody has looked at. Comparing packed against
     * picked catches that on its own.
     *
     * 'mismatch' passes deliberately: somebody looked, found the box did not match the
     * pick, said so in writing and chose to ship it. That is a decision, not a gap.
     */
    public function packGate(int $soId): ?string
    {
        $order = Database::selectOne(
            "SELECT pack_status FROM sales_orders WHERE id = ?", [$soId]
        );

        if ($order === false) {
            return 'That order no longer exists.';
        }

        if (($order['pack_status'] ?? '') === 'mismatch') {
            return null;
        }

        $row = Database::selectOne("
            SELECT COALESCE(SUM(li.qty_picked), 0)  AS picked,
                   COALESCE(SUM(li.qty_packed), 0)  AS packed
            FROM sales_order_line_items li
            LEFT JOIN products p ON p.id = li.product_id
            WHERE li.sales_order_id = ?
              AND NOT " . self::NOT_GOODS . "
        ", [$soId]) ?: [];

        $picked = (float)($row['picked'] ?? 0);
        $packed = (float)($row['packed'] ?? 0);

        if ($picked <= 0) {
            return 'Nothing has been picked on this order yet.';
        }

        if ($packed + 0.0001 < $picked) {
            $n = fn (float $v) => rtrim(rtrim(number_format($v, 2), '0'), '.');

            return sprintf(
                'Pack and verify it first — %s of %s checked into the box. '
                . 'Scan the rest, or use "Confirm without scanning" on the pack screen.',
                $n($packed),
                $n($picked)
            );
        }

        return null;
    }

    /** Let an order out to the warehouse, optionally settling the carrier on the way. */
    public function approve(int $soId, int $userId, ?int $shipViaId = null): void
    {
        if ($shipViaId !== null) {
            Database::statement(
                "UPDATE sales_orders SET ship_via_id = ? WHERE id = ?", [$shipViaId, $soId]
            );
        }

        Database::statement("
            UPDATE sales_orders
            SET approved_at = NOW(), approved_by = ?
            WHERE id = ? AND approved_at IS NULL
        ", [$userId, $soId]);
    }

    /** Pull an order back off the floor. Only while nothing has been picked. */
    public function unapprove(int $soId): bool
    {
        $row = Database::selectOne("
            SELECT COALESCE(SUM(li.qty_picked), 0) AS picked
            FROM sales_order_line_items li WHERE li.sales_order_id = ?
        ", [$soId]);

        if ((float)($row['picked'] ?? 0) > 0) {
            return false;
        }

        Database::statement(
            "UPDATE sales_orders SET approved_at = NULL, approved_by = NULL WHERE id = ?",
            [$soId]
        );

        return true;
    }

    public function releaseQueue(): array
    {
        return Database::select("
            SELECT so.id,
                   so.so_number,
                   so.status,
                   so.priority,
                   so.ship_by,
                   so.pick_status,
                   so.pick_note,
                   so.channel,
                   so.total_amount,
                   c.company_name,

                   COUNT(li.id)                                        AS line_count,
                   SUM(li.qty_ordered)                                 AS qty_ordered,
                   SUM(COALESCE(li.qty_picked, 0))                     AS qty_picked,
                   SUM(COALESCE(li.qty_shipped, 0))                    AS qty_shipped,

                   SUM(CASE WHEN COALESCE(li.qty_picked,0) >= li.qty_ordered THEN 1 ELSE 0 END) AS lines_complete,

                   -- Short means it CANNOT be finished, not merely that it has not been.
                   SUM(CASE
                         WHEN COALESCE(li.qty_picked,0) < li.qty_ordered
                          AND COALESCE(p.qty_on_hand,0) < (li.qty_ordered - COALESCE(li.qty_picked,0))
                         THEN 1 ELSE 0 END)                            AS lines_short,

                   (SELECT COUNT(*) FROM invoices i WHERE i.sales_order_id = so.id) AS invoice_count,
                   so.pack_status,
                   SUM(COALESCE(li.qty_packed, 0))                     AS qty_packed,
                   sv.name AS ship_via_name,
                   COALESCE(sv.ship_mode, 'other') AS ship_mode

            FROM sales_orders so
            JOIN customers c             ON c.id  = so.customer_id
            LEFT JOIN ship_via sv        ON sv.id = so.ship_via_id
            -- Charges joined out: a shipping line inflates the ordered count and makes
            -- a finished order read as unfinished work.
            LEFT JOIN sales_order_line_items li
                   ON li.sales_order_id = so.id AND NOT " . self::NOT_GOODS . "
            LEFT JOIN products p         ON p.id  = li.product_id
            WHERE so.status IN ('confirmed','processing','partially_shipped')
              AND so.approved_at IS NOT NULL
            GROUP BY so.id
            ORDER BY FIELD(so.priority,'urgent','high','normal'),
                     so.ship_by IS NULL, so.ship_by, so.id
        ");
    }

    /**
     * Nothing anybody picks or packs. Shipping, handling, freight, a delivery charge,
     * a discount: a line on the order and money on the invoice, but not an object in
     * the building. Listing them at the bench asks somebody to go and find a thing
     * that does not exist, and then to count it into a box.
     *
     * Matched on the product's item type where there is a product, and on the wording
     * where there is not - a typed 'Shipping and Handling' line has no product to ask.
     * A free-text line that is NOT a charge still appears, because that is a real thing
     * somebody has to go and find.
     */
    private const NOT_GOODS = "
        (
            (li.product_id IS NOT NULL
             AND COALESCE(
                   (SELECT np.item_type FROM products np WHERE np.id = li.product_id), ''
                 ) NOT IN ('inventory_part','inventory_assembly'))
         OR (li.product_id IS NULL
             AND CONCAT(COALESCE(li.quickbooks_item,''), ' ', COALESCE(li.description,''))
                 REGEXP '(^|[^a-z])(shipping|handling|freight|delivery|surcharge|discount|fuel charge|service charge)([^a-z]|$)')
        )
    ";

    /**
     * True when a line is a charge rather than goods.
     *
     * The same question NOT_GOODS asks in SQL, for the places that have the row in
     * hand already. Both have to agree: a charge the bench cannot see must also be a
     * charge the shortfall arithmetic ignores, or an order carrying shipping is
     * partially shipped for ever because nobody can pick "Shipping and Handling".
     */
    /** The NOT_GOODS test, for queries built outside this class. */
    public static function notGoodsSql(): string
    {
        return self::NOT_GOODS;
    }

    public static function isChargeLine(array $line): bool
    {
        if (($line['product_id'] ?? null) !== null) {
            return !in_array($line['item_type'] ?? '', ['inventory_part', 'inventory_assembly'], true)
                && array_key_exists('item_type', $line);
        }

        $text = trim(((string)($line['quickbooks_item'] ?? '')) . ' ' . ((string)($line['description'] ?? '')));

        return (bool)preg_match(
            '~(^|[^a-z])(shipping|handling|freight|delivery|surcharge|discount|fuel charge|service charge)([^a-z]|$)~i',
            $text
        );
    }

    public function getPickLines(int $salesOrderId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT li.id, li.product_id, li.description, li.quickbooks_item,
                   li.qty_ordered, li.qty_picked, li.qty_shipped, li.pick_note,
                   li.qty_packed, li.pack_note,
                   p.sku, p.name AS product_name, p.gtin12, p.gtin14,
                   p.units_per_case, p.uom_code
            FROM sales_order_line_items li
            LEFT JOIN products p ON p.id = li.product_id
            WHERE li.sales_order_id = ?
              AND NOT " . self::NOT_GOODS . "
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

    public function setPacked(int $lineId, float $qty, ?string $note = null): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE sales_order_line_items SET qty_packed = ?, pack_note = ? WHERE id = ?"
        );
        $stmt->execute([max(0, $qty), $note, $lineId]);
    }

    /**
     * Recalculate the order's pack status from its lines.
     *
     * Compared against what was PICKED, not what was ordered. A deliberate short pick is
     * not a packing error — four of ten picked means four in the box is correct — and
     * comparing against the order would flag every short ship and teach people to ignore
     * the warning.
     *
     * `verified` requires every picked line to be matched exactly. Anything else in
     * progress stays in progress; the mismatch state is only set deliberately, by somebody
     * finishing a box that does not match.
     */
    public function refreshPackStatus(int $salesOrderId, ?int $userId = null): string
    {
        $lines = $this->getPickLines($salesOrderId);

        $anyPacked = false;
        $allMatch  = true;
        $anyPicked = false;

        foreach ($lines as $l) {
            $picked = (float)$l['qty_picked'];
            $packed = (float)$l['qty_packed'];

            if ($packed > 0) { $anyPacked = true; }
            if ($picked > 0) { $anyPicked = true; }

            if (abs($packed - $picked) > 0.0001) { $allMatch = false; }
        }

        $status = match (true) {
            $anyPicked && $allMatch && $anyPacked => 'verified',
            $anyPacked                           => 'in_progress',
            default                              => 'not_started',
        };

        $stmt = $this->pdo->prepare(
            "UPDATE sales_orders
             SET pack_status = ?,
                 packed_by = COALESCE(?, packed_by),
                 packed_at = CASE WHEN ? = 'verified' THEN NOW() ELSE packed_at END
             WHERE id = ?"
        );
        $stmt->execute([$status, $userId, $status, $salesOrderId]);

        return $status;
    }

    /** Somebody sealed a box that did not match the pick, and said why. */
    /**
     * Mark every picked line as packed, and the order as confirmed by a person.
     *
     * Deliberately not routed through refreshPackStatus: that derives 'verified' from
     * the quantities alone and cannot tell how they got there, so calling it here would
     * relabel an unscanned confirmation as a scan-verified one the moment it was saved.
     */
    public function confirmPackedAsPicked(int $salesOrderId, ?int $userId = null): void
    {
        $this->pdo->beginTransaction();

        try {
            $this->pdo->prepare(
                "UPDATE sales_order_line_items
                 SET qty_packed = qty_picked
                 WHERE sales_order_id = ? AND qty_picked > 0"
            )->execute([$salesOrderId]);

            $this->pdo->prepare(
                "UPDATE sales_orders
                 SET pack_status = 'confirmed',
                     packed_by   = COALESCE(?, packed_by),
                     packed_at   = NOW()
                 WHERE id = ?"
            )->execute([$userId, $salesOrderId]);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function recordPackMismatch(int $salesOrderId, string $note, ?int $userId = null): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE sales_orders
             SET pack_status = 'mismatch', pack_note = ?, packed_by = COALESCE(?, packed_by), packed_at = NOW()
             WHERE id = ?"
        );
        $stmt->execute([$note, $userId, $salesOrderId]);
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
        // ship_mode comes along so the form knows whether a FedEx quote is even a
        // sensible thing to offer. Nobody can ask FedEx what an LTL pallet costs.
        // carrier_code as well as ship_mode: the quote button is a FedEx button, not a
        // parcel button. We have a FedEx account and no other, so offering it against
        // UPS would price a UPS shipment at FedEx's rates and look authoritative doing
        // it — a wrong number is worse than no number.
        return $this->pdo->query("
            SELECT id, name,
                   COALESCE(ship_mode, 'other')  AS ship_mode,
                   COALESCE(carrier_code, '')    AS carrier_code
            FROM ship_via WHERE is_active = 1 ORDER BY sort_order, name
        ")->fetchAll(\PDO::FETCH_ASSOC);
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
                -- Drafts are not open orders. They were counted here, so the list
                -- header claimed 27 open orders worth 31,724 dollars while the Drafts card beside
                -- it said two of those were not visible to shipping. An order nobody
                -- downstream can act on is not open, and money nothing will collect
                -- does not belong in a value total. Drafts keep their own count.
                SUM(CASE WHEN status IN ('confirmed','processing','partially_shipped','paid') THEN 1 ELSE 0 END) AS open_count,
                SUM(CASE WHEN status IN ('confirmed','processing','partially_shipped','paid') THEN total_amount ELSE 0 END) AS open_value,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS draft_count,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) AS paid_count
            FROM sales_orders
        ");
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}

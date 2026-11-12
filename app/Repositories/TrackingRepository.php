<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/** Tracking numbers against an invoice, and the carrier links that make them useful. */
class TrackingRepository
{
    public function add(array $d): int
    {
        Database::statement("
            INSERT INTO shipment_tracking
                (invoice_id, sales_order_id, ship_via_id, carrier_name, freight_carrier_id,
                 tracking_number, tracking_type, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $d['invoice_id']     ?? null,
            $d['sales_order_id'] ?? null,
            $d['ship_via_id']    ?? null,
            ($d['carrier_name'] ?? '') !== '' ? $d['carrier_name'] : null,
            $d['freight_carrier_id'] ?? null,
            $d['tracking_number'],
            $d['tracking_type'] ?? 'parcel',
            $d['notes']   ?? null,
            $d['created_by'] ?? null,
        ]);

        return (int)Database::connection()->lastInsertId();
    }

    /**
     * Every tracking number on an invoice, from wherever it is actually stored.
     *
     * Tracking has two homes. These rows are the real record - carrier, type, label -
     * and `invoices.tracking_number` is an older single column still written by the
     * Tracking # field on the invoice form and by the import.
     *
     * Asking only the rows made three screens disagree with the invoice in front of
     * them: Awaiting Review said None yet, the tracking panel said there were none,
     * and approving said `no_tracking` and sent the customer nothing - all while the
     * invoice displayed 794905225741. The fallback lives here, in the one method they
     * all call, rather than in each of them.
     *
     * Collapsing the two stores into one is cutover work; see docs/TESTING_LOG.md.
     */
    public function forInvoice(int $invoiceId): array
    {
        $rows = Database::select("
            SELECT t.*, sv.name AS carrier, sv.tracking_url_template
            FROM shipment_tracking t
            LEFT JOIN ship_via sv ON sv.id = t.ship_via_id
            WHERE t.invoice_id = ?
            ORDER BY t.id
        ", [$invoiceId]);

        foreach ($rows as &$r) {
            $r['url'] = $this->url($r['tracking_url_template'] ?? null, (string)$r['tracking_number']);
        }
        unset($r);

        if ($rows !== []) {
            return $rows;
        }

        // Nothing at the bench. Fall back to the invoice's own column, shaped like a
        // row so callers need no special case. It carries no ship_via, so there is no
        // carrier and no tracking URL - which is the truth about a number somebody
        // typed onto an invoice.
        $legacy = Database::selectOne(
            'SELECT tracking_number FROM invoices WHERE id = ?',
            [$invoiceId]
        );

        $number = $legacy === false ? '' : trim((string)($legacy['tracking_number'] ?? ''));

        if ($number === '') {
            return [];
        }

        return [[
            'id'              => null,
            'invoice_id'      => $invoiceId,
            'tracking_number' => $number,
            'tracking_type'   => 'parcel',
            'ship_via_id'     => null,
            'carrier'         => null,
            'label_path'      => null,
            'url'             => null,
            'from_invoice'    => true,
        ]];
    }

    public function exists(int $invoiceId, string $number): bool
    {
        $row = Database::selectOne(
            "SELECT id FROM shipment_tracking WHERE invoice_id = ? AND tracking_number = ?",
            [$invoiceId, $number]
        );

        return $row !== false;
    }

    public function remove(int $id): void
    {
        Database::statement("DELETE FROM shipment_tracking WHERE id = ?", [$id]);
    }

    /** Find a shipment from a tracking number — the "look it up if needed" case. */
    public function search(string $number): array
    {
        return Database::select("
            SELECT t.tracking_number, t.tracking_type, t.created_at,
                   sv.name AS carrier, sv.tracking_url_template,
                   i.id AS invoice_id, i.invoice_number, i.invoice_date, i.ship_date,
                   c.company_name, so.so_number
            FROM shipment_tracking t
            LEFT JOIN invoices i      ON i.id = t.invoice_id
            LEFT JOIN customers c     ON c.id = i.customer_id
            LEFT JOIN sales_orders so ON so.id = i.sales_order_id
            LEFT JOIN ship_via sv     ON sv.id = t.ship_via_id
            WHERE t.tracking_number LIKE ?
            ORDER BY t.id DESC
            LIMIT 25
        ", ['%' . $number . '%']);
    }

    public function shipViaByName(?string $name): ?array
    {
        if ($name === null || trim($name) === '') {
            return null;
        }

        $row = Database::selectOne("SELECT * FROM ship_via WHERE name = ? LIMIT 1", [trim($name)]);

        return $row === false ? null : $row;
    }

    public function markEmail(int $invoiceId, string $status, ?string $to): void
    {
        Database::statement("
            UPDATE invoices
            SET shipment_email_status = ?,
                shipment_email_to     = ?,
                shipment_email_sent_at = CASE WHEN ? = 'sent' THEN NOW() ELSE shipment_email_sent_at END
            WHERE id = ?
        ", [$status, $to, $status, $invoiceId]);
    }

    public function url(?string $template, string $number): ?string
    {
        if ($template === null || trim($template) === '') {
            return null;
        }

        return str_replace('{tracking}', rawurlencode($number), $template);
    }
}

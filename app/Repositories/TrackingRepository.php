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
                (invoice_id, sales_order_id, ship_via_id, tracking_number, tracking_type, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ", [
            $d['invoice_id']     ?? null,
            $d['sales_order_id'] ?? null,
            $d['ship_via_id']    ?? null,
            $d['tracking_number'],
            $d['tracking_type'] ?? 'parcel',
            $d['notes']   ?? null,
            $d['created_by'] ?? null,
        ]);

        return (int)Database::connection()->lastInsertId();
    }

    /** Every number on an invoice, with its carrier and a ready-made link. */
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

        return $rows;
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

<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/** Tax jurisdictions, the states we collect in, and how an address resolves to a rate. */
class TaxRepository
{
    /** Do we collect in this state at all? Having a rate is not the same as owing tax. */
    public function collectsIn(string $stateCode): bool
    {
        $row = Database::selectOne(
            "SELECT collects FROM tax_nexus_states WHERE state_code = ?",
            [strtoupper(trim($stateCode))]
        );

        return $row !== false && (int)$row['collects'] === 1;
    }

    public function nexusStates(): array
    {
        return Database::select("SELECT * FROM tax_nexus_states ORDER BY state_code");
    }

    /**
     * The jurisdiction for a delivery ZIP, as at a given date.
     *
     * Returns the ambiguity flag with it, because a ZIP spanning two counties gives a rate
     * that is a best guess and the invoice should say so rather than look certain.
     */
    public function byZip(string $zip, string $onDate): ?array
    {
        $zip = substr(preg_replace('/\D/', '', $zip) ?? '', 0, 5);

        if (strlen($zip) !== 5) {
            return null;
        }

        $row = Database::selectOne("
            SELECT tr.*, z.is_ambiguous
            FROM tax_zip_jurisdictions z
            JOIN tax_rates tr ON tr.id = z.tax_rate_id
            WHERE z.zip = ?
              AND tr.is_active = 1
              AND tr.effective_from <= ?
              AND (tr.effective_to IS NULL OR tr.effective_to >= ?)
            LIMIT 1
        ", [$zip, $onDate, $onDate]);

        return $row === false ? null : $row;
    }

    /** The fallback rate for a state when no ZIP mapping exists yet. */
    public function stateDefault(string $stateCode, string $onDate): ?array
    {
        $row = Database::selectOne("
            SELECT * FROM tax_rates
            WHERE state_code = ?
              AND is_active = 1
              AND effective_from <= ?
              AND (effective_to IS NULL OR effective_to >= ?)
            ORDER BY is_state_default DESC, id
            LIMIT 1
        ", [strtoupper(trim($stateCode)), $onDate, $onDate]);

        return $row === false ? null : $row;
    }

    /**
     * Tax owed, broken out by jurisdiction — the shape the Georgia return wants.
     *
     * Reads what was frozen onto each invoice rather than recalculating, so a rate change
     * today cannot alter what last quarter's return says.
     */
    public function liabilityByJurisdiction(string $from, string $to): array
    {
        return Database::select("
            SELECT COALESCE(tr.state_code, i.ship_state, '—') AS state_code,
                   COALESCE(tr.county, '(no county recorded)')  AS county,
                   COALESCE(tr.name, 'Unassigned')              AS jurisdiction,
                   i.tax_rate_applied,
                   COUNT(*)                   AS invoices,
                   SUM(i.taxable_subtotal)    AS taxable_sales,
                   SUM(i.tax_amount)          AS tax_collected
            FROM invoices i
            LEFT JOIN tax_rates tr ON tr.id = i.tax_rate_id
            WHERE i.tax_source = 'usscos'
              AND i.status <> 'void'
              AND i.invoice_date BETWEEN ? AND ?
            GROUP BY tr.state_code, tr.county, tr.name, i.tax_rate_applied
            ORDER BY state_code, county
        ", [$from, $to]);
    }

    /**
     * Marketplace sales, which the return reports and then deducts.
     *
     * Separate from the liability figures on purpose: this is Amazon's money, and the two
     * must never be added together by accident.
     */
    public function marketplaceSummary(string $from, string $to): array
    {
        return Database::select("
            SELECT COALESCE(i.ship_state, '—') AS state_code,
                   COUNT(*)                          AS invoices,
                   SUM(i.subtotal)                   AS sales,
                   SUM(i.marketplace_tax_collected)  AS tax_collected_by_marketplace
            FROM invoices i
            WHERE i.tax_source = 'marketplace'
              AND i.status <> 'void'
              AND i.invoice_date BETWEEN ? AND ?
            GROUP BY i.ship_state
            ORDER BY sales DESC
        ", [$from, $to]);
    }

    /**
     * Sales by destination state, for watching economic-nexus thresholds.
     *
     * The point is to see a threshold coming rather than discover it afterwards. Whether
     * marketplace sales count toward a state's threshold varies by state, so they are
     * shown separately rather than folded in.
     */
    public function salesByState(string $from, string $to): array
    {
        return Database::select("
            SELECT COALESCE(NULLIF(i.ship_state, ''), '—') AS state_code,
                   COUNT(*) AS invoices,
                   SUM(CASE WHEN i.tax_source = 'marketplace' THEN 0 ELSE i.subtotal END) AS direct_sales,
                   SUM(CASE WHEN i.tax_source = 'marketplace' THEN i.subtotal ELSE 0 END) AS marketplace_sales,
                   SUM(i.subtotal) AS total_sales,
                   MAX(n.collects) AS collects
            FROM invoices i
            LEFT JOIN tax_nexus_states n ON n.state_code = i.ship_state
            WHERE i.status <> 'void'
              AND i.invoice_date BETWEEN ? AND ?
            GROUP BY i.ship_state
            ORDER BY total_sales DESC
        ", [$from, $to]);
    }

    /** Rates nobody has verified — surfaced rather than quietly trusted. */
    public function unverifiedRates(): array
    {
        return Database::select("
            SELECT * FROM tax_rates
            WHERE needs_review = 1 AND is_active = 1
            ORDER BY state_code, county
        ");
    }
}

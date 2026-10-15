<?php

declare(strict_types=1);

/**
 * What the QuickBooks bridge is allowed to take.
 *
 * CHANNELS is the pilot gate. While it holds a non-empty list, only records whose sales
 * order came from one of those channels are ever offered to the bridge — everything else
 * stays pending and untouched, exactly as it is today.
 *
 * It is a gate rather than a setting screen on purpose. Opening the sync to every invoice
 * is a decision somebody should make deliberately, on the server, not something reachable
 * by a mis-click. Empty means no gate at all, which is the eventual destination and not
 * where we are starting.
 *
 * An invoice with NO sales order - a counter sale, a manual correction - has no channel,
 * so it is excluded while the gate is on. That is correct for the pilot: those are the
 * records whose double entry we are not ready to stop yet.
 */
return [
    'channels' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string)($_ENV['QB_SYNC_CHANNELS'] ?? 'website'))
    ))),

    /*
     * Sales tax.
     *
     * QuickBooks will not accept a tax figure. InvoiceAdd has no field for one - it holds
     * a reference to a Sales Tax Item carrying its own fixed rate, and QuickBooks
     * multiplies that rate by the taxable lines itself. Whatever USSCOS calculated is
     * invisible to it.
     *
     * So we use the pattern Intuit documents for invoices no single tax item covers: point
     * every invoice at a ZERO PERCENT item, and send the tax as an ordinary line. The
     * arithmetic then agrees, and USSCOS stays the only thing in the building that decides
     * what the tax is.
     *
     * THE ALTERNATIVE, AND WHY NOT. A tax item per jurisdiction would keep QuickBooks'
     * own Sales Tax Liability report working. It would also mean maintaining 259 Georgia
     * counties by hand inside QuickBooks and revising them every quarter - the exact
     * duplication the automated rate refresh exists to prevent. Confirmed with Chip that
     * nobody will be working in QuickBooks once this is live, so that report has no
     * reader. Filing comes from /accounting/tax.
     *
     * BOTH ITEMS MUST EXIST IN THE COMPANY FILE before the first push, or every invoice
     * fails with error 3140 and sits in the queue:
     *
     *   tax_item       a Sales Tax Item with a rate of 0%
     *   tax_line_item  an Other Charge item mapped to SALES TAX PAYABLE - not to income,
     *                  or the P&L overstates revenue by the whole year's sales tax
     */
    'tax_item'      => (string)($_ENV['QB_TAX_ITEM']      ?? 'Tax Calculated in USSCOS'),
    'tax_line_item' => (string)($_ENV['QB_TAX_LINE_ITEM'] ?? 'Sales Tax'),
];

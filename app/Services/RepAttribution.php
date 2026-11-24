<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Which rep gets credit for an invoice — defined once, used by every query that asks.
 *
 * There are two columns and both are real:
 *
 *   invoices.sales_rep_id   who was credited AT THE TIME of the sale, from QuickBooks.
 *   customers.sales_rep_id  who owns the account NOW.
 *
 * Neither works alone. The QuickBooks "Sales by Rep" export covered 2026 only, so
 * invoices.sales_rep_id is set on 9,334 of 34,737 invoices — using it by itself reports
 * three quarters of the history as unattributed. Using the customer's current rep by
 * itself credits a 2024 sale to whoever inherited the account in 2026, which is wrong the
 * moment an account changes hands, and accounts change hands.
 *
 * So: the credit recorded at the time, falling back to the account's present owner where
 * the import never carried one. It is not perfect — a reassigned account's older invoices
 * follow the new rep — but it is one answer rather than two, and it stops being a
 * compromise as the fuller history is imported.
 *
 * WHY THIS CLASS EXISTS AT ALL. The rep dashboard counted by the customer's rep while the
 * goals screen counted by the invoice's rep. Same question, two answers, on two screens a
 * rep reads in the same minute — and a rep who is told 81% of target on one and something
 * else on the other stops trusting both. One expression, one meaning.
 *
 * Every query using this must join invoices as `i` and customers as `c`.
 */
final class RepAttribution
{
    /** SQL for the attributed rep. Requires the invoices `i` / customers `c` aliases. */
    public const EXPR = 'COALESCE(i.sales_rep_id, c.sales_rep_id)';

    /** `… WHERE <this> = ?`, for a single rep. */
    public static function whereRep(): string
    {
        return self::EXPR . ' = ?';
    }

    /**
     * NOT who keyed the order.
     *
     * invoices.processed_by records who typed it in, which is a different question and has
     * its own report (Sales by Employee). Mixing them pays commission to the office.
     */
    public static function note(): string
    {
        return 'Credit at the time of sale, falling back to the account owner.';
    }
}

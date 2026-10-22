<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\SettingsRepository;

/**
 * Which invoices USSCOS is willing to say anything about, receivables-wise.
 *
 * One definition, used by every A/R figure, so the dashboard tile and the aging report
 * cannot disagree. Two screens quoting different totals for the same question is worse
 * than either of them being wrong on its own, because then nobody knows which to trust.
 *
 * See migration 096 for why this exists.
 */
class ArPeriod
{
    private const KEY = 'ar_opening_date';

    public function __construct(
        private SettingsRepository $settings = new SettingsRepository(),
    ) {
    }

    /** The cutover date, or null while nobody has set one. */
    public function openingDate(): ?string
    {
        $raw = $this->settings->get(self::KEY);

        if ($raw === null) {
            return null;
        }

        // A malformed date must not silently become 1970 and hide every invoice.
        $ts = strtotime($raw);

        return $ts === false ? null : date('Y-m-d', $ts);
    }

    public function isSet(): bool
    {
        return $this->openingDate() !== null;
    }

    /**
     * A WHERE fragment restricting to invoices USSCOS owns, and its parameters.
     *
     * @param string $alias table alias, or '' when the query has none
     * @return array{0:string, 1:list<string>}
     */
    public function clause(string $alias = 'i'): array
    {
        $date = $this->openingDate();

        if ($date === null) {
            return ['', []];
        }

        $col = $alias === '' ? 'invoice_date' : $alias . '.invoice_date';

        return [" AND {$col} >= ? ", [$date]];
    }

    /**
     * The same restriction as a named parameter, for queries built with them.
     *
     * PDO refuses to mix named and positional placeholders in one statement, and some
     * repositories are written with names. Rather than rewrite those queries - or, worse,
     * let a second copy of this rule grow up beside the first - the rule is offered in
     * both shapes.
     *
     * @return array{0:string, 1:array<string,string>}
     */
    public function clauseNamed(string $alias = 'i', string $param = ':ar_from'): array
    {
        $date = $this->openingDate();

        if ($date === null) {
            return ['', []];
        }

        $col = $alias === '' ? 'invoice_date' : $alias . '.invoice_date';

        return [" AND {$col} >= {$param} ", [$param => $date]];
    }

    /**
     * A boolean expression, for use INSIDE a SUM or CASE rather than in a WHERE.
     *
     * Needed because a customer's lifetime revenue, order count and first order date are
     * perfectly good history and must NOT be restricted - only the balance figures are
     * wrong, and only because the payments were never imported. Filtering the whole query
     * would quietly erase years of legitimate sales history from the customer profile.
     *
     * Returns '1' when no opening date is set, so the expression is always valid SQL.
     *
     * @return array{0:string, 1:list<string>}
     */
    public function inScopeExpr(string $alias = 'i'): array
    {
        $date = $this->openingDate();

        if ($date === null) {
            return ['1', []];
        }

        $col = $alias === '' ? 'invoice_date' : $alias . '.invoice_date';

        return ["({$col} >= ?)", [$date]];
    }

    /**
     * Invoices sitting outside the period with no payment record at all.
     *
     * This is the number that makes the case for setting an opening date: imported
     * history that will read as overdue for ever, because the payments that settled it
     * were never brought across.
     *
     * @return array{count:int, balance:float}
     */
    public function unexplainedHistory(): array
    {
        $date = $this->openingDate();
        [$where, $params] = $date === null
            ? ['', []]
            : [' AND i.invoice_date < ? ', [$date]];

        $row = \App\Core\Database::selectOne("
            SELECT COUNT(*) AS n, COALESCE(SUM(i.balance_due), 0) AS total
            FROM invoices i
            WHERE i.balance_due > 0
              AND i.status NOT IN ('void','paid')
              AND NOT EXISTS (
                  SELECT 1 FROM payment_applications pa WHERE pa.invoice_id = i.id
              )
              {$where}
        ", $params);

        return [
            'count'   => (int)($row['n'] ?? 0),
            'balance' => (float)($row['total'] ?? 0),
        ];
    }
}

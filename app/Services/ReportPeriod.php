<?php

declare(strict_types=1);

namespace App\Services;

/**
 * A resolved reporting date range.
 *
 * Accounting reports all want the same set of periods, so the preset names and the
 * arithmetic behind them live here rather than in each controller. Both bounds are
 * inclusive and formatted Y-m-d, which suits the DATE columns they are compared
 * against (invoices.invoice_date, invoices.due_date).
 *
 * `all` yields nulls for both bounds, meaning "no date filter at all".
 */
final class ReportPeriod
{
    public const DEFAULT_PRESET = 'ytd';

    private function __construct(
        public readonly string $preset,
        public readonly ?string $from,
        public readonly ?string $to,
        public readonly string $label,
    ) {
    }

    /**
     * Presets offered in the period picker, in display order.
     *
     * Individual years are appended by options() from the years actually present in the
     * data, so this list stays fixed.
     */
    public static function presets(): array
    {
        return [
            'ytd'          => 'Year to date',
            'qtd'          => 'Quarter to date',
            'mtd'          => 'Month to date',
            'last_30'      => 'Last 30 days',
            'last_12m'     => 'Last 12 months',
            'last_year'    => 'Last year',
            'all'          => 'All time',
        ];
    }

    /**
     * The full option list for a <select>, including one entry per year in $years.
     *
     * @param int[] $years
     */
    public static function options(array $years): array
    {
        $options = self::presets();

        foreach ($years as $y) {
            $options['year:' . $y] = (string)$y;
        }

        $options['custom'] = 'Custom range…';

        return $options;
    }

    /**
     * Resolve a preset (and, for `custom`, explicit bounds) into a concrete range.
     *
     * An unrecognised preset falls back to the default rather than erroring — these
     * arrive from a query string and are not worth a 400.
     */
    public static function resolve(?string $preset, ?string $from = null, ?string $to = null): self
    {
        $preset = $preset !== null && $preset !== '' ? $preset : self::DEFAULT_PRESET;
        $today  = new \DateTimeImmutable('today');
        $fmt    = 'Y-m-d';

        // A specific year, e.g. "year:2025".
        if (preg_match('/^year:(\d{4})$/', $preset, $m) === 1) {
            $year = (int)$m[1];

            return new self(
                $preset,
                sprintf('%04d-01-01', $year),
                sprintf('%04d-12-31', $year),
                (string)$year,
            );
        }

        if ($preset === 'custom') {
            $start = self::parse($from);
            $end   = self::parse($to);

            // Tolerate a half-open custom range, and swap reversed bounds rather than
            // silently returning nothing.
            if ($start !== null && $end !== null && $start > $end) {
                [$start, $end] = [$end, $start];
            }

            if ($start === null && $end === null) {
                return self::resolve(self::DEFAULT_PRESET);
            }

            return new self(
                'custom',
                $start?->format($fmt),
                $end?->format($fmt),
                match (true) {
                    $start !== null && $end !== null => $start->format('M j, Y') . ' – ' . $end->format('M j, Y'),
                    $start !== null                  => 'From ' . $start->format('M j, Y'),
                    default                          => 'Through ' . $end->format('M j, Y'),
                },
            );
        }

        return match ($preset) {
            'qtd' => new self(
                'qtd',
                $today->setDate((int)$today->format('Y'), (intdiv((int)$today->format('n') - 1, 3) * 3) + 1, 1)->format($fmt),
                $today->format($fmt),
                'Quarter to date',
            ),
            'mtd' => new self(
                'mtd',
                $today->modify('first day of this month')->format($fmt),
                $today->format($fmt),
                'Month to date',
            ),
            'last_30' => new self(
                'last_30',
                $today->modify('-29 days')->format($fmt),
                $today->format($fmt),
                'Last 30 days',
            ),
            'last_12m' => new self(
                'last_12m',
                $today->modify('-1 year +1 day')->format($fmt),
                $today->format($fmt),
                'Last 12 months',
            ),
            'last_year' => new self(
                'last_year',
                $today->modify('-1 year')->format('Y') . '-01-01',
                $today->modify('-1 year')->format('Y') . '-12-31',
                'Last year (' . $today->modify('-1 year')->format('Y') . ')',
            ),
            'all' => new self('all', null, null, 'All time'),
            default => new self(
                'ytd',
                $today->format('Y') . '-01-01',
                $today->format($fmt),
                'Year to date',
            ),
        };
    }

    /** True when no date filter applies, so callers can skip the WHERE clause. */
    public function isUnbounded(): bool
    {
        return $this->from === null && $this->to === null;
    }

    /** Strict Y-m-d parse — rejects junk and impossible dates like 2026-02-31. */
    private static function parse(?string $value): ?\DateTimeImmutable
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', trim($value));

        if ($date === false) {
            return null;
        }

        return \DateTimeImmutable::createFromFormat('Y-m-d', $date->format('Y-m-d')) !== false
            && $date->format('Y-m-d') === trim($value)
                ? $date
                : null;
    }
}

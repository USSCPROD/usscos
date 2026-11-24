<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/** All SQL for sales_goals. */
class SalesGoalRepository
{
    /**
     * The goal covering a date, for a rep.
     *
     * Months beat quarters beat years when more than one covers the same day. A rep with a
     * yearly target and a January target has both; January's is the more specific
     * statement of intent, so the dashboard compares against it.
     */
    public function forPeriod(int $repId, string $type, string $date): ?array
    {
        $start = $this->periodStart($type, $date);

        return Database::selectOne(
            'SELECT * FROM sales_goals
              WHERE sales_rep_id = :rep AND period_type = :type AND period_start = :start
              LIMIT 1',
            [':rep' => $repId, ':type' => $type, ':start' => $start]
        ) ?: null;
    }

    /** Every goal for a rep, newest period first, for the admin screen. */
    public function forRep(int $repId): array
    {
        return Database::select(
            'SELECT * FROM sales_goals WHERE sales_rep_id = ? ORDER BY period_start DESC, period_type',
            [$repId]
        );
    }

    /**
     * Goals for one period across every rep, keyed by rep id.
     *
     * One query for the whole table rather than one per rep: the admin screen lists thirty
     * reps and would otherwise make thirty round trips to render a column.
     */
    public function allForPeriod(string $type, string $date): array
    {
        $rows = Database::select(
            'SELECT sales_rep_id, goal_amount, notes
               FROM sales_goals WHERE period_type = ? AND period_start = ?',
            [$type, $this->periodStart($type, $date)]
        );

        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r['sales_rep_id']] = $r;
        }
        return $out;
    }

    /** Setting a goal to zero removes it — a target of nothing is not a target. */
    public function set(int $repId, string $type, string $date, float $amount, ?string $notes, ?int $userId): void
    {
        $start = $this->periodStart($type, $date);

        if ($amount <= 0) {
            Database::delete(
                'DELETE FROM sales_goals WHERE sales_rep_id = ? AND period_type = ? AND period_start = ?',
                [$repId, $type, $start]
            );
            return;
        }

        Database::statement(
            'INSERT INTO sales_goals (sales_rep_id, period_type, period_start, goal_amount, notes, created_by)
             VALUES (:rep, :type, :start, :amount, :notes, :user)
             ON DUPLICATE KEY UPDATE
                 goal_amount = VALUES(goal_amount),
                 notes       = VALUES(notes),
                 created_by  = VALUES(created_by)',
            [
                ':rep'    => $repId,
                ':type'   => $type,
                ':start'  => $start,
                ':amount' => $amount,
                ':notes'  => $notes,
                ':user'   => $userId,
            ]
        );
    }

    /**
     * The first day of the period a date falls in.
     *
     * Normalised here rather than trusted from the caller, because the unique key is on
     * period_start: 2026-11-14 and 2026-11-01 are the same November, and storing both
     * would give one rep two November targets with nothing deciding between them.
     */
    public function periodStart(string $type, string $date): string
    {
        $ts = strtotime($date) ?: time();

        return match ($type) {
            'year'    => date('Y-01-01', $ts),
            'quarter' => date('Y-m-01', mktime(0, 0, 0, (int)(floor(((int)date('n', $ts) - 1) / 3) * 3) + 1, 1, (int)date('Y', $ts))),
            default   => date('Y-m-01', $ts),
        };
    }

    /** The last day of the period, for the matching sales query. */
    public function periodEnd(string $type, string $date): string
    {
        $start = $this->periodStart($type, $date);

        return match ($type) {
            'year'    => date('Y-12-31', strtotime($start)),
            'quarter' => date('Y-m-t', strtotime($start . ' +2 months')),
            default   => date('Y-m-t', strtotime($start)),
        };
    }
}

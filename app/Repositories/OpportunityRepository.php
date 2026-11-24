<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class OpportunityRepository
{
    public function allByStage(): array
    {
        $rows = Database::select("
            SELECT o.*,
                   l.company_name AS lead_name,
                   c.company_name AS customer_name,
                   u.first_name AS rep_first, u.last_name AS rep_last
            FROM opportunities o
            LEFT JOIN leads l    ON l.id = o.lead_id
            LEFT JOIN customers c ON c.id = o.customer_id
            LEFT JOIN users u    ON u.id = o.rep_id
            WHERE o.stage NOT IN ('closed_won','closed_lost')
            ORDER BY o.expected_value DESC
        ");

        $stages = ['prospecting' => [], 'proposal' => [], 'negotiation' => []];
        foreach ($rows as $row) {
            $stages[$row['stage']][] = $row;
        }
        return $stages;
    }

    public function find(int $id): ?array
    {
        return Database::selectOne("
            SELECT o.*,
                   l.company_name AS lead_name,
                   c.company_name AS customer_name,
                   u.first_name AS rep_first, u.last_name AS rep_last
            FROM opportunities o
            LEFT JOIN leads l     ON l.id = o.lead_id
            LEFT JOIN customers c ON c.id = o.customer_id
            LEFT JOIN users u     ON u.id = o.rep_id
            WHERE o.id = ?
        ", [$id]) ?: null;
    }

    public function insert(array $data): int
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare("
            INSERT INTO opportunities
                (name, lead_id, customer_id, rep_id, stage,
                 expected_value, probability, next_step, next_step_date,
                 expected_close, notes, created_by)
            VALUES
                (:name, :lead_id, :customer_id, :rep_id, :stage,
                 :expected_value, :probability, :next_step, :next_step_date,
                 :expected_close, :notes, :created_by)
        ");
        $stmt->execute($this->only($data, [
            ':name', ':lead_id', ':customer_id', ':rep_id', ':stage',
            ':expected_value', ':probability', ':next_step', ':next_step_date',
            ':expected_close', ':notes', ':created_by',
        ]));
        return (int)$pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare("
            /*
             * lead_id and customer_id are NOT written here, and that is the fix for a
             * silent data loss.
             *
             * The edit form only renders the hidden lead_id / customer_id inputs when it
             * is handed a lead or customer, and edit() hands it neither - so on every
             * edit those keys were absent from $_POST, mapPost() turned the absence into
             * null, and this statement wrote NULL over both. Saving an opportunity
             * unlinked it from the account it belonged to, said 'Opportunity updated.',
             * and left the pipeline card reading '-' where the company name had been.
             * In place since the initial commit (3 Aug 2026), found 24 Nov 2026 while
             * testing the next-step nag.
             *
             * Rather than make the form post them back, the statement no longer claims
             * them: nothing on this form edits which account an opportunity is for. It is
             * set when the opportunity is raised, and changed by the actions built for it
             * (linking a quote, converting a lead). A statement that writes a column no
             * form offers can only ever get that column wrong.
             */
            UPDATE opportunities SET
                name           = :name,
                rep_id         = :rep_id,
                stage          = :stage,
                expected_value = :expected_value,
                probability    = :probability,
                next_step      = :next_step,
                next_step_date = :next_step_date,
                expected_close = :expected_close,
                notes          = :notes,
                lost_reason    = :lost_reason
            WHERE id = :id
        ");
        $data[':id'] = $id;
        $stmt->execute($this->only($data, [
            ':name', ':rep_id', ':stage',
            ':expected_value', ':probability', ':next_step', ':next_step_date',
            ':expected_close', ':notes', ':lost_reason', ':id',
        ]));
    }

    /**
     * Narrow a parameter map to what a statement actually names.
     *
     * The controller builds ONE map and feeds it to both statements, which do not take the
     * same columns - insert wants created_by, update wants lost_reason and id. It used to
     * manage this by unset()ing the surplus key at each call site, which works until
     * somebody adds a column to one statement and forgets the other.
     *
     * That exact mismatch made LeadController::update fatal on every save from 3 Aug to
     * 24 Nov 2026: with emulated prepares off, PDO refuses a parameter the SQL never
     * names. Filtered here so the statement decides, and the next column cannot repeat it.
     */
    private function only(array $data, array $keys): array
    {
        return array_intersect_key($data, array_flip($keys));
    }

    public function updateValue(int $id, float $value): void
    {
        Database::connection()->prepare(
            "UPDATE opportunities SET expected_value = :val WHERE id = :id"
        )->execute([':val' => $value, ':id' => $id]);
    }

    /**
     * Move the stage, and record why if it is being lost.
     *
     * Both reason columns are cleared when moving to any other stage. A deal reopened from
     * closed_lost and then won would otherwise still carry the reason it was lost for, and
     * the losses report would count it as both.
     */
    public function updateStage(int $id, string $stage, ?string $lostReason = null, ?string $lostCode = null): void
    {
        $lost = $stage === 'closed_lost';

        Database::connection()->prepare("
            UPDATE opportunities
               SET stage            = :stage,
                   lost_reason      = :reason,
                   lost_reason_code = :code
             WHERE id = :id
        ")->execute([
            ':stage'  => $stage,
            ':reason' => $lost ? $lostReason : null,
            ':code'   => $lost ? $lostCode   : null,
            ':id'     => $id,
        ]);
    }

    /**
     * The deals that have finished, won and lost.
     *
     * The board deliberately shows only open stages — a pipeline is work still in
     * front of you. But that was the only view there was, so a deal vanished the
     * moment it was won: the Closed Won tile counted it and nothing anywhere could
     * list it, and the only way back to a won deal was through the lead or customer
     * it hung off. Found 13 Nov 2026.
     */
    public function closed(int $limit = 200): array
    {
        return Database::select("
            SELECT o.*,
                   u.first_name AS rep_first, u.last_name AS rep_last,
                   COALESCE(c.company_name, l.company_name) AS account_name,
                   c.id AS cust_id, l.id AS lead_ref
              FROM opportunities o
              LEFT JOIN users u      ON u.id = o.rep_id
              LEFT JOIN customers c  ON c.id = o.customer_id
              LEFT JOIN leads l      ON l.id = o.lead_id
             WHERE o.stage IN ('closed_won','closed_lost')
             ORDER BY o.updated_at DESC, o.id DESC
             LIMIT {$limit}
        ");
    }

    /**
     * Open opportunities that nobody is doing anything about.
     *
     * The one screen on a sales dashboard that earns its place: everything else reports
     * what happened, this says what is being neglected. Four reasons, in the order a rep
     * should care about them:
     *
     *   1. The next step is past its date. Somebody said what to do and when, and the day
     *      has gone. The strongest signal on the list, because it is self-reported.
     *   2. There is no next step at all. Nothing is planned, so nothing will happen.
     *   3. The expected close date has passed. The forecast is now wrong either way.
     *   4. Untouched for $staleDays. Nothing has been recorded against it at all.
     *
     * One reason per row, the strongest that applies, because a line reading "overdue, no
     * next step, stale" is three ways of saying the same thing and reads as noise.
     *
     * "Untouched" comes from activity_log, not updated_at. updated_at moves when anything
     * on the row changes, including a nightly value sync from a quote, so it reports work
     * that nobody did. The log only has entries somebody caused.
     *
     * $repId null means every rep - that is the owner's view, not a missing filter.
     */
    public function needingAttention(?int $repId = null, int $staleDays = 14, int $limit = 10): array
    {
        $where  = ["o.stage NOT IN ('closed_won','closed_lost')"];
        $params = [':stale' => $staleDays];

        if ($repId !== null) {
            $where[]           = 'o.rep_id = :rep_id';
            $params[':rep_id'] = $repId;
        }

        $sql = "
            SELECT o.id, o.name, o.stage, o.expected_value, o.next_step, o.next_step_date,
                   o.expected_close,
                   COALESCE(c.company_name, l.company_name) AS party,
                   TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) AS rep_name,
                   GREATEST(COALESCE(a.last_at, o.created_at), o.created_at) AS last_at,
                   DATEDIFF(CURDATE(), DATE(GREATEST(COALESCE(a.last_at, o.created_at), o.created_at))) AS days_quiet,
                   CASE
                     WHEN o.next_step_date IS NOT NULL AND o.next_step_date < CURDATE() THEN 'next_step_overdue'
                     WHEN o.next_step IS NULL OR TRIM(o.next_step) = ''                 THEN 'no_next_step'
                     WHEN o.expected_close IS NOT NULL AND o.expected_close < CURDATE()  THEN 'close_date_passed'
                     ELSE 'stale'
                   END AS reason,
                   CASE
                     WHEN o.next_step_date IS NOT NULL AND o.next_step_date < CURDATE()
                       THEN DATEDIFF(CURDATE(), o.next_step_date)
                     WHEN o.expected_close IS NOT NULL AND o.expected_close < CURDATE()
                       THEN DATEDIFF(CURDATE(), o.expected_close)
                     ELSE NULL
                   END AS days_late
            FROM opportunities o
            LEFT JOIN customers c ON c.id = o.customer_id
            LEFT JOIN leads l     ON l.id = o.lead_id
            LEFT JOIN users u     ON u.id = o.rep_id
            LEFT JOIN (
                SELECT subject_id, MAX(created_at) AS last_at
                FROM activity_log
                WHERE subject_type = 'Opportunity'
                GROUP BY subject_id
            ) a ON a.subject_id = o.id
            WHERE " . implode(' AND ', $where) . "
              AND (
                    (o.next_step_date IS NOT NULL AND o.next_step_date < CURDATE())
                 OR  o.next_step IS NULL OR TRIM(o.next_step) = ''
                 OR (o.expected_close IS NOT NULL AND o.expected_close < CURDATE())
                 OR  DATEDIFF(CURDATE(), DATE(GREATEST(COALESCE(a.last_at, o.created_at), o.created_at))) > :stale
              )
            ORDER BY
                FIELD(CASE
                     WHEN o.next_step_date IS NOT NULL AND o.next_step_date < CURDATE() THEN 'next_step_overdue'
                     WHEN o.next_step IS NULL OR TRIM(o.next_step) = ''                 THEN 'no_next_step'
                     WHEN o.expected_close IS NOT NULL AND o.expected_close < CURDATE()  THEN 'close_date_passed'
                     ELSE 'stale'
                   END, 'next_step_overdue', 'no_next_step', 'close_date_passed', 'stale'),
                o.expected_value DESC
            LIMIT :limit
        ";

        $stmt = Database::connection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, \PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** How many open opportunities are being neglected, for a badge. */
    public function countNeedingAttention(?int $repId = null, int $staleDays = 14): int
    {
        return count($this->needingAttention($repId, $staleDays, 500));
    }

    /**
     * Win/loss, overall and per rep, for closed deals in a period.
     *
     * Counts AND value, because they answer different questions: a rep who wins nine small
     * deals and loses one large one has a 90% win rate and has lost the quarter.
     *
     * Closed deals only - an open deal is neither, and counting it as a loss would make
     * every rep's rate improve simply by having fewer live deals.
     *
     * Period is on updated_at, which is when the stage was last moved. It is the closest
     * thing to a close date on the row; expected_close is a forecast and is frequently
     * blank. Anything more exact needs a closed_at column, and that is worth adding the
     * day somebody disputes a quarter boundary, not before.
     */
    public function winLoss(?int $repId = null, ?string $since = null): array
    {
        $where  = ["o.stage IN ('closed_won','closed_lost')"];
        $params = [];

        if ($repId !== null) {
            $where[]           = 'o.rep_id = :rep_id';
            $params[':rep_id'] = $repId;
        }
        if ($since !== null) {
            $where[]          = 'o.updated_at >= :since';
            $params[':since'] = $since;
        }

        $row = Database::selectOne("
            SELECT
                SUM(o.stage = 'closed_won')                                   AS won_count,
                SUM(o.stage = 'closed_lost')                                  AS lost_count,
                COALESCE(SUM(CASE WHEN o.stage='closed_won'  THEN o.expected_value END), 0) AS won_value,
                COALESCE(SUM(CASE WHEN o.stage='closed_lost' THEN o.expected_value END), 0) AS lost_value
            FROM opportunities o
            WHERE " . implode(' AND ', $where), $params);

        return $row ?: [];
    }

    /** The same, broken down by rep. Unassigned deals come back under a null rep. */
    public function winLossByRep(?string $since = null): array
    {
        $params = [];
        $extra  = '';
        if ($since !== null) {
            $extra            = ' AND o.updated_at >= :since';
            $params[':since'] = $since;
        }

        return Database::select("
            SELECT o.rep_id,
                   TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) AS rep_name,
                   SUM(o.stage = 'closed_won')  AS won_count,
                   SUM(o.stage = 'closed_lost') AS lost_count,
                   COALESCE(SUM(CASE WHEN o.stage='closed_won'  THEN o.expected_value END), 0) AS won_value,
                   COALESCE(SUM(CASE WHEN o.stage='closed_lost' THEN o.expected_value END), 0) AS lost_value
            FROM opportunities o
            LEFT JOIN users u ON u.id = o.rep_id
            WHERE o.stage IN ('closed_won','closed_lost'){$extra}
            GROUP BY o.rep_id, rep_name
            ORDER BY won_value DESC, lost_value DESC
        ", $params);
    }

    /**
     * Why deals were lost, most costly first.
     *
     * Ordered by value, not count. Ten small deals lost on price and one large one lost
     * because we could not supply in time are not the same problem, and a count puts the
     * supply failure at the bottom of the list.
     *
     * Deals with no code come back under NULL rather than being filtered out: "we do not
     * know why we lost 40% of these" is itself the most important line on the report, and
     * dropping it would make the rest look complete.
     */
    public function lostReasons(?string $since = null): array
    {
        $params = [];
        $extra  = '';
        if ($since !== null) {
            $extra            = ' AND o.updated_at >= :since';
            $params[':since'] = $since;
        }

        return Database::select("
            SELECT o.lost_reason_code AS code,
                   COUNT(*)           AS n,
                   COALESCE(SUM(o.expected_value), 0) AS value
            FROM opportunities o
            WHERE o.stage = 'closed_lost'{$extra}
            GROUP BY o.lost_reason_code
            ORDER BY value DESC, n DESC
        ", $params);
    }

    /** The individual losses behind a reason, so a figure can be checked against reality. */
    public function lossesForReason(?string $code, ?string $since = null, int $limit = 50): array
    {
        $params = [];
        $where  = ["o.stage = 'closed_lost'"];

        if ($code === null || $code === '') {
            $where[] = "(o.lost_reason_code IS NULL OR o.lost_reason_code = '')";
        } else {
            $where[]         = 'o.lost_reason_code = :code';
            $params[':code'] = $code;
        }
        if ($since !== null) {
            $where[]          = 'o.updated_at >= :since';
            $params[':since'] = $since;
        }

        $stmt = Database::connection()->prepare("
            SELECT o.id, o.name, o.expected_value, o.lost_reason, o.updated_at,
                   COALESCE(c.company_name, l.company_name) AS account_name,
                   TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) AS rep_name
            FROM opportunities o
            LEFT JOIN customers c ON c.id = o.customer_id
            LEFT JOIN leads l     ON l.id = o.lead_id
            LEFT JOIN users u     ON u.id = o.rep_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY o.expected_value DESC
            LIMIT :limit
        ");
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getPipelineStats(): array
    {
        return Database::selectOne("
            SELECT
                SUM(CASE WHEN stage NOT IN ('closed_won','closed_lost') THEN expected_value ELSE 0 END) AS pipeline_value,
                SUM(CASE WHEN stage NOT IN ('closed_won','closed_lost') THEN expected_value * probability / 100 ELSE 0 END) AS weighted_value,
                SUM(CASE WHEN stage = 'closed_won'  THEN expected_value ELSE 0 END) AS won_value,
                SUM(CASE WHEN stage = 'closed_lost' THEN expected_value ELSE 0 END) AS lost_value,
                COUNT(CASE WHEN stage NOT IN ('closed_won','closed_lost') THEN 1 END) AS open_count,
                COUNT(CASE WHEN stage = 'closed_won'  THEN 1 END) AS won_count
            FROM opportunities
        ") ?: [];
    }

    public function getByCustomer(int $customerId): array
    {
        return Database::select("
            SELECT o.*, u.first_name AS rep_first, u.last_name AS rep_last
            FROM opportunities o
            LEFT JOIN users u ON u.id = o.rep_id
            WHERE o.customer_id = ?
               OR o.lead_id IN (SELECT id FROM leads WHERE customer_id = ?)
            ORDER BY o.created_at DESC
        ", [$customerId, $customerId]);
    }

    public function getByLead(int $leadId): array
    {
        $opps = Database::select("
            SELECT o.*, u.first_name AS rep_first, u.last_name AS rep_last
            FROM opportunities o
            LEFT JOIN users u ON u.id = o.rep_id
            WHERE o.lead_id = ?
            ORDER BY o.created_at DESC
        ", [$leadId]);

        foreach ($opps as &$opp) {
            $opp['quotes'] = Database::select(
                "SELECT id, quote_number, quote_date, total_amount, status
                 FROM quotes WHERE opportunity_id = ? ORDER BY quote_date DESC",
                [(int)$opp['id']]
            );
        }
        return $opps;
    }
}

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
                 expected_value, probability, expected_close, notes, created_by)
            VALUES
                (:name, :lead_id, :customer_id, :rep_id, :stage,
                 :expected_value, :probability, :expected_close, :notes, :created_by)
        ");
        $stmt->execute($data);
        return (int)$pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare("
            UPDATE opportunities SET
                name           = :name,
                lead_id        = :lead_id,
                customer_id    = :customer_id,
                rep_id         = :rep_id,
                stage          = :stage,
                expected_value = :expected_value,
                probability    = :probability,
                expected_close = :expected_close,
                notes          = :notes,
                lost_reason    = :lost_reason
            WHERE id = :id
        ");
        $data[':id'] = $id;
        $stmt->execute($data);
    }

    public function updateValue(int $id, float $value): void
    {
        Database::connection()->prepare(
            "UPDATE opportunities SET expected_value = :val WHERE id = :id"
        )->execute([':val' => $value, ':id' => $id]);
    }

    public function updateStage(int $id, string $stage, ?string $lostReason = null): void
    {
        Database::connection()->prepare("
            UPDATE opportunities SET stage = :stage, lost_reason = :reason WHERE id = :id
        ")->execute([':stage' => $stage, ':reason' => $lostReason, ':id' => $id]);
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

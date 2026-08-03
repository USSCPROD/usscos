<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class LeadRepository
{
    public function paginate(int $page, int $perPage, string $search, string $status): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where  = ['1=1'];

        if ($search !== '') {
            $where[]       = '(l.company_name LIKE :s OR l.first_name LIKE :s2 OR l.last_name LIKE :s3 OR l.email LIKE :s4)';
            $params[':s']  = "%{$search}%";
            $params[':s2'] = "%{$search}%";
            $params[':s3'] = "%{$search}%";
            $params[':s4'] = "%{$search}%";
        }

        if ($status !== 'all') {
            $where[]           = 'l.status = :status';
            $params[':status'] = $status;
        }

        $whereStr = implode(' AND ', $where);
        $pdo      = Database::connection();

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM leads l WHERE {$whereStr}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sql = "
            SELECT l.*,
                   u.first_name AS rep_first, u.last_name AS rep_last
            FROM leads l
            LEFT JOIN users u ON u.id = l.rep_id
            WHERE {$whereStr}
            ORDER BY l.created_at DESC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  \PDO::PARAM_INT);
        $stmt->execute();

        $lastPage = max(1, (int)ceil($total / $perPage));

        return [
            'data'         => $stmt->fetchAll(\PDO::FETCH_ASSOC),
            'total'        => $total,
            'current_page' => $page,
            'last_page'    => $lastPage,
            'per_page'     => $perPage,
            'from'         => $total > 0 ? $offset + 1 : 0,
            'to'           => min($offset + $perPage, $total),
        ];
    }

    public function find(int $id): ?array
    {
        return Database::selectOne("
            SELECT l.*,
                   u.first_name AS rep_first, u.last_name AS rep_last
            FROM leads l
            LEFT JOIN users u ON u.id = l.rep_id
            WHERE l.id = ?
        ", [$id]) ?: null;
    }

    public function insert(array $data): int
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare("
            INSERT INTO leads
                (company_name, first_name, last_name, email, phone, website,
                 source, status, rep_id, notes, created_by)
            VALUES
                (:company_name, :first_name, :last_name, :email, :phone, :website,
                 :source, :status, :rep_id, :notes, :created_by)
        ");
        $stmt->execute($data);
        return (int)$pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare("
            UPDATE leads SET
                company_name = :company_name,
                first_name   = :first_name,
                last_name    = :last_name,
                email        = :email,
                phone        = :phone,
                website      = :website,
                source       = :source,
                status       = :status,
                rep_id       = :rep_id,
                notes        = :notes
            WHERE id = :id
        ");
        $data[':id'] = $id;
        $stmt->execute($data);
    }

    public function convert(int $id, int $customerId): void
    {
        Database::connection()->prepare("
            UPDATE leads SET status = 'converted', customer_id = :cid WHERE id = :id
        ")->execute([':cid' => $customerId, ':id' => $id]);
    }

    public function getOpportunities(int $leadId): array
    {
        return Database::select("
            SELECT o.*, u.first_name AS rep_first, u.last_name AS rep_last
            FROM opportunities o
            LEFT JOIN users u ON u.id = o.rep_id
            WHERE o.lead_id = ?
            ORDER BY o.created_at DESC
        ", [$leadId]);
    }

    public function getStats(): array
    {
        return Database::selectOne("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'new'       THEN 1 ELSE 0 END) AS new_count,
                SUM(CASE WHEN status = 'contacted' THEN 1 ELSE 0 END) AS contacted_count,
                SUM(CASE WHEN status = 'qualified' THEN 1 ELSE 0 END) AS qualified_count,
                SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) AS converted_count,
                SUM(CASE WHEN status = 'dead'      THEN 1 ELSE 0 END) AS dead_count
            FROM leads
        ") ?: [];
    }
}

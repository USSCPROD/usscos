<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class TaskRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function paginate(int $page, int $perPage, array $filters): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where  = ['1=1'];

        if (!empty($filters['search'])) {
            $where[]      = 't.title LIKE :search';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $where[]         = 't.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['priority']) && $filters['priority'] !== 'all') {
            $where[]           = 't.priority = :priority';
            $params[':priority'] = $filters['priority'];
        }
        if (!empty($filters['assigned_to'])) {
            $where[]              = 't.assigned_to = :assigned_to';
            $params[':assigned_to'] = (int)$filters['assigned_to'];
        }
        if (!empty($filters['due'])) {
            if ($filters['due'] === 'overdue') {
                $where[] = 't.due_date < CURDATE() AND t.status NOT IN (\'completed\',\'cancelled\')';
            } elseif ($filters['due'] === 'today') {
                $where[] = 't.due_date = CURDATE()';
            } elseif ($filters['due'] === 'week') {
                $where[] = 't.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)';
            }
        }

        $whereStr = implode(' AND ', $where);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM tasks t WHERE {$whereStr}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sql = "
            SELECT t.*,
                   a.first_name AS assigned_first, a.last_name AS assigned_last,
                   cb.first_name AS created_first, cb.last_name AS created_last,
                   c.company_name AS customer_name,
                   l.company_name AS lead_name,
                   opp.name AS opportunity_name,
                   q.quote_number,
                   so.so_number
            FROM tasks t
            LEFT JOIN users a   ON a.id  = t.assigned_to
            LEFT JOIN users cb  ON cb.id = t.created_by
            LEFT JOIN customers c    ON c.id   = t.customer_id
            LEFT JOIN leads l        ON l.id   = t.lead_id
            LEFT JOIN opportunities opp ON opp.id = t.opportunity_id
            LEFT JOIN quotes q       ON q.id   = t.quote_id
            LEFT JOIN sales_orders so ON so.id = t.sales_order_id
            WHERE {$whereStr}
            ORDER BY
                CASE t.status WHEN 'completed' THEN 1 WHEN 'cancelled' THEN 2 ELSE 0 END,
                CASE t.priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END,
                t.due_date ASC, t.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  \PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data'         => $stmt->fetchAll(\PDO::FETCH_ASSOC),
            'total'        => $total,
            'current_page' => $page,
            'last_page'    => max(1, (int)ceil($total / $perPage)),
            'per_page'     => $perPage,
            'from'         => $total > 0 ? $offset + 1 : 0,
            'to'           => min($offset + $perPage, $total),
        ];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT t.*,
                   a.first_name AS assigned_first, a.last_name AS assigned_last,
                   cb.first_name AS created_first, cb.last_name AS created_last,
                   c.company_name AS customer_name,
                   l.company_name AS lead_name,
                   opp.name AS opportunity_name,
                   q.quote_number,
                   so.so_number
            FROM tasks t
            LEFT JOIN users a   ON a.id  = t.assigned_to
            LEFT JOIN users cb  ON cb.id = t.created_by
            LEFT JOIN customers c    ON c.id   = t.customer_id
            LEFT JOIN leads l        ON l.id   = t.lead_id
            LEFT JOIN opportunities opp ON opp.id = t.opportunity_id
            LEFT JOIN quotes q       ON q.id   = t.quote_id
            LEFT JOIN sales_orders so ON so.id = t.sales_order_id
            WHERE t.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getForRecord(string $type, int $recordId): array
    {
        $col = match($type) {
            'customer'    => 'customer_id',
            'lead'        => 'lead_id',
            'opportunity' => 'opportunity_id',
            'quote'       => 'quote_id',
            'sales_order' => 'sales_order_id',
            default       => throw new \InvalidArgumentException("Unknown type: {$type}"),
        };

        $stmt = $this->pdo->prepare("
            SELECT t.*,
                   a.first_name AS assigned_first, a.last_name AS assigned_last
            FROM tasks t
            LEFT JOIN users a ON a.id = t.assigned_to
            WHERE t.{$col} = :id
            ORDER BY
                CASE t.status WHEN 'completed' THEN 1 WHEN 'cancelled' THEN 2 ELSE 0 END,
                CASE t.priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END,
                t.due_date ASC
        ");
        $stmt->execute([':id' => $recordId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT t.*,
                   c.company_name AS customer_name,
                   l.company_name AS lead_name,
                   opp.name AS opportunity_name
            FROM tasks t
            LEFT JOIN customers c    ON c.id   = t.customer_id
            LEFT JOIN leads l        ON l.id   = t.lead_id
            LEFT JOIN opportunities opp ON opp.id = t.opportunity_id
            WHERE t.assigned_to = :uid
              AND t.status NOT IN ('completed','cancelled')
            ORDER BY
                CASE t.priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END,
                t.due_date ASC, t.created_at DESC
        ");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function insert(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO tasks
                (title, description, status, priority, due_date,
                 assigned_to, created_by,
                 customer_id, lead_id, opportunity_id, quote_id, sales_order_id)
            VALUES
                (:title, :description, :status, :priority, :due_date,
                 :assigned_to, :created_by,
                 :customer_id, :lead_id, :opportunity_id, :quote_id, :sales_order_id)
        ");
        $stmt->execute($data);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE tasks SET
                title          = :title,
                description    = :description,
                status         = :status,
                priority       = :priority,
                due_date       = :due_date,
                assigned_to    = :assigned_to,
                customer_id    = :customer_id,
                lead_id        = :lead_id,
                opportunity_id = :opportunity_id,
                quote_id       = :quote_id,
                sales_order_id = :sales_order_id,
                completed_at   = :completed_at
            WHERE id = :id
        ");
        $stmt->execute(array_merge($data, [':id' => $id]));
    }

    public function setStatus(int $id, string $status): void
    {
        $completedAt = $status === 'completed' ? date('Y-m-d H:i:s') : null;
        $stmt = $this->pdo->prepare("UPDATE tasks SET status = :status, completed_at = :completed_at WHERE id = :id");
        $stmt->execute([':status' => $status, ':completed_at' => $completedAt, ':id' => $id]);
    }

    public function getStats(int $userId = 0): array
    {
        $where  = $userId ? 'WHERE assigned_to = ' . $userId : '';
        $stmt   = $this->pdo->query("
            SELECT
                SUM(CASE WHEN status = 'open'        THEN 1 ELSE 0 END) AS open_count,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress_count,
                SUM(CASE WHEN status = 'completed'   THEN 1 ELSE 0 END) AS completed_count,
                SUM(CASE WHEN due_date < CURDATE() AND status NOT IN ('completed','cancelled') THEN 1 ELSE 0 END) AS overdue_count,
                SUM(CASE WHEN priority = 'urgent' AND status NOT IN ('completed','cancelled') THEN 1 ELSE 0 END) AS urgent_count
            FROM tasks {$where}
        ");
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}

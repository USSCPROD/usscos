<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * All SQL for activity_log.
 *
 * Reads join users so a timeline can say who, not just when. The join is LEFT because an
 * event can have no user - a webhook, a cron, an import - and "the website did it" is a
 * real answer that must not drop the row.
 *
 * LIMIT and OFFSET are bound as PDO::PARAM_INT through this class's own handle, not passed
 * in the execute() array: emulated prepares are off project-wide, so an array-bound limit
 * reaches MySQL as LIMIT '50' and the query dies.
 */
class ActivityRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function insert(array $data): string
    {
        return Database::insert(
            'INSERT INTO activity_log
                (company_id, user_id, subject_type, subject_id, customer_id, lead_id,
                 event, description, old_values, new_values, ip_address, user_agent)
             VALUES
                (:company_id, :user_id, :subject_type, :subject_id, :customer_id, :lead_id,
                 :event, :description, :old_values, :new_values, :ip_address, :user_agent)',
            [
                ':company_id'   => $data['company_id'],
                ':user_id'      => $data['user_id'],
                ':subject_type' => $data['subject_type'],
                ':subject_id'   => $data['subject_id'],
                ':customer_id'  => $data['customer_id'],
                ':lead_id'      => $data['lead_id'],
                ':event'        => $data['event'],
                ':description'  => $data['description'],
                ':old_values'   => $data['old_values'],
                ':new_values'   => $data['new_values'],
                ':ip_address'   => $data['ip_address'],
                ':user_agent'   => $data['user_agent'],
            ]
        );
    }

    /** Newest first. Limited in SQL - a long-standing customer runs to thousands of rows. */
    public function forCustomer(int $customerId, int $limit = 50, int $offset = 0): array
    {
        return $this->fetch(
            'WHERE a.customer_id = :customer_id',
            [':customer_id' => $customerId],
            $limit,
            $offset
        );
    }

    public function countForCustomer(int $customerId): int
    {
        return $this->count('customer_id', $customerId);
    }

    public function forLead(int $leadId, int $limit = 50, int $offset = 0): array
    {
        return $this->fetch('WHERE a.lead_id = :lead_id', [':lead_id' => $leadId], $limit, $offset);
    }

    public function countForLead(int $leadId): int
    {
        return $this->count('lead_id', $leadId);
    }

    /** One record's own history - what happened to invoice 412, in order. */
    public function forSubject(string $type, int $id, int $limit = 50): array
    {
        return $this->fetch(
            'WHERE a.subject_type = :type AND a.subject_id = :id',
            [':type' => $type, ':id' => $id],
            $limit
        );
    }

    /** The whole log, for the admin audit screen. */
    public function recent(int $limit = 100, int $offset = 0, array $filters = []): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['event'])) {
            $where[]          = 'a.event = :event';
            $params[':event'] = $filters['event'];
        }
        if (!empty($filters['user_id'])) {
            $where[]            = 'a.user_id = :user_id';
            $params[':user_id'] = (int) $filters['user_id'];
        }
        if (!empty($filters['subject_type'])) {
            $where[]                 = 'a.subject_type = :subject_type';
            $params[':subject_type'] = $filters['subject_type'];
        }

        return $this->fetch('WHERE ' . implode(' AND ', $where), $params, $limit, $offset);
    }

    /** Distinct event names actually present, so the audit filter offers only real ones. */
    public function eventNames(): array
    {
        return array_column(
            Database::select('SELECT DISTINCT event FROM activity_log ORDER BY event'),
            'event'
        );
    }

    private function fetch(string $where, array $params, int $limit, int $offset = 0): array
    {
        $sql = "SELECT a.*,
                       TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))) AS user_name
                  FROM activity_log a
                  LEFT JOIN users u ON u.id = a.user_id
                  {$where}
                 ORDER BY a.created_at DESC, a.id DESC
                 LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $limit,  \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function count(string $column, int $value): int
    {
        $row = Database::selectOne(
            "SELECT COUNT(*) AS n FROM activity_log WHERE {$column} = ?",
            [$value]
        );
        return (int) ($row['n'] ?? 0);
    }
}

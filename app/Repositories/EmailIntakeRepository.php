<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/** All SQL for email_intake. */
class EmailIntakeRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    /**
     * Store a received message.
     *
     * Returns null when the message_id is already present. The UNIQUE index does the
     * deduplication, not a SELECT-then-INSERT: two deliveries arriving at once would both
     * pass a check and both insert.
     */
    public function insert(array $d): ?int
    {
        try {
            $id = Database::insert(
                'INSERT INTO email_intake
                    (message_id, direction, from_email, from_name, to_emails, subject, body,
                     sent_at, matched_customer_id, matched_lead_id, matched_on, match_type,
                     activity_id, status)
                 VALUES
                    (:message_id, :direction, :from_email, :from_name, :to_emails, :subject, :body,
                     :sent_at, :customer_id, :lead_id, :matched_on, :match_type,
                     :activity_id, :status)',
                [
                    ':message_id'  => $d['message_id'],
                    ':direction'   => $d['direction'],
                    ':from_email'  => $d['from_email'],
                    ':from_name'   => $d['from_name'],
                    ':to_emails'   => $d['to_emails'],
                    ':subject'     => $d['subject'],
                    ':body'        => $d['body'],
                    ':sent_at'     => $d['sent_at'],
                    ':customer_id' => $d['matched_customer_id'],
                    ':lead_id'     => $d['matched_lead_id'],
                    ':matched_on'  => $d['matched_on'],
                    ':match_type'  => $d['match_type'],
                    ':activity_id' => $d['activity_id'],
                    ':status'      => $d['status'],
                ]
            );
            return (int) $id;
        } catch (\PDOException $e) {
            // 23000 is an integrity constraint - here, the unique message_id. A repeat
            // delivery is normal and is not an error worth raising.
            if ($e->getCode() === '23000') {
                return null;
            }
            throw $e;
        }
    }

    public function find(int $id): ?array
    {
        return Database::selectOne('SELECT * FROM email_intake WHERE id = ?', [$id]) ?: null;
    }

    /** @param string[] $statuses */
    public function byStatus(array $statuses, int $limit = 100, int $offset = 0): array
    {
        /*
         * Named placeholders for the IN list too, not '?'.
         *
         * PDO refuses a statement that mixes positional and named parameters outright -
         * "Invalid parameter number: mixed named and positional parameters" - and LIMIT has
         * to be named here because it is bound as PARAM_INT separately.
         */
        $names = [];
        foreach (array_values($statuses) as $i => $v) {
            $names[':s' . $i] = $v;
        }
        $in = implode(',', array_keys($names));

        $stmt = $this->pdo->prepare("
            SELECT e.*,
                   c.company_name AS customer_name,
                   l.company_name AS lead_name
            FROM email_intake e
            LEFT JOIN customers c ON c.id = e.matched_customer_id
            LEFT JOIN leads l     ON l.id = e.matched_lead_id
            WHERE e.status IN ({$in})
            ORDER BY e.created_at DESC, e.id DESC
            LIMIT :limit OFFSET :offset
        ");
        foreach ($names as $name => $v) {
            $stmt->bindValue($name, $v);
        }
        $stmt->bindValue(':limit',  $limit,  \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function countByStatus(string $status): int
    {
        $row = Database::selectOne('SELECT COUNT(*) AS n FROM email_intake WHERE status = ?', [$status]);
        return (int) ($row['n'] ?? 0);
    }

    public function attach(int $id, ?int $customerId, ?int $leadId, int $activityId): void
    {
        Database::update(
            'UPDATE email_intake
                SET matched_customer_id = :customer_id,
                    matched_lead_id     = :lead_id,
                    match_type          = \'manual\',
                    activity_id         = :activity_id,
                    status              = \'matched\'
              WHERE id = :id',
            [
                ':customer_id' => $customerId,
                ':lead_id'     => $leadId,
                ':activity_id' => $activityId,
                ':id'          => $id,
            ]
        );
    }

    public function linkActivity(int $id, int $activityId): void
    {
        Database::update('UPDATE email_intake SET activity_id = ? WHERE id = ?', [$activityId, $id]);
    }

    public function ignore(int $id): void
    {
        Database::update("UPDATE email_intake SET status = 'ignored' WHERE id = ?", [$id]);
    }
}

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
        /*
         * Bound to the statement's own placeholders, not to whatever the caller passed.
         *
         * The controller builds one parameter map and uses it for both insert and update.
         * insert() writes created_by; this statement does not, and with emulated prepares
         * off PDO rejects a parameter the SQL never names: "Invalid parameter number".
         * Editing a lead threw a 500 on every save from the initial commit (3 Aug 2026)
         * until this was found on 24 Nov 2026 - the form looked fine, took the edit, and
         * died on submit, which reads as "the lead won't save" rather than as a crash.
         *
         * Filtering here rather than in the controller because this statement is the thing
         * that knows which columns it writes, and the next column added to one statement
         * and not the other must not be able to break the other one again.
         */
        $allowed = [
            ':company_name', ':first_name', ':last_name', ':email', ':phone',
            ':website', ':source', ':status', ':rep_id', ':notes',
        ];

        $params = array_intersect_key($data, array_flip($allowed));
        $params[':id'] = $id;

        $stmt->execute($params);
    }

    /**
     * Web leads per originating site, newest era first.
     *
     * The reason source_site exists: "how many enquiries does each site actually bring
     * in" was unanswerable, and on 13 Nov 2026 a spam rule deleted 117 web leads with no
     * way to tell whose they were. Leads taken before migration 127 come back under a
     * null site and are reported as not recorded, never folded into either real site.
     */
    public function countsBySite(): array
    {
        return Database::select("
            SELECT COALESCE(source_site, '') AS site,
                   COUNT(*)                  AS n,
                   MIN(created_at)           AS first_at,
                   MAX(created_at)           AS last_at
            FROM leads
            WHERE source = 'web'
            GROUP BY COALESCE(source_site, '')
            ORDER BY n DESC
        ");
    }

    /**
     * Mark a lead converted, and bring what it was carrying across with it.
     *
     * A lead can hold quotes and opportunities — quoting an enquiry before they are a
     * customer is the normal way round. Converting used to stamp the lead alone, so
     * those quotes stayed pointed at a lead with no customer on them: a sales order
     * needs a customer, so an accepted quote became a dead end the moment its lead
     * was converted, with nothing saying why.
     *
     * Found 12 Nov 2026 walking lead to sales order for the first time. The whole
     * thing runs in one transaction, because a lead marked converted whose quotes did
     * not follow is exactly the state this is fixing.
     */
    public function convert(int $id, int $customerId): void
    {
        $pdo = Database::connection();
        $own = !$pdo->inTransaction();

        if ($own) {
            $pdo->beginTransaction();
        }

        try {
            $pdo->prepare("
                UPDATE leads SET status = 'converted', customer_id = :cid WHERE id = :id
            ")->execute([':cid' => $customerId, ':id' => $id]);

            // Quotes raised against the lead now belong to the customer it became.
            // lead_id stays, so where the quote came from is still on the record.
            $pdo->prepare("
                UPDATE quotes SET customer_id = :cid
                 WHERE lead_id = :id AND customer_id IS NULL
            ")->execute([':cid' => $customerId, ':id' => $id]);

            $pdo->prepare("
                UPDATE opportunities SET customer_id = :cid
                 WHERE lead_id = :id AND customer_id IS NULL
            ")->execute([':cid' => $customerId, ':id' => $id]);

            if ($own) {
                $pdo->commit();
            }
        } catch (\Throwable $e) {
            if ($own && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
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

    /** A lead from a website form. See App\Services\WebsiteLeadService. */
    public function insertWebsiteLead(array $d): int
    {
        Database::insert(
            'INSERT INTO leads
                (first_name, last_name, company_name, email, phone,
                 source, source_detail, source_site, notes, status, rep_id,
                 created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, \'web\', ?, ?, ?, \'new\', ?, NOW(), NOW())',
            [
                $d['first_name'], $d['last_name'], $d['company_name'], $d['email'],
                $d['phone'], $d['source_detail'], $d['source_site'] ?? null,
                $d['notes'], $d['rep_id'],
            ]
        );

        return (int)Database::lastInsertId();
    }

    /** The lead_routing_* settings, key => value. */
    public function routingSettings(): array
    {
        $map = [];

        foreach (Database::select("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'lead_routing_%'") as $row) {
            $map[$row['setting_key']] = $row['setting_value'];
        }

        return $map;
    }
}

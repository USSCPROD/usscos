<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class AdminRepository
{
    /**
     * The admin lookup tables, and what refers to them.
     *
     * Deactivating is always safe. Deleting is only safe when nothing points at the row,
     * because the foreign keys do very different things:
     *
     *   SET NULL   the delete SUCCEEDS and quietly blanks the column on historical
     *              records — how a deleted sales rep would vanish from past invoices
     *   NO ACTION  the database blocks the delete outright
     *   CASCADE    dependent rows are deleted too (users -> tasks, sales_goals)
     *
     * So `refs` is checked in the application before deleting rather than relying on the
     * database to object, since for the SET NULL cases it won't.
     *
     * `deletable => false` means never offer a hard delete at all. Users are the case:
     * their history spans twenty-odd tables, and the CASCADE on tasks and sales_goals
     * would destroy records. They are deactivated instead.
     *
     * Keys are URL slugs. A slug is only ever used to look up this map, never
     * interpolated into SQL — the table and column names below are the only ones that
     * reach a query.
     */
    private const ENTITIES = [
        'sales-reps' => [
            'table'     => 'sales_reps',
            'label'     => 'Sales rep',
            'name_col'  => 'name',
            'redirect'  => '/admin/sales-reps',
            'deletable' => true,
            'refs'      => [
                ['invoices',  'sales_rep_id',        'invoices'],
                ['invoices',  'processed_by_rep_id', 'invoices (processed by)'],
                ['customers', 'sales_rep_id',        'customers'],
            ],
        ],
        'ship-via' => [
            'table'     => 'ship_via',
            'label'     => 'Shipping method',
            'name_col'  => 'name',
            'redirect'  => '/admin/ship-via',
            'deletable' => true,
            'refs'      => [
                ['quotes',       'ship_via_id', 'quotes'],
                ['sales_orders', 'ship_via_id', 'sales orders'],
            ],
        ],
        'payment-terms' => [
            'table'     => 'payment_terms',
            'label'     => 'Payment term',
            'name_col'  => 'name',
            'redirect'  => '/admin/payment-terms',
            'deletable' => true,
            'refs'      => [
                ['invoices',  'payment_term_id', 'invoices'],
                ['customers', 'payment_term_id', 'customers'],
                ['quotes',    'payment_term_id', 'quotes'],
                ['bills',     'payment_term_id', 'bills'],
                ['vendors',   'payment_term_id', 'vendors'],
            ],
        ],
        'tax-rates' => [
            'table'     => 'tax_rates',
            'label'     => 'Tax rate',
            'name_col'  => 'name',
            'redirect'  => '/admin/tax-rates',
            'deletable' => true,
            'refs'      => [
                ['customers',    'tax_rate_id', 'customers'],
                ['quotes',       'tax_rate_id', 'quotes'],
                ['sales_orders', 'tax_rate_id', 'sales orders'],
            ],
        ],
        'customer-messages' => [
            'table'     => 'customer_messages',
            'label'     => 'Customer message',
            'name_col'  => 'name',
            'redirect'  => '/admin/customer-messages',
            'deletable' => true,
            'refs'      => [
                ['sales_orders', 'customer_message_id', 'sales orders'],
            ],
        ],
        'departments' => [
            'table'     => 'departments',
            'label'     => 'Department',
            'name_col'  => 'name',
            'redirect'  => '/admin/departments',
            'deletable' => true,
            'refs'      => [
                ['users', 'department_id', 'users'],
            ],
        ],
        'customer-types' => [
            'table'     => 'customer_types',
            'label'     => 'Customer type',
            'name_col'  => 'name',
            'redirect'  => '/admin/customer-types',
            'deletable' => true,
            // customers.customer_type holds the type NAME, not an id, so this reference
            // is matched by name rather than by key.
            'refs'      => [
                ['customers', 'customer_type', 'customers', 'name'],
            ],
        ],
        'users' => [
            'table'     => 'users',
            'label'     => 'User',
            'name_col'  => "CONCAT(first_name, ' ', last_name)",
            'redirect'  => '/admin/users',
            'deletable' => false,
            'refs'      => [],
        ],
    ];

    /** Config for an admin entity slug, or null if the slug isn't one we manage. */
    public function entityConfig(string $slug): ?array
    {
        return self::ENTITIES[$slug] ?? null;
    }

    /** One row from an admin lookup table, with its display name. */
    public function findEntity(string $slug, int $id): ?array
    {
        $cfg = $this->entityConfig($slug);
        if ($cfg === null) {
            return null;
        }

        // Database::selectOne returns false, not null, for a missing row — normalise it so
        // callers can rely on the ?array contract.
        $row = Database::selectOne(
            "SELECT id, is_active, {$cfg['name_col']} AS display_name FROM {$cfg['table']} WHERE id = ?",
            [$id]
        );

        return $row === false ? null : $row;
    }

    public function setEntityActive(string $slug, int $id, bool $active): void
    {
        $cfg = $this->entityConfig($slug);
        if ($cfg === null) {
            throw new \InvalidArgumentException("Unknown admin entity: {$slug}");
        }

        Database::statement(
            "UPDATE {$cfg['table']} SET is_active = ? WHERE id = ?",
            [$active ? 1 : 0, $id]
        );
    }

    /**
     * What still points at this row, as [human label => count], omitting zeros.
     *
     * An empty result means a hard delete cannot damage anything.
     */
    public function entityReferences(string $slug, int $id): array
    {
        $cfg = $this->entityConfig($slug);
        if ($cfg === null) {
            return [];
        }

        $row = $this->findEntity($slug, $id);
        if ($row === null) {
            return [];
        }

        $counts = [];

        foreach ($cfg['refs'] as $ref) {
            [$table, $column, $label] = $ref;
            $matchBy = $ref[3] ?? 'id';
            $value   = $matchBy === 'name' ? $row['display_name'] : $id;

            $result = Database::selectOne(
                "SELECT COUNT(*) AS n FROM {$table} WHERE {$column} = ?",
                [$value]
            );

            $n = $result === false ? 0 : (int)($result['n'] ?? 0);
            if ($n > 0) {
                $counts[$label] = $n;
            }
        }

        return $counts;
    }

    /**
     * Total references per row for a whole list, as [id => count].
     *
     * One grouped query per referencing table rather than one per row, so a list of 30
     * rows costs three queries instead of ninety. Used to decide whether to offer Delete
     * at all — the controller re-checks before acting.
     */
    public function entityReferenceCounts(string $slug): array
    {
        $cfg = $this->entityConfig($slug);
        if ($cfg === null || $cfg['refs'] === []) {
            return [];
        }

        $totals = [];

        foreach ($cfg['refs'] as $ref) {
            [$table, $column] = $ref;
            $matchBy = $ref[3] ?? 'id';

            if ($matchBy === 'name') {
                // The referencing column holds the display name, so join back on it.
                $sql = "SELECT t.id AS id, COUNT(r.{$column}) AS n
                        FROM {$cfg['table']} t
                        JOIN {$table} r ON r.{$column} = t.{$cfg['name_col']}
                        GROUP BY t.id";
            } else {
                $sql = "SELECT {$column} AS id, COUNT(*) AS n
                        FROM {$table}
                        WHERE {$column} IS NOT NULL
                        GROUP BY {$column}";
            }

            foreach (Database::select($sql) as $r) {
                $id = (int)$r['id'];
                $totals[$id] = ($totals[$id] ?? 0) + (int)$r['n'];
            }
        }

        return $totals;
    }

    /**
     * Delete an admin lookup row, but only when nothing references it.
     *
     * The reference check is repeated inside the transaction so a record that gains a
     * reference between the page render and the click cannot slip through.
     *
     * @throws \RuntimeException if the entity is never deletable, or is still in use.
     */
    public function deleteEntity(string $slug, int $id): void
    {
        $cfg = $this->entityConfig($slug);
        if ($cfg === null) {
            throw new \InvalidArgumentException("Unknown admin entity: {$slug}");
        }
        if (!$cfg['deletable']) {
            throw new \RuntimeException("{$cfg['label']}s cannot be deleted — deactivate instead.");
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $refs = $this->entityReferences($slug, $id);

            if ($refs !== []) {
                $parts = [];
                foreach ($refs as $label => $n) {
                    $parts[] = $n . ' ' . $label;
                }
                throw new \RuntimeException(
                    "Still used by " . implode(', ', $parts) . ". Deactivate it instead — deleting would remove it from those records."
                );
            }

            $stmt = $pdo->prepare("DELETE FROM {$cfg['table']} WHERE id = ?");
            $stmt->execute([$id]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // -------------------------------------------------------------------------
    // Ship Via
    // -------------------------------------------------------------------------

    // -------------------------------------------------------------- Sales reps

    /**
     * Reps with their attributed volume, so the admin screen shows the consequence of
     * deactivating or reclassifying one.
     */
    /**
     * Sales reps for the admin list.
     *
     * Defaults to actual reps only — employees, the owner, house accounts, website and
     * placeholder rows are attribution buckets, not people to manage here. They stay
     * reachable with $repsOnly = false so their records remain editable, since they
     * still carry invoice history.
     *
     * Inactive reps are always included: this is the page you reactivate them from.
     */
    public function allSalesReps(bool $repsOnly = true): array
    {
        return Database::select("
            SELECT sr.*,
                   TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) AS user_name,
                   (SELECT COUNT(*) FROM customers c WHERE c.sales_rep_id = sr.id) AS customer_count,
                   (SELECT COUNT(*) FROM invoices  i WHERE i.sales_rep_id = sr.id) AS invoice_count
            FROM sales_reps sr
            LEFT JOIN users u ON u.id = sr.user_id
            " . ($repsOnly ? "WHERE sr.rep_type = 'person'" : "") . "
            ORDER BY FIELD(sr.rep_type,'person','employee','owner','partner','house','website','none'),
                     sr.is_active DESC,
                     sr.name
        ");
    }

    /** The login linked to a sales rep, or null when they have none. */
    public function findUserForRep(mixed $userId): ?array
    {
        if ($userId === null || $userId === '') {
            return null;
        }

        $row = Database::selectOne(
            'SELECT id, first_name, last_name, email, role, is_active
             FROM users WHERE id = ? AND deleted_at IS NULL',
            [(int)$userId]
        );

        return $row === false ? null : $row;
    }

    /** Count of rows hidden by the default reps-only filter, for the "show all" link. */
    public function countNonRepSalesReps(): int
    {
        $row = Database::selectOne("SELECT COUNT(*) AS n FROM sales_reps WHERE rep_type <> 'person'");

        return (int)($row['n'] ?? 0);
    }

    public function findSalesRep(int $id): array|false
    {
        return Database::selectOne('SELECT * FROM sales_reps WHERE id = ?', [$id]);
    }

    public function insertSalesRep(array $d): int
    {
        $pdo = Database::connection();
        $pdo->prepare("
            INSERT INTO sales_reps (name, quickbooks_name, rep_type, user_id, commission_rate, is_active, notes)
            VALUES (:name, :qb, :type, :user, :rate, :active, :notes)
        ")->execute($this->salesRepParams($d));
        return (int)$pdo->lastInsertId();
    }

    public function updateSalesRep(int $id, array $d): void
    {
        $params = $this->salesRepParams($d);
        $params[':id'] = $id;
        Database::connection()->prepare("
            UPDATE sales_reps SET
                name            = :name,
                quickbooks_name = :qb,
                rep_type        = :type,
                user_id         = :user,
                commission_rate = :rate,
                is_active       = :active,
                notes           = :notes
            WHERE id = :id
        ")->execute($params);
    }

    private function salesRepParams(array $d): array
    {
        $types = ['person','employee','owner','house','website','partner','none'];
        $type  = in_array($d['rep_type'] ?? '', $types, true) ? $d['rep_type'] : 'person';

        return [
            ':name'   => trim((string)($d['name'] ?? '')),
            // The QuickBooks name is the join key for imports — must never be blank,
            // so it falls back to the display name.
            ':qb'     => trim((string)($d['quickbooks_name'] ?? '')) ?: trim((string)($d['name'] ?? '')),
            ':type'   => $type,
            ':user'   => ($d['user_id'] ?? '') !== '' ? (int)$d['user_id'] : null,
            ':rate'   => ($d['commission_rate'] ?? '') !== '' ? (float)$d['commission_rate'] : null,
            ':active' => (int)(bool)($d['is_active'] ?? 1),
            ':notes'  => trim((string)($d['notes'] ?? '')) ?: null,
        ];
    }

    public function allShipVia(): array
    {
        return Database::select('SELECT * FROM ship_via ORDER BY sort_order, name');
    }

    public function findShipVia(int $id): array|false
    {
        return Database::selectOne('SELECT * FROM ship_via WHERE id = ?', [$id]);
    }

    public function insertShipVia(string $name, int $sortOrder): void
    {
        Database::insert('INSERT INTO ship_via (name, sort_order) VALUES (?, ?)', [$name, $sortOrder]);
    }

    public function updateShipVia(int $id, string $name, int $sortOrder, int $isActive): void
    {
        Database::update('UPDATE ship_via SET name = ?, sort_order = ?, is_active = ? WHERE id = ?', [$name, $sortOrder, $isActive, $id]);
    }

    // -------------------------------------------------------------------------
    // Payment Terms
    // -------------------------------------------------------------------------

    public function allPaymentTerms(): array
    {
        return Database::select('SELECT * FROM payment_terms ORDER BY days_due, name');
    }

    public function findPaymentTerm(int $id): array|false
    {
        return Database::selectOne('SELECT * FROM payment_terms WHERE id = ?', [$id]);
    }

    public function insertPaymentTerm(array $d): void
    {
        Database::insert(
            'INSERT INTO payment_terms (name, days_due, is_credit_card, is_active) VALUES (?, ?, ?, 1)',
            [$d['name'], (int)$d['days_due'], (int)($d['is_credit_card'] ?? 0)]
        );
    }

    public function updatePaymentTerm(int $id, array $d): void
    {
        Database::update(
            'UPDATE payment_terms SET name = ?, days_due = ?, is_credit_card = ?, is_active = ? WHERE id = ?',
            [$d['name'], (int)$d['days_due'], (int)($d['is_credit_card'] ?? 0), (int)($d['is_active'] ?? 1), $id]
        );
    }

    // -------------------------------------------------------------------------
    // Tax Rates
    // -------------------------------------------------------------------------

    public function allTaxRates(): array
    {
        return Database::select('SELECT * FROM tax_rates ORDER BY state_code, name');
    }

    public function findTaxRate(int $id): array|false
    {
        return Database::selectOne('SELECT * FROM tax_rates WHERE id = ?', [$id]);
    }

    public function insertTaxRate(array $d): void
    {
        Database::insert(
            'INSERT INTO tax_rates (name, state_code, rate, is_active) VALUES (?, ?, ?, 1)',
            [$d['name'], strtoupper($d['state_code'] ?? ''), round((float)$d['rate'] / 100, 4)]
        );
    }

    public function updateTaxRate(int $id, array $d): void
    {
        Database::update(
            'UPDATE tax_rates SET name = ?, state_code = ?, rate = ?, is_active = ? WHERE id = ?',
            [$d['name'], strtoupper($d['state_code'] ?? ''), round((float)$d['rate'] / 100, 4), (int)($d['is_active'] ?? 1), $id]
        );
    }

    // -------------------------------------------------------------------------
    // Customer Messages
    // -------------------------------------------------------------------------

    public function allCustomerMessages(): array
    {
        return Database::select('SELECT * FROM customer_messages ORDER BY sort_order, id');
    }

    public function findCustomerMessage(int $id): array|false
    {
        return Database::selectOne('SELECT * FROM customer_messages WHERE id = ?', [$id]);
    }

    public function insertCustomerMessage(string $message, int $sortOrder): void
    {
        Database::insert('INSERT INTO customer_messages (message, sort_order) VALUES (?, ?)', [$message, $sortOrder]);
    }

    public function updateCustomerMessage(int $id, string $message, int $sortOrder, int $isActive): void
    {
        Database::update(
            'UPDATE customer_messages SET message = ?, sort_order = ?, is_active = ? WHERE id = ?',
            [$message, $sortOrder, $isActive, $id]
        );
    }

    // -------------------------------------------------------------------------
    // Users
    // -------------------------------------------------------------------------

    public function allUsers(): array
    {
        return Database::select(
            'SELECT u.*, d.name AS department_name
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             WHERE u.deleted_at IS NULL
             ORDER BY u.first_name, u.last_name'
        );
    }

    public function findUser(int $id): array|false
    {
        return Database::selectOne('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL', [$id]);
    }

    public function insertUser(array $d): void
    {
        Database::insert(
            'INSERT INTO users (company_id, first_name, last_name, email, password, role, department_id, phone, title, commission_rate, rep_code, is_active)
             VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)',
            [
                $d['first_name'],
                $d['last_name'],
                strtolower(trim($d['email'])),
                password_hash($d['password'], PASSWORD_DEFAULT),
                $d['role'],
                ($d['department_id'] ?? '') !== '' ? (int)$d['department_id'] : null,
                $d['phone']           ?: null,
                $d['title']           ?: null,
                ($d['commission_rate'] ?? '') !== '' ? (float)$d['commission_rate'] : null,
                ($d['rep_code']        ?? '') !== '' ? $d['rep_code']               : null,
            ]
        );
    }

    public function updateUser(int $id, array $d): void
    {
        $sql = 'UPDATE users SET first_name=?, last_name=?, email=?, role=?, department_id=?, phone=?, title=?, commission_rate=?, rep_code=?, is_active=?';
        $params = [
            $d['first_name'],
            $d['last_name'],
            strtolower(trim($d['email'])),
            $d['role'],
            ($d['department_id'] ?? '') !== '' ? (int)$d['department_id'] : null,
            $d['phone']           ?: null,
            $d['title']           ?: null,
            ($d['commission_rate'] ?? '') !== '' ? (float)$d['commission_rate'] : null,
            ($d['rep_code']        ?? '') !== '' ? $d['rep_code']               : null,
            (int)($d['is_active'] ?? 1),
        ];
        if (!empty($d['password'])) {
            $sql .= ', password=?';
            $params[] = password_hash($d['password'], PASSWORD_DEFAULT);
        }
        $sql .= ' WHERE id = ?';
        $params[] = $id;
        Database::update($sql, $params);
    }

    // -------------------------------------------------------------------------
    // Customer Types
    // -------------------------------------------------------------------------

    public function allCustomerTypes(): array
    {
        return Database::select('SELECT * FROM customer_types ORDER BY sort_order, name');
    }

    public function findCustomerType(int $id): array|false
    {
        return Database::selectOne('SELECT * FROM customer_types WHERE id = ?', [$id]);
    }

    public function insertCustomerType(string $name, int $sortOrder): void
    {
        Database::insert('INSERT INTO customer_types (name, sort_order) VALUES (?, ?)', [$name, $sortOrder]);
    }

    public function updateCustomerType(int $id, string $name, int $sortOrder, int $isActive): void
    {
        Database::update(
            'UPDATE customer_types SET name=?, sort_order=?, is_active=? WHERE id=?',
            [$name, $sortOrder, $isActive, $id]
        );
    }

    // -------------------------------------------------------------------------
    // Departments
    // -------------------------------------------------------------------------

    public function allDepartments(): array
    {
        return Database::select('SELECT * FROM departments ORDER BY sort_order, name');
    }

    public function findDepartment(int $id): array|false
    {
        return Database::selectOne('SELECT * FROM departments WHERE id = ?', [$id]);
    }

    public function insertDepartment(string $name, string $description, int $sortOrder): void
    {
        Database::insert(
            'INSERT INTO departments (name, description, sort_order) VALUES (?, ?, ?)',
            [$name, $description ?: null, $sortOrder]
        );
    }

    public function updateDepartment(int $id, string $name, string $description, int $sortOrder, int $isActive): void
    {
        Database::update(
            'UPDATE departments SET name=?, description=?, sort_order=?, is_active=? WHERE id=?',
            [$name, $description ?: null, $sortOrder, $isActive, $id]
        );
    }

    // -------------------------------------------------------------------------
    // Settings
    // -------------------------------------------------------------------------

    public function getLeadRouting(): array
    {
        $rows = Database::select(
            "SELECT setting_key, setting_value FROM settings
             WHERE setting_key LIKE 'lead_routing_%'"
        );
        $map = [];
        foreach ($rows as $row) {
            $map[$row['setting_key']] = $row['setting_value'];
        }
        return $map;
    }

    public function setSetting(string $key, ?string $value): void
    {
        Database::update(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
            [$key, $value]
        );
    }

    public function allActiveUsers(): array
    {
        return Database::select(
            'SELECT id, first_name, last_name, email, role FROM users
             WHERE is_active = 1 AND deleted_at IS NULL
             ORDER BY first_name, last_name'
        );
    }

    // -------------------------------------------------------------------------
    // Company Info
    // -------------------------------------------------------------------------

    public function getCompany(): array|false
    {
        return Database::selectOne('SELECT * FROM companies WHERE id = 1');
    }

    public function updateCompany(array $d, ?string $logoPath = null): void
    {
        $logoSql = $logoPath !== null ? ', logo=?' : '';
        $params  = [
            $d['name'], $d['legal_name'] ?: null, $d['phone'] ?: null,
            $d['email'] ?: null, $d['website'] ?: null,
            $d['address_line1'] ?: null, $d['address_line2'] ?: null,
            $d['city'] ?: null, $d['state'] ?: null, $d['postal_code'] ?: null,
            ($d['default_tax_rate_id']     ?? '') !== '' ? (int)$d['default_tax_rate_id']     : null,
            ($d['default_payment_term_id'] ?? '') !== '' ? (int)$d['default_payment_term_id'] : null,
            ($d['default_ship_via_id']     ?? '') !== '' ? (int)$d['default_ship_via_id']     : null,
        ];
        if ($logoPath !== null) {
            $params[] = $logoPath;
        }
        Database::update(
            "UPDATE companies SET name=?, legal_name=?, phone=?, email=?, website=?,
             address_line1=?, address_line2=?, city=?, state=?, postal_code=?,
             default_tax_rate_id=?, default_payment_term_id=?, default_ship_via_id=?
             {$logoSql} WHERE id=1",
            $params
        );
    }

    public function getCompanyDefaults(): array
    {
        $co = Database::selectOne(
            'SELECT default_tax_rate_id, default_payment_term_id, default_ship_via_id FROM companies WHERE id = 1'
        );
        return $co ?: [];
    }
}

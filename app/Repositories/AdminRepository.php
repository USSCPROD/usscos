<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class AdminRepository
{
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

<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class AdminRepository
{
    // -------------------------------------------------------------------------
    // Ship Via
    // -------------------------------------------------------------------------

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

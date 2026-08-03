<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class VendorRepository
{
    public function paginate(int $page, int $perPage, string $search = ''): array
    {
        $conditions = [];
        $params     = [];

        if ($search !== '') {
            $conditions[] = '(company_name LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)';
            $s = '%' . $search . '%';
            array_push($params, $s, $s, $s, $s);
        }

        $where  = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $offset = ($page - 1) * $perPage;

        $total = (int)(Database::selectOne(
            "SELECT COUNT(*) AS total FROM vendors $where", $params
        )['total'] ?? 0);

        $rows = Database::select(
            "SELECT v.*, pt.name AS payment_term_name
             FROM vendors v
             LEFT JOIN payment_terms pt ON pt.id = v.payment_term_id
             $where
             ORDER BY v.company_name ASC
             LIMIT $perPage OFFSET $offset",
            $params
        );

        return [
            'data'         => $rows,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int)ceil($total / $perPage),
            'from'         => $offset + 1,
            'to'           => min($offset + $perPage, $total),
        ];
    }

    public function findById(int $id): array|false
    {
        return Database::selectOne(
            "SELECT v.*, pt.name AS payment_term_name
             FROM vendors v
             LEFT JOIN payment_terms pt ON pt.id = v.payment_term_id
             WHERE v.id = ? LIMIT 1",
            [$id]
        ) ?: false;
    }

    public function all(): array
    {
        return Database::select(
            "SELECT id, company_name, first_name, last_name, phone, email, account_number
             FROM vendors WHERE is_active = 1 ORDER BY company_name ASC"
        );
    }

    public function insert(array $data): int
    {
        $pdo = Database::connection();
        $pdo->prepare("
            INSERT INTO vendors
                (company_name, first_name, last_name, email, phone, fax,
                 account_number, address_1, address_2, city, state, zip, country,
                 payment_term_id, tax_id, is_1099, is_active, notes)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ")->execute([
            trim($data['company_name'] ?? ''),
            trim($data['first_name'] ?? '') ?: null,
            trim($data['last_name'] ?? '') ?: null,
            trim($data['email'] ?? '') ?: null,
            trim($data['phone'] ?? '') ?: null,
            trim($data['fax'] ?? '') ?: null,
            trim($data['account_number'] ?? '') ?: null,
            trim($data['address_1'] ?? '') ?: null,
            trim($data['address_2'] ?? '') ?: null,
            trim($data['city'] ?? '') ?: null,
            trim($data['state'] ?? '') ?: null,
            trim($data['zip'] ?? '') ?: null,
            trim($data['country'] ?? 'US'),
            ($data['payment_term_id'] ?? '') !== '' ? (int)$data['payment_term_id'] : null,
            trim($data['tax_id'] ?? '') ?: null,
            isset($data['is_1099']) ? 1 : 0,
            isset($data['is_active']) ? (int)$data['is_active'] : 1,
            trim($data['notes'] ?? '') ?: null,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        Database::connection()->prepare("
            UPDATE vendors SET
                company_name     = ?,
                first_name       = ?,
                last_name        = ?,
                email            = ?,
                phone            = ?,
                fax              = ?,
                account_number   = ?,
                address_1        = ?,
                address_2        = ?,
                city             = ?,
                state            = ?,
                zip              = ?,
                country          = ?,
                payment_term_id  = ?,
                tax_id           = ?,
                is_1099          = ?,
                is_active        = ?,
                notes            = ?
            WHERE id = ?
        ")->execute([
            trim($data['company_name'] ?? ''),
            trim($data['first_name'] ?? '') ?: null,
            trim($data['last_name'] ?? '') ?: null,
            trim($data['email'] ?? '') ?: null,
            trim($data['phone'] ?? '') ?: null,
            trim($data['fax'] ?? '') ?: null,
            trim($data['account_number'] ?? '') ?: null,
            trim($data['address_1'] ?? '') ?: null,
            trim($data['address_2'] ?? '') ?: null,
            trim($data['city'] ?? '') ?: null,
            trim($data['state'] ?? '') ?: null,
            trim($data['zip'] ?? '') ?: null,
            trim($data['country'] ?? 'US'),
            ($data['payment_term_id'] ?? '') !== '' ? (int)$data['payment_term_id'] : null,
            trim($data['tax_id'] ?? '') ?: null,
            isset($data['is_1099']) ? 1 : 0,
            (int)($data['is_active'] ?? 1),
            trim($data['notes'] ?? '') ?: null,
            $id,
        ]);
    }

    public function getPaymentTerms(): array
    {
        return Database::select("SELECT id, name FROM payment_terms WHERE is_active = 1 ORDER BY name ASC");
    }
}

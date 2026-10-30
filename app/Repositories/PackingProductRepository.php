<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class PackingProductRepository
{
    public function invoiceLines(int $invoiceId): array
    {
        return Database::select(
            'SELECT product_id, quickbooks_item, qty FROM invoice_line_items WHERE invoice_id = ?',
            [$invoiceId]
        );
    }

    public function resolveItem(string $item): ?int
    {
        $item = trim($item);
        if ($item === '') {
            return null;
        }
        // Exact identifiers only. Conflicting SKU/item matches require review.
        $matches = Database::select(
            'SELECT id FROM products WHERE quickbooks_item = ? OR sku = ?',
            [$item, $item]
        );
        return count($matches) === 1 ? (int)$matches[0]['id'] : null;
    }
}

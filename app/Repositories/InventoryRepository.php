<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class InventoryRepository
{
    // Roles that can make inventory adjustments
    public const CAN_ADJUST = ['owner', 'admin', 'bookkeeper', 'manager', 'shipping'];

    public function paginateProducts(int $page, int $perPage, string $search = '', string $brand = '', string $filter = 'all'): array
    {
        $conditions = ['p.is_active = 1'];
        $params     = [];

        if ($search !== '') {
            $conditions[] = '(p.sku LIKE ? OR p.name LIKE ?)';
            $s = '%' . $search . '%';
            array_push($params, $s, $s);
        }

        if ($brand !== '') {
            $conditions[] = 'b.name = ?';
            $params[]     = $brand;
        }

        if ($filter === 'low') {
            $conditions[] = 'p.reorder_point IS NOT NULL AND p.qty_on_hand <= p.reorder_point';
        } elseif ($filter === 'out') {
            $conditions[] = 'p.qty_on_hand <= 0';
        }

        $where  = 'WHERE ' . implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $total = (int)(Database::selectOne(
            "SELECT COUNT(*) AS total
             FROM products p
             LEFT JOIN product_brands b ON b.id = p.brand_id
             $where", $params
        )['total'] ?? 0);

        $rows = Database::select(
            "SELECT p.id, p.sku, p.name, p.uom_code, p.color,
                    p.qty_on_hand, p.qty_on_sales_order, p.qty_on_po,
                    p.reorder_point, p.min_order_qty, p.cost,
                    b.name AS brand_name
             FROM products p
             LEFT JOIN product_brands b ON b.id = p.brand_id
             $where
             ORDER BY p.sku ASC
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

    public function getProductWithTransactions(int $id): array|false
    {
        $product = Database::selectOne(
            "SELECT p.*, b.name AS brand_name
             FROM products p
             LEFT JOIN product_brands b ON b.id = p.brand_id
             WHERE p.id = ? LIMIT 1",
            [$id]
        );

        if (!$product) return false;

        $transactions = Database::select(
            "SELECT it.*,
                    u.first_name AS user_first, u.last_name AS user_last
             FROM inventory_transactions it
             LEFT JOIN users u ON u.id = it.created_by
             WHERE it.product_id = ?
             ORDER BY it.transaction_date DESC, it.id DESC
             LIMIT 100",
            [$id]
        );

        return compact('product', 'transactions');
    }

    public function getAllBrands(): array
    {
        return Database::select("SELECT id, name FROM product_brands ORDER BY name ASC");
    }

    public function getLowStockCount(): int
    {
        return (int)(Database::selectOne(
            "SELECT COUNT(*) AS c FROM products
             WHERE is_active = 1 AND reorder_point IS NOT NULL AND qty_on_hand <= reorder_point"
        )['c'] ?? 0);
    }

    public function getOutOfStockCount(): int
    {
        return (int)(Database::selectOne(
            "SELECT COUNT(*) AS c FROM products WHERE is_active = 1 AND qty_on_hand <= 0"
        )['c'] ?? 0);
    }

    public function adjust(int $productId, float $qty, string $type, string $notes, int $userId): void
    {
        $pdo = Database::connection();

        // Get current qty
        $current = (float)(Database::selectOne(
            "SELECT qty_on_hand FROM products WHERE id = ?", [$productId]
        )['qty_on_hand'] ?? 0);

        $newQty = $type === 'set' ? $qty : $current + $qty;

        // Update product qty
        $pdo->prepare("UPDATE products SET qty_on_hand = ? WHERE id = ?")->execute([$newQty, $productId]);

        // Record transaction
        $txnQty = $type === 'set' ? ($qty - $current) : $qty;
        $pdo->prepare("
            INSERT INTO inventory_transactions
                (product_id, transaction_type, transaction_date, qty, qty_on_hand_after, notes, created_by)
            VALUES (?, 'adjustment', CURDATE(), ?, ?, ?, ?)
        ")->execute([$productId, $txnQty, $newQty, $notes ?: null, $userId]);
    }
}

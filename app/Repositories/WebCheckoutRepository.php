<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/** web_checkouts: online checkouts handed to Square. See migration 123. */
class WebCheckoutRepository
{
    public function create(array $d): int
    {
        Database::insert(
            "INSERT INTO web_checkouts
                (token, status, paid_by, items, priced_lines, subtotal, shipping_service, shipping_name, shipping_amount, total,
                 ship_zip, residential, login_id, customer_id, price_tier, square_environment)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$d['token'], $d['status'] ?? 'open', $d['paid_by'] ?? 'card', json_encode($d['items']), json_encode($d['lines']),
             $d['subtotal'], $d['shipping_service'], $d['shipping_name'], $d['shipping_amount'], $d['total'], $d['ship_zip'],
             $d['residential'] ? 1 : 0, $d['login_id'] ?? null, $d['customer_id'] ?? null, $d['price_tier'] ?? 'Retail',
             $d['square_environment']]
        );

        return (int)Database::lastInsertId();
    }

    public function setSquare(int $id, string $linkId, string $orderId): void
    {
        Database::update("UPDATE web_checkouts SET square_payment_link_id = ?, square_order_id = ? WHERE id = ?",
            [$linkId, $orderId, $id]);
    }

    public function findByToken(string $token): array|false
    {
        return preg_match('/^[0-9a-f]{32}$/', $token)
            ? Database::selectOne("SELECT * FROM web_checkouts WHERE token = ?", [$token]) : false;
    }

    public function findBySquareOrder(string $orderId): array|false
    {
        return Database::selectOne("SELECT * FROM web_checkouts WHERE square_order_id = ?", [$orderId]);
    }

    /** The checkout a sales order was made from: what shipping was quoted, to which ZIP. */
    public function forSalesOrder(int $soId): array|false
    {
        return Database::selectOne(
            "SELECT token, paid_by, ship_zip, shipping_name, shipping_amount FROM web_checkouts WHERE sales_order_id = ? LIMIT 1", [$soId]);
    }

    /** The row, locked for the rest of the current transaction: one order per payment. */
    public function lock(int $id): array|false
    {
        return Database::selectOne("SELECT * FROM web_checkouts WHERE id = ? FOR UPDATE", [$id]);
    }

    /** An order on terms: written straight to its sales order, no Square. */
    public function attachOrder(int $id, int $soId): void
    {
        Database::update("UPDATE web_checkouts SET status = 'ordered', sales_order_id = ?, ordered_at = NOW() WHERE id = ?", [$soId, $id]);
    }

    public function markOrdered(int $id, int $soId, string $paymentId): void
    {
        Database::update("UPDATE web_checkouts SET status = 'ordered', sales_order_id = ?, square_payment_id = ?,
                                 ordered_at = NOW(), error = NULL WHERE id = ?", [$soId, $paymentId, $id]);
    }

    public function markFailed(int $id, string $error): void
    {
        Database::update("UPDATE web_checkouts SET status = 'failed', error = ? WHERE id = ? AND status = 'open'",
            [mb_substr($error, 0, 2000), $id]);
    }

    /** A product's id and QuickBooks item, for a sales order line. */
    public function productForLine(string $sku): array
    {
        return Database::selectOne("SELECT id, quickbooks_item FROM products WHERE sku = ? LIMIT 1", [$sku]) ?: [];
    }

    /** A custom-stencil product (STEN-CUSTOM-TEXT): unpublished, non-inventory, made once. */
    public function ensureCustomProduct(string $sku, string $name): array
    {
        $found = $this->productForLine($sku);

        if ($found !== []) {
            return $found;
        }

        Database::insert(
            "INSERT INTO products (sku, name, quickbooks_item, item_type, pack_level, publish_to_website, is_active)
             VALUES (?, ?, ?, 'non_inventory_part', 'Each', 0, 1)",
            [$sku, $name, 'US SPECIALTY COATINGS:' . $sku]
        );

        return $this->productForLine($sku);
    }

    /** Open checkouts old enough that the customer has had time to pay, for reconciliation. */
    public function openOlderThan(int $minutes, int $maxDays = 7): array
    {
        return Database::select(
            "SELECT * FROM web_checkouts
             WHERE status = 'open' AND square_order_id IS NOT NULL
               AND created_at < NOW() - INTERVAL ? MINUTE AND created_at > NOW() - INTERVAL ? DAY
             ORDER BY id", [$minutes, $maxDays]);
    }
}

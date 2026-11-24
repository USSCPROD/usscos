<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/** All SQL for customer_product_prices. */
class CustomerPriceRepository
{
    /**
     * The agreed price for this customer, product and quantity.
     *
     * Highest qualifying break wins: with rows at 1, 25 and 100, ordering 50 takes the 25
     * row. ORDER BY min_qty DESC LIMIT 1 rather than fetching all and choosing in PHP,
     * because this runs on every line of every quote.
     */
    public function priceFor(int $customerId, int $productId, float $qty): ?array
    {
        return Database::selectOne(
            'SELECT id, min_qty, unit_price, note
               FROM customer_product_prices
              WHERE customer_id = :customer_id
                AND product_id  = :product_id
                AND min_qty    <= :qty
              ORDER BY min_qty DESC
              LIMIT 1',
            [':customer_id' => $customerId, ':product_id' => $productId, ':qty' => $qty]
        ) ?: null;
    }

    /**
     * Agreed prices for many products at once, keyed by product id.
     *
     * The product autocomplete returns fifteen rows on every keystroke. Calling priceFor()
     * per row would be fifteen queries per character typed; this is one.
     *
     * The highest qualifying break per product is picked in SQL, so the caller gets one row
     * per product and does not have to know the rule.
     *
     * @param int[] $productIds
     * @return array<int,array{min_qty:float,unit_price:float,note:?string}>
     */
    public function priceForMany(int $customerId, array $productIds, float $qty): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if ($ids === []) {
            return [];
        }

        $in = implode(',', array_fill(0, count($ids), '?'));

        $rows = Database::select(
            "SELECT cpp.product_id, cpp.min_qty, cpp.unit_price, cpp.note
               FROM customer_product_prices cpp
               JOIN (
                   SELECT product_id, MAX(min_qty) AS best
                     FROM customer_product_prices
                    WHERE customer_id = ? AND product_id IN ({$in}) AND min_qty <= ?
                    GROUP BY product_id
               ) pick ON pick.product_id = cpp.product_id AND pick.best = cpp.min_qty
              WHERE cpp.customer_id = ?",
            array_merge([$customerId], $ids, [$qty, $customerId])
        );

        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r['product_id']] = [
                'min_qty'    => (float)$r['min_qty'],
                'unit_price' => (float)$r['unit_price'],
                'note'       => $r['note'],
            ];
        }
        return $out;
    }

    /**
     * Every agreed price for a customer, for the pricing screen.
     *
     * Joins the product so the screen can show what the list price is beside what was
     * agreed - an agreed price that is now ABOVE list is the thing nobody notices, and it
     * happens whenever a list price is cut and the deals underneath it are not revisited.
     */
    public function forCustomer(int $customerId): array
    {
        return Database::select(
            'SELECT cpp.*, p.sku, p.name AS product_name, p.price AS list_price,
                    TRIM(CONCAT(COALESCE(u.first_name,\'\'), \' \', COALESCE(u.last_name,\'\'))) AS agreed_by
               FROM customer_product_prices cpp
               JOIN products p  ON p.id = cpp.product_id
               LEFT JOIN users u ON u.id = cpp.created_by
              WHERE cpp.customer_id = ?
              ORDER BY p.sku, cpp.min_qty',
            [$customerId]
        );
    }

    public function find(int $id): ?array
    {
        return Database::selectOne(
            'SELECT cpp.*, p.sku, p.name AS product_name
               FROM customer_product_prices cpp
               JOIN products p ON p.id = cpp.product_id
              WHERE cpp.id = ?',
            [$id]
        ) ?: null;
    }

    /**
     * Add or re-agree a price.
     *
     * ON DUPLICATE KEY so re-agreeing a figure updates the row the unique key already
     * covers, instead of inserting a second one for the same break that would win or lose
     * on row order.
     */
    public function upsert(int $customerId, int $productId, float $minQty, float $price, ?string $note, ?int $userId): void
    {
        Database::statement(
            'INSERT INTO customer_product_prices
                 (customer_id, product_id, min_qty, unit_price, note, created_by)
             VALUES (:customer_id, :product_id, :min_qty, :price, :note, :user_id)
             ON DUPLICATE KEY UPDATE
                 unit_price = VALUES(unit_price),
                 note       = VALUES(note),
                 created_by = VALUES(created_by)',
            [
                ':customer_id' => $customerId,
                ':product_id'  => $productId,
                ':min_qty'     => $minQty,
                ':price'       => $price,
                ':note'        => $note,
                ':user_id'     => $userId,
            ]
        );
    }

    public function delete(int $id): void
    {
        Database::delete('DELETE FROM customer_product_prices WHERE id = ?', [$id]);
    }

    /** How many customers have an agreed price on this product, for the product page. */
    public function countForProduct(int $productId): int
    {
        $row = Database::selectOne(
            'SELECT COUNT(DISTINCT customer_id) AS n FROM customer_product_prices WHERE product_id = ?',
            [$productId]
        );
        return (int) ($row['n'] ?? 0);
    }
}

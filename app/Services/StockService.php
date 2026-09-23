<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\StockRepository;

/**
 * Taking stock out when goods ship.
 *
 * Receiving puts stock in; without this the count only ever climbs. Shipment is the
 * other half, and it runs off the same `applyMovement()` path so that a shipment and a
 * receipt are the same kind of event with opposite signs, recorded the same way.
 */
class StockService
{
    public function __construct(
        private StockRepository $stock = new StockRepository(),
    ) {
    }

    /**
     * Deduct what is shipping on a sales order.
     *
     * Quantities come from the caller rather than being re-derived here, because the
     * caller has already worked out what is going on the invoice and the two must not
     * be able to disagree — billing for ten and deducting eight is exactly the drift
     * this whole exercise exists to stop.
     *
     * @param  list<array{product_id:int|string|null, qty:float}> $lines
     * @return list<array{product_id:int, location_id:int, qty:float, was_short:bool}>
     */
    public function shipLines(array $lines, array $meta = []): array
    {
        $productIds = [];
        foreach ($lines as $l) {
            if (!empty($l['product_id'])) {
                $productIds[] = (int)$l['product_id'];
            }
        }

        $stocked = $this->stock->stockedFlags($productIds);
        $moves   = [];

        foreach ($lines as $l) {
            $productId = (int)($l['product_id'] ?? 0);
            $qty       = (float)($l['qty'] ?? 0);

            // A free-text line, a service, freight or a discount moves no stock.
            if ($productId <= 0 || $qty <= 0 || ($stocked[$productId] ?? false) === false) {
                continue;
            }

            foreach ($this->stock->consume($productId, $qty, $meta) as $a) {
                $moves[] = ['product_id' => $productId] + $a;
            }
        }

        return $moves;
    }
}

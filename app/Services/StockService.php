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
     * Make the stock match what an invoice says went out.
     *
     * The invoice is the statement of what left the building, so stock follows it rather
     * than being moved separately and hoped to agree. Called on create, on edit, and by
     * shipping, it works out the difference between what has already moved for this
     * invoice and what the invoice now says — so calling it twice does nothing the second
     * time, and correcting a quantity moves only the correction.
     *
     * Without this, editing an invoice down from ten to eight would bill for eight and
     * leave ten deducted, which is the same disagreement in the other direction.
     *
     * @param list<array{product_id:int|string|null, qty:float}> $lines the invoice as it now stands
     */
    public function syncInvoice(int $invoiceId, array $lines, array $meta = []): void
    {
        $meta += ['reference_type' => 'invoice', 'reference_id' => $invoiceId];

        $wanted = [];
        foreach ($lines as $l) {
            $productId = (int)($l['product_id'] ?? 0);
            $qty       = (float)($l['qty'] ?? 0);

            if ($productId > 0 && $qty > 0) {
                $wanted[$productId] = ($wanted[$productId] ?? 0) + $qty;
            }
        }

        $alreadyNet = $this->stock->netMovedForInvoice($invoiceId);
        $stocked    = $this->stock->stockedFlags(
            array_merge(array_keys($wanted), array_keys($alreadyNet))
        );

        foreach (array_keys($wanted + $alreadyNet) as $productId) {
            // Freight, setup charges and discounts sit on invoices but have no physical
            // existence, so invoicing one moves nothing.
            if (($stocked[$productId] ?? false) === false) {
                continue;
            }

            // What the invoice says should have gone out, less what already has.
            $shouldBeOut = $wanted[$productId] ?? 0.0;
            $alreadyOut  = -($alreadyNet[$productId] ?? 0.0);
            $delta       = $shouldBeOut - $alreadyOut;

            if (abs($delta) < 0.0001) {
                continue;
            }

            if ($delta > 0) {
                $this->stock->consume($productId, $delta, $meta);
            } else {
                $this->stock->restore($productId, -$delta, $meta);
            }
        }
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

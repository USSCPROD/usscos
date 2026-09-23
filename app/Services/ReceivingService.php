<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Repositories\StockRepository;

/**
 * Booking goods in.
 *
 * This is where stock first enters USSCOS, and the screen that decides whether the count
 * is worth anything. Paint arriving without being recorded is the single biggest cause of
 * the figures being wrong today, so the job of this service is to make recording it
 * quicker than not recording it.
 */
class ReceivingService
{
    public function __construct(
        private StockRepository $stock = new StockRepository(),
    ) {
    }

    /**
     * Work out what a scanned code means, and suggest a quantity.
     *
     * A case barcode implies a whole case, and a pallet implies a pallet's worth — but the
     * suggestion is only ever a default. The receiver confirms it, because a batch does
     * not always yield what the pallet quantity says and 1-gallon pallets vary outright.
     *
     * @return array{found:bool, message:string, product?:array, suggested_qty?:float, basis?:string, varies?:bool}
     */
    public function lookup(string $code, string $unit = 'unit'): array
    {
        $match = $this->stock->resolveScan($code);

        if ($match === null) {
            return ['found' => false, 'message' => 'Not recognised: ' . $code];
        }

        $p           = $match['product'];
        $perCase     = (int)($p['units_per_case'] ?? 0);
        $palletPacks = (int)($p['pallet_qty'] ?? 0);
        $varies      = (bool)($p['pallet_qty_varies'] ?? false);

        // Scanning a case barcode means a case even if the operator left the selector on
        // "unit" — the barcode is the more reliable signal.
        if ($match['is_case'] && $unit === 'unit') {
            $unit = 'case';
        }

        [$qty, $basis] = match ($unit) {
            'case'   => $perCase > 0
                ? [(float)$perCase, $perCase . ' per case']
                : [1.0, 'no case quantity on this product — enter units'],
            'pallet' => ($perCase > 0 && $palletPacks > 0)
                ? [(float)$perCase * $palletPacks, $palletPacks . ' packs x ' . $perCase . ' per pallet']
                : [1.0, 'no pallet quantity on this product — enter units'],
            default  => [1.0, 'single unit'],
        };

        return [
            'found'         => true,
            'message'       => $p['name'],
            'product'       => $p,
            'suggested_qty' => $qty,
            'basis'         => $basis,
            'varies'        => $varies && $unit === 'pallet',
        ];
    }

    /**
     * Book stock in.
     *
     * @throws \RuntimeException with a message meant for the person receiving
     */
    public function receive(array $input): array
    {
        $productId  = (int)($input['product_id'] ?? 0);
        $locationId = (int)($input['location_id'] ?? 0);
        $qty        = (float)($input['qty'] ?? 0);

        if ($productId <= 0) {
            throw new \RuntimeException('Scan or choose a product first.');
        }
        if ($locationId <= 0) {
            throw new \RuntimeException('Choose where it is going.');
        }
        if ($qty <= 0) {
            throw new \RuntimeException('Enter how many arrived.');
        }

        $expected = ($input['qty_expected'] ?? '') !== '' ? (float)$input['qty_expected'] : null;

        $notes = trim((string)($input['notes'] ?? ''));

        // A corrected quantity is worth recording as a fact, not just a number. Repeated
        // corrections on one product are how a wrong pallet quantity gets noticed.
        if ($expected !== null && abs($expected - $qty) > 0.0001) {
            $short = $expected > $qty;
            $note  = sprintf(
                '%s expected %s, received %s',
                $short ? 'Short —' : 'Over —',
                rtrim(rtrim(number_format($expected, 2), '0'), '.'),
                rtrim(rtrim(number_format($qty, 2), '0'), '.')
            );
            $notes = $notes !== '' ? $note . '. ' . $notes : $note;
        }

        $txnId = $this->stock->applyMovement([
            'product_id'     => $productId,
            'qty'            => $qty,
            'qty_expected'   => $expected,
            'type'           => 'receipt',
            'to_location_id' => $locationId,
            'reference_type' => ($input['reference_num'] ?? '') !== '' ? 'adjustment' : null,
            'reference_num'  => trim((string)($input['reference_num'] ?? '')) ?: null,
            'notes'          => $notes ?: null,
            'user_id'        => Auth::id(),
        ]);

        return ['transaction_id' => $txnId, 'qty' => $qty];
    }

    public function locations(): array
    {
        return $this->stock->storableLocations();
    }

    public function recentReceipts(int $limit = 25): array
    {
        return $this->stock->recentMovements('receipt', $limit);
    }
}

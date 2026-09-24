<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Repositories\StockRepository;
use App\Repositories\TransferRepository;

/**
 * Moving stock between buildings.
 *
 * Built around one decision: a transfer is a record, not a place. A location can say where
 * something is, but not where it came from and where it is going, which is the only
 * interesting thing about a pallet on a truck. So the transfer is the thing that exists.
 *
 * Stock leaves when it is scanned out and arrives when it is scanned in. In between it
 * belongs to neither building and the on-hand total genuinely dips — correct, because
 * paint on a truck cannot be picked at either end.
 *
 * Both ends are scanned deliberately. Scanning once would make the far building's count a
 * matter of trust; scanning twice turns a pallet that never arrives into a dated, named
 * discrepancy instead of a slow mystery.
 */
class TransferService
{
    public function __construct(
        private TransferRepository $repo = new TransferRepository(),
        private StockRepository $stock = new StockRepository(),
    ) {
    }

    public function start(array $input): int
    {
        $from = (int)($input['from_location_id'] ?? 0);
        $to   = (int)($input['to_location_id'] ?? 0);

        if ($from <= 0 || $to <= 0) {
            throw new \RuntimeException('Choose where the stock is coming from and where it is going.');
        }
        if ($from === $to) {
            throw new \RuntimeException('A transfer needs two different places.');
        }

        return $this->repo->create($from, $to, trim((string)($input['notes'] ?? '')) ?: null, Auth::id());
    }

    /**
     * Put an item on a transfer that has not left yet.
     *
     * Quantities add up rather than replace, because loading a truck is a scan per pallet
     * and the natural action is to scan the same product several times.
     */
    public function addItem(int $transferId, string $code, float $qty): array
    {
        $transfer = $this->require($transferId, 'draft', 'Items can only be added before it leaves.');

        if ($qty <= 0) {
            throw new \RuntimeException('Enter how many.');
        }

        $match = $this->stock->resolveScan($code);

        if ($match === null) {
            throw new \RuntimeException('Not recognised: ' . $code);
        }

        $product = $match['product'];
        $this->repo->addToLine($transferId, (int)$product['id'], $qty);

        return [
            'product'   => $product,
            'available' => $this->stock->qtyAt((int)$product['id'], (int)$transfer['from_location_id']),
        ];
    }

    public function removeItem(int $transferId, int $lineId): void
    {
        $this->require($transferId, 'draft', 'Items can only be changed before it leaves.');
        $this->repo->removeLine($lineId);
    }

    /**
     * Scan the truck out. Stock leaves the source building now.
     *
     * One transaction for the whole load: a transfer that took half its stock out of 1000
     * and then failed would be worse than one that never started.
     */
    public function send(int $transferId): array
    {
        $transfer = $this->require($transferId, 'draft', 'This transfer has already left.');
        $lines    = $this->repo->lines($transferId);

        if (!$lines) {
            throw new \RuntimeException('Nothing on this transfer yet.');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            foreach ($lines as $l) {
                if ((float)$l['qty_sent'] <= 0) {
                    continue;
                }

                $this->stock->applyMovement([
                    'product_id'       => (int)$l['product_id'],
                    'qty'              => (float)$l['qty_sent'],
                    'type'             => 'transfer',
                    'from_location_id' => (int)$transfer['from_location_id'],
                    'reference_type'   => 'transfer',
                    'reference_id'     => $transferId,
                    'reference_num'    => $transfer['transfer_number'],
                    'notes'            => 'Sent to ' . $transfer['to_code'],
                    'user_id'          => Auth::id(),
                ]);
            }

            $this->repo->markSent($transferId, Auth::id());
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        return ['lines' => count($lines)];
    }

    /**
     * Scan an item in at the far end. Stock arrives now.
     *
     * Receiving more than was sent is allowed — the truck is the truth, the paperwork is
     * not — and the difference shows on the transfer for somebody to explain.
     */
    public function receiveItem(int $transferId, string $code, float $qty): array
    {
        $transfer = $this->require($transferId, 'in_transit', 'This transfer is not on its way.');

        if ($qty <= 0) {
            throw new \RuntimeException('Enter how many arrived.');
        }

        $match = $this->stock->resolveScan($code);

        if ($match === null) {
            throw new \RuntimeException('Not recognised: ' . $code);
        }

        $product = $match['product'];
        $line    = $this->repo->findLine($transferId, (int)$product['id']);

        if ($line === null) {
            throw new \RuntimeException(
                $product['name'] . ' is not on this transfer. Check it came off the right truck.'
            );
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $this->stock->applyMovement([
                'product_id'     => (int)$product['id'],
                'qty'            => $qty,
                'type'           => 'transfer',
                'to_location_id' => (int)$transfer['to_location_id'],
                'reference_type' => 'transfer',
                'reference_id'   => $transferId,
                'reference_num'  => $transfer['transfer_number'],
                'notes'          => 'Received from ' . $transfer['from_code'],
                'user_id'        => Auth::id(),
            ]);

            $this->repo->addReceived((int)$line['id'], $qty);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        $after = $this->repo->findLine($transferId, (int)$product['id']);

        return [
            'product'   => $product,
            'sent'      => (float)$line['qty_sent'],
            'received'  => (float)($after['qty_received'] ?? 0),
        ];
    }

    /**
     * Close the transfer at the far end.
     *
     * A shortfall does not block closing — the truck has gone and the driver is not coming
     * back — but it is recorded as short rather than received, and the missing stock stays
     * visible instead of quietly evaporating.
     */
    public function close(int $transferId, ?string $note = null): array
    {
        $this->require($transferId, 'in_transit', 'This transfer is not on its way.');

        $lines   = $this->repo->lines($transferId);
        $missing = [];

        foreach ($lines as $l) {
            $gap = (float)$l['qty_sent'] - (float)$l['qty_received'];

            if (abs($gap) > 0.0001) {
                $missing[] = ['product' => $l['product_name'], 'sku' => $l['sku'], 'gap' => $gap];

                if ($note) {
                    $this->repo->setLineNote((int)$l['id'], $note);
                }
            }
        }

        $this->repo->markReceived($transferId, Auth::id(), $missing !== []);

        return ['missing' => $missing];
    }

    public function cancel(int $transferId): void
    {
        $this->require($transferId, 'draft', 'A transfer that has left cannot be cancelled — receive it instead.');
        $this->repo->setStatus($transferId, 'cancelled');
    }

    public function get(int $id): ?array
    {
        return $this->repo->find($id);
    }

    public function lines(int $id): array
    {
        return $this->repo->lines($id);
    }

    public function all(): array
    {
        return $this->repo->all();
    }

    public function inTransit(): array
    {
        return $this->repo->inTransitSummary();
    }

    public function locations(): array
    {
        return $this->stock->storableLocations();
    }

    public function qtyAt(int $productId, int $locationId): float
    {
        return $this->stock->qtyAt($productId, $locationId);
    }

    /** Fetch a transfer and insist it is in the state the caller expects. */
    private function require(int $id, string $status, string $message): array
    {
        $transfer = $this->repo->find($id);

        if ($transfer === null) {
            throw new \RuntimeException('That transfer no longer exists.');
        }

        if ($transfer['status'] !== $status) {
            throw new \RuntimeException($message);
        }

        return $transfer;
    }
}

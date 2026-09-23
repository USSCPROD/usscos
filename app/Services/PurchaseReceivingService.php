<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\PurchaseOrderRepository;
use App\Repositories\StockRepository;

/**
 * Receiving goods against a purchase order.
 *
 * A PO is an expectation. We order 600 cases from TCC, the batch yields less, and the PO
 * gets edited afterwards to what was really made — sometimes twice, once for the bulk and
 * again after canning. Whether anyone remembers to edit it is exactly the problem, so
 * this records what actually arrived and reports the difference rather than trusting the
 * paperwork.
 *
 * It never caps a receipt at what was ordered. The previous code did, which meant 620
 * cases arriving against a PO for 600 quietly became 600 — reality trimmed to fit the
 * paperwork, with no error and no note.
 */
class PurchaseReceivingService
{
    public function __construct(
        private PurchaseOrderRepository $orders = new PurchaseOrderRepository(),
        private StockRepository $stock = new StockRepository(),
    ) {
    }

    /**
     * Book in one or more lines of a PO.
     *
     * @param  array<int|string,mixed> $quantities  line id => quantity received
     * @return array{received:int, variances:int}
     * @throws \RuntimeException with a message intended for the receiver
     */
    public function receive(int $poId, array $quantities, int $locationId, int $userId, string $notes = ''): array
    {
        if ($locationId <= 0) {
            throw new \RuntimeException('Choose where the delivery is going.');
        }

        $lines = $this->orders->getLinesById($poId);
        $byId  = [];
        foreach ($lines as $l) {
            $byId[(int)$l['id']] = $l;
        }

        $poNumber  = (string)($this->orders->findById($poId)['po_number'] ?? $poId);
        $received  = 0;
        $variances = 0;

        $pdo = Database::connection();
        $ownTransaction = !$pdo->inTransaction();

        if ($ownTransaction) {
            $pdo->beginTransaction();
        }

        try {
            foreach ($quantities as $lineId => $qty) {
                $qty  = (float)$qty;
                $line = $byId[(int)$lineId] ?? null;

                if ($qty <= 0 || $line === null) {
                    continue;
                }

                $ordered     = (float)$line['qty_ordered'];
                $alreadyIn   = (float)$line['qty_received'];
                $outstanding = $ordered - $alreadyIn;

                // Stock moves through the same path as every other movement, so the
                // transaction log and the per-location balance cannot disagree.
                if (!empty($line['product_id'])) {
                    $this->stock->applyMovement([
                        'product_id'     => (int)$line['product_id'],
                        'qty'            => $qty,
                        'qty_expected'   => $outstanding > 0 ? $outstanding : null,
                        'type'           => 'receipt',
                        'to_location_id' => $locationId,
                        'reference_type' => 'purchase_order',
                        'reference_id'   => $poId,
                        'reference_num'  => $poNumber,
                        'notes'          => $notes ?: null,
                        'user_id'        => $userId,
                    ]);
                }

                // Uncapped on purpose. If more arrived than was ordered, that is what
                // arrived, and the variance queue is where somebody decides what it means.
                $this->orders->addReceivedQty((int)$lineId, $qty);

                $received++;

                if (abs(($alreadyIn + $qty) - $ordered) > 0.0001) {
                    $variances++;
                }
            }

            $this->orders->recalcStatusFor($poId);

            if ($ownTransaction) {
                $pdo->commit();
            }
        } catch (\Throwable $e) {
            if ($ownTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        return ['received' => $received, 'variances' => $variances];
    }

    /**
     * Lines where what arrived does not match what was ordered, and nobody has said why.
     *
     * This is the queue the user asked for: a receipt that silently disagrees with its PO
     * is how the count and the paperwork drift apart, and the drift is invisible unless
     * something lists it.
     */
    public function openVariances(): array
    {
        return $this->orders->openVariances();
    }

    /**
     * Close a variance by setting the PO to what actually arrived.
     *
     * The normal outcome for TCC: the batch yielded what it yielded, and the PO follows
     * the receipt rather than the other way round.
     */
    public function acceptReceived(int $lineId, int $userId, string $note = ''): void
    {
        $this->orders->setOrderedToReceived($lineId, $userId, $note ?: 'PO set to the quantity actually received');
    }

    /**
     * Close a variance while leaving the PO alone.
     *
     * For when the difference is expected — the rest is still coming, or the overage was
     * agreed. The reason is required, because a variance closed without one is just a
     * variance that got hidden.
     */
    public function acknowledge(int $lineId, int $userId, string $note): void
    {
        if (trim($note) === '') {
            throw new \RuntimeException('Say why the quantities differ — that is the whole point of the note.');
        }

        $this->orders->acknowledgeVariance($lineId, $userId, trim($note));
    }
}

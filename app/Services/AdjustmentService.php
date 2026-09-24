<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Repositories\StockRepository;

/**
 * Correcting the count.
 *
 * This is the release valve, and the system needs one. Receiving, shipping and PO receipts
 * all assume that when reality and the count disagree, somebody can put it right. Without
 * that, the first disagreement sends people around the system — which is exactly how the
 * present numbers rotted.
 *
 * Every adjustment needs a reason, and the reason is a code rather than prose, because the
 * questions worth asking are asked across many adjustments at once: what are we losing to
 * damage in a year, is one product always short, is one location worse than the others.
 *
 * Adjustments upward matter as much as downward. A system that only lets people write
 * stock off quietly teaches them to hide everything else.
 */
class AdjustmentService
{
    public const REASONS = [
        'count_correction' => 'Count correction — physical count disagreed',
        'damaged'          => 'Damaged — dented, leaking, unsellable',
        'spilled'          => 'Spilled or lost in handling',
        'expired'          => 'Expired — past shelf life',
        'found'            => 'Found — turned up, an upward correction',
        'sample'           => 'Sample or testing',
        'internal_use'     => 'Used internally rather than sold',
        'theft'            => 'Known loss',
        'other'            => 'Other — explain in the note',
    ];

    /** Reasons where a bare note is not enough of an explanation. */
    private const NOTE_REQUIRED = ['other', 'theft'];

    public function __construct(
        private StockRepository $stock = new StockRepository(),
    ) {
    }

    /**
     * Adjust one product at one location.
     *
     * Two ways in, because people think both ways. "Set to 40" is what somebody holding a
     * clipboard says; "take 3 off" is what somebody who just dropped a pail says. They
     * become the same movement.
     *
     * @return array{transaction_id:int, delta:float, before:float, after:float}
     * @throws \RuntimeException with a message meant for the person adjusting
     */
    public function adjust(array $input): array
    {
        $productId  = (int)($input['product_id'] ?? 0);
        $locationId = (int)($input['location_id'] ?? 0);
        $mode       = (string)($input['mode'] ?? 'set');
        $reason     = (string)($input['reason_code'] ?? '');
        $note       = trim((string)($input['notes'] ?? ''));

        if ($productId <= 0) {
            throw new \RuntimeException('Scan or choose a product first.');
        }
        if ($locationId <= 0) {
            throw new \RuntimeException('Choose which location you are correcting.');
        }
        if (!isset(self::REASONS[$reason])) {
            throw new \RuntimeException('Choose a reason — an adjustment without one tells nobody anything later.');
        }
        if (in_array($reason, self::NOTE_REQUIRED, true) && $note === '') {
            throw new \RuntimeException('That reason needs a note saying what happened.');
        }

        $qtyRaw = (string)($input['qty'] ?? '');

        if ($qtyRaw === '' || !is_numeric($qtyRaw)) {
            throw new \RuntimeException('Enter a quantity.');
        }

        $before = $this->stock->qtyAt($productId, $locationId);
        $qty    = (float)$qtyRaw;

        $delta = match ($mode) {
            'set'    => $qty - $before,
            'add'    => $qty,
            'remove' => -$qty,
            default  => throw new \RuntimeException('Unknown adjustment type.'),
        };

        if ($mode !== 'set' && $qty <= 0) {
            throw new \RuntimeException('Enter how many to ' . $mode . '.');
        }

        if (abs($delta) < 0.0001) {
            throw new \RuntimeException(
                'The count already says ' . $this->fmt($before) . ' there — nothing to change.'
            );
        }

        // A counted figure is worth more than the arithmetic: it says somebody actually
        // looked. Recorded even when the count agreed, which is why it happens before the
        // movement rather than as part of it.
        if ($mode === 'set') {
            $this->stock->markCounted($productId, $locationId, Auth::id());
        }

        $txnId = $this->stock->adjustAt($productId, $locationId, $delta, [
            'reason_code' => $reason,
            'notes'       => $this->describe($mode, $reason, $before, $qty, $note),
            'user_id'     => Auth::id(),
        ]);

        return [
            'transaction_id' => $txnId,
            'delta'          => $delta,
            'before'         => $before,
            'after'          => $before + $delta,
        ];
    }

    /**
     * The note that gets stored.
     *
     * A count correction records what the system had said, because that difference is the
     * whole finding — "set to 40" means nothing a year later without knowing it had said 47.
     */
    private function describe(string $mode, string $reason, float $before, float $qty, string $note): string
    {
        $parts = [];

        if ($mode === 'set') {
            $parts[] = 'Counted ' . $this->fmt($qty) . ', system said ' . $this->fmt($before);
        }

        if ($note !== '') {
            $parts[] = $note;
        }

        return implode('. ', $parts) ?: self::REASONS[$reason];
    }

    private function fmt(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2), '0'), '.');
    }

    public function stockByLocation(int $productId): array
    {
        return $this->stock->stockByLocation($productId);
    }

    public function locations(): array
    {
        return $this->stock->storableLocations();
    }

    public function recent(int $limit = 25): array
    {
        return $this->stock->recentAdjustments($limit);
    }

    public function negativeStock(): array
    {
        return $this->stock->negativeStock();
    }

    public function lookup(string $code): ?array
    {
        $match = $this->stock->resolveScan($code);

        return $match === null ? null : $match['product'];
    }
}

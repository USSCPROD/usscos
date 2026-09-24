<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Repositories\CountRepository;
use App\Repositories\StockRepository;

/**
 * Cycle counting — rolling counts, so an error surfaces in days rather than at year end.
 *
 * Three decisions shape this:
 *
 * COUNTING IS BLIND. The counter is not shown what the system expects. Given the expected
 * figure, a tired person at the end of a shift confirms it instead of counting it, and the
 * count becomes a transcription of the very number it was meant to check.
 *
 * THE EXPECTED FIGURE IS TAKEN AT THE MOMENT OF COUNTING, not when the sheet was made.
 * Stock keeps moving while somebody walks the racks, and comparing a fresh count against a
 * stale snapshot invents variances that are really just shipments.
 *
 * NOTHING MOVES UNTIL IT IS REVIEWED. A count is a claim about reality; a claim worth
 * acting on is worth looking at first. Applying posts ordinary adjustments through the same
 * path as everything else, so a counted correction is as traceable as any other movement.
 */
class CountService
{
    public function __construct(
        private CountRepository $repo = new CountRepository(),
        private StockRepository $stock = new StockRepository(),
    ) {
    }

    /**
     * Open a count sheet for a location, seeded with what the system believes is there.
     *
     * @return array{id:int, lines:int}
     */
    public function open(array $input): array
    {
        $locationId = (int)($input['location_id'] ?? 0);
        $type       = (string)($input['count_type'] ?? 'cycle');

        if ($locationId <= 0) {
            throw new \RuntimeException('Choose which location is being counted.');
        }
        if (!in_array($type, ['cycle', 'full'], true)) {
            throw new \RuntimeException('Unknown kind of count.');
        }

        $id    = $this->repo->create($locationId, $type, trim((string)($input['notes'] ?? '')) ?: null, Auth::id());
        $lines = $this->repo->seedFromLocation($id, $locationId);

        return ['id' => $id, 'lines' => $lines];
    }

    /**
     * Record a counted quantity.
     *
     * Zero is a real answer and is stored as zero — "counted, and there are none" is a
     * different fact from "not counted yet", and the two must not collapse into each other.
     */
    public function count(int $countId, string $code, string $qtyRaw, ?string $note = null): array
    {
        $count = $this->require($countId, 'counting', 'This count is no longer being counted.');

        if ($qtyRaw === '' || !is_numeric($qtyRaw)) {
            throw new \RuntimeException('Enter how many are there — zero is a valid answer.');
        }

        $qty = (float)$qtyRaw;

        if ($qty < 0) {
            throw new \RuntimeException('A count cannot be negative. If stock is missing, count what is there.');
        }

        $match = $this->stock->resolveScan($code);

        if ($match === null) {
            throw new \RuntimeException('Not recognised: ' . $code);
        }

        $product = $match['product'];

        // Counting something that is not on the sheet is the point, not an edge case —
        // stock turning up where the system says there is none is exactly the drift these
        // counts exist to find.
        $line  = $this->repo->findLine($countId, (int)$product['id']);
        $added = $line === null;
        $lineId = $added ? $this->repo->addLine($countId, (int)$product['id']) : (int)$line['id'];

        $expected = $this->stock->qtyAt((int)$product['id'], (int)$count['location_id']);

        $this->repo->recordCount($lineId, $qty, $expected, Auth::id(), $note);

        // The counter is told what they counted, never what was expected.
        return [
            'product'    => $product,
            'counted'    => $qty,
            'was_added'  => $added,
            'recount'    => !$added && $line['counted_qty'] !== null,
        ];
    }

    /** Move a finished sheet to review, where the variances become visible. */
    public function submit(int $countId): array
    {
        $this->require($countId, 'counting', 'This count is not being counted.');

        $lines    = $this->repo->linesForReview($countId);
        $uncounted = array_filter($lines, fn($l) => $l['counted_qty'] === null);

        if (count($uncounted) === count($lines)) {
            throw new \RuntimeException('Nothing has been counted yet.');
        }

        $this->repo->setStatus($countId, 'review');

        return ['uncounted' => count($uncounted), 'counted' => count($lines) - count($uncounted)];
    }

    public function reopen(int $countId): void
    {
        $this->require($countId, 'review', 'Only a count awaiting review can be reopened.');
        $this->repo->setStatus($countId, 'counting');
    }

    /**
     * Apply the count: post an adjustment for every line that disagrees.
     *
     * Uncounted lines are left alone. Not counting something is not evidence that there is
     * none of it, and treating a blank as a zero would write off stock nobody looked at.
     *
     * @return array{adjusted:int, skipped:int, net:float}
     */
    public function apply(int $countId): array
    {
        $count = $this->require($countId, 'review', 'This count is not ready to apply.');
        $lines = $this->repo->linesForReview($countId);

        $adjusted = 0;
        $skipped  = 0;
        $net      = 0.0;

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            foreach ($lines as $l) {
                if ($l['counted_qty'] === null) {
                    $skipped++;
                    continue;
                }

                // Re-read rather than trusting the figure taken at counting time: stock may
                // have moved since, and the adjustment has to land on what is there now or
                // it will overshoot.
                $current = $this->stock->qtyAt((int)$l['product_id'], (int)$count['location_id']);
                $delta   = (float)$l['counted_qty'] - $current;

                if (abs($delta) < 0.0001) {
                    $skipped++;
                    continue;
                }

                $this->stock->adjustAt(
                    (int)$l['product_id'],
                    (int)$count['location_id'],
                    $delta,
                    [
                        'reason_code'   => 'count_correction',
                        'reference_num' => $count['count_number'],
                        'notes'         => sprintf(
                            'Counted %s on %s, system said %s',
                            $this->fmt((float)$l['counted_qty']),
                            $count['count_number'],
                            $this->fmt($current)
                        ),
                        'user_id'       => Auth::id(),
                    ]
                );

                $this->stock->markCounted((int)$l['product_id'], (int)$count['location_id'], Auth::id());

                $adjusted++;
                $net += $delta;
            }

            $this->repo->markApplied($countId, Auth::id());
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        return ['adjusted' => $adjusted, 'skipped' => $skipped, 'net' => $net];
    }

    public function cancel(int $countId): void
    {
        $count = $this->repo->find($countId);

        if ($count === null) {
            throw new \RuntimeException('That count no longer exists.');
        }
        if ($count['status'] === 'applied') {
            throw new \RuntimeException('An applied count cannot be cancelled — adjust the stock instead.');
        }

        $this->repo->setStatus($countId, 'cancelled');
    }

    private function require(int $id, string $status, string $message): array
    {
        $count = $this->repo->find($id);

        if ($count === null) {
            throw new \RuntimeException('That count no longer exists.');
        }
        if ($count['status'] !== $status) {
            throw new \RuntimeException($message);
        }

        return $count;
    }

    private function fmt(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2), '0'), '.');
    }

    public function get(int $id): ?array               { return $this->repo->find($id); }
    public function countingLines(int $id): array      { return $this->repo->linesForCounting($id); }
    public function reviewLines(int $id): array        { return $this->repo->linesForReview($id); }
    public function all(): array                       { return $this->repo->all(); }
    public function locations(): array                 { return $this->stock->storableLocations(); }
    public function stale(int $locationId): array      { return $this->repo->staleAt($locationId); }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Turning what was ordered into the boxes that actually leave.
 *
 * A case is not a package. Aerosol is cased 12 cans, but cases pair up into shipping
 * boxes — one case goes in a single, two go in a double, three go as a double plus a
 * single. Asking FedEx for three packages when two leave is wrong twice over: the customer
 * gets a tracking number for a box that does not exist, and the freight is overpriced.
 *
 * Cases POOL ACROSS PRODUCTS. A double box holds two cases; it does not care whether
 * they are the same colour. Three DuraStripe white and one red is four cases and
 * leaves as two double boxes, not as a double plus two singles. Packing each line on
 * its own — which is what this used to do — invents a third box that nobody tapes up
 * and bills the customer for shipping it.
 *
 * Which products may share a box is data, not a rule in code: they pool when their
 * largest packing rule names the same box. That is why fat cans never pair with
 * T-tips — a fat can's only rule is FATCAN-1 — without fat cans being mentioned here.
 *
 * Nothing here guesses. A product with no packing rule is reported as needing one, and a
 * box with no weight is reported as needing that, rather than being quietly assumed.
 */
class CartonizerService
{
    /**
     * Work out the packages for a set of order lines.
     *
     * @param list<array{product_id:int|string|null, qty:float|string}> $lines
     *
     * @return array{
     *   packages: list<array{box:?string, qty:float, weight:?float, length:?float, width:?float, height:?float, product_id:int}>,
     *   freight_required: bool,
     *   reasons: list<string>,
     *   missing: list<string>,
     *   total_weight: ?float
     * }
     */
    public function pack(array $lines): array
    {
        $packages = [];
        $reasons  = [];
        $missing  = [];
        $pending  = [];
        $freight  = false;
        $weighed  = true;
        $total    = 0.0;

        foreach ($lines as $line) {
            $productId = (int)($line['product_id'] ?? 0);
            $qty       = (float)($line['qty'] ?? 0);

            if ($productId <= 0 || $qty <= 0) {
                continue;
            }

            $product = Database::selectOne("
                SELECT id, sku, name, units_per_case, max_parcel_qty,
                       case_weight_gross, case_length, case_width, case_height,
                       unit_gross_weight, hazmat_class
                FROM products WHERE id = ?
            ", [$productId]);

            if ($product === false) {
                continue;
            }

            $label = trim((string)($product['sku'] ?: $product['name']));

            // Freight is decided on the floor by what fits, not by arithmetic — so it is a
            // quantity threshold per product rather than a weight calculation.
            $limit = $product['max_parcel_qty'] !== null ? (float)$product['max_parcel_qty'] : null;

            if ($limit !== null && $qty > $limit) {
                $freight   = true;
                $reasons[] = sprintf(
                    '%s: %s is more than the %s that goes by parcel — this is freight.',
                    $label,
                    $this->fmt($qty),
                    $this->fmt($limit)
                );
                continue;
            }

            $rules = Database::select("
                SELECT r.cases_per_box, b.code, b.name, b.length_in, b.width_in, b.height_in, b.tare_lb, b.max_lb
                FROM product_packing_rules r
                JOIN shipping_boxes b ON b.id = r.shipping_box_id
                WHERE r.product_id = ? AND r.is_active = 1 AND b.is_active = 1
                ORDER BY r.cases_per_box DESC
            ", [$productId]);

            $caseWeight = $product['case_weight_gross'] !== null ? (float)$product['case_weight_gross'] : null;

            if ($caseWeight === null || $caseWeight <= 0) {
                $weighed   = false;
                $missing[] = $label . ' has no case weight';
            }

            if ($rules === []) {
                // No rule means one package per selling unit, which is the honest default —
                // but it is reported, because for anything cased it is probably wrong.
                $missing[] = $label . ' has no packing rule — treated as one box each';

                for ($i = 0; $i < (int)ceil($qty); $i++) {
                    $packages[] = $this->package($productId, null, 1, $caseWeight, null, $product);
                    $total     += $caseWeight ?? 0;
                }

                continue;
            }

            // Collected now, packed later: cases pool across every line that shares a
            // box, so no line can be packed until all of them have been read.
            $pending[] = [
                'product_id' => $productId,
                'product'    => $product,
                'label'      => $label,
                'qty'        => $qty,
                'case_weight'=> $caseWeight,
                'rules'      => $rules,
            ];
        }

        // ------------------------------------------------------------------
        // Pool by box, then fill
        // ------------------------------------------------------------------

        $groups = [];

        foreach ($pending as $i => $pl) {
            // The largest rule decides the pool. Rules arrive ordered by cases_per_box
            // descending, so the first is the biggest box this product may travel in.
            $groups[(string)$pl['rules'][0]['code']][] = $i;
        }

        foreach ($groups as $members) {
            $capacity = (float)$pending[$members[0]]['rules'][0]['cases_per_box'];
            $box      = $pending[$members[0]]['rules'][0];

            // A queue of cases still to pack, in line order, so a box is filled from
            // the products as they were ordered rather than in some invented order.
            $queue = [];

            foreach ($members as $i) {
                $queue[] = ['i' => $i, 'left' => $pending[$i]['qty']];
            }

            $pooled = 0.0;

            foreach ($queue as $q) {
                $pooled += $q['left'];
            }

            $cursor = 0;

            // Full boxes first, drawing across products.
            while ($pooled >= $capacity - 0.0001 && $capacity > 0) {
                $contents = [];
                $room     = $capacity;

                while ($room > 0.0001 && $cursor < count($queue)) {
                    if ($queue[$cursor]['left'] <= 0.0001) {
                        $cursor++;
                        continue;
                    }

                    $take = min($room, $queue[$cursor]['left']);
                    $queue[$cursor]['left'] -= $take;
                    $room   -= $take;
                    $pooled -= $take;

                    $contents[] = ['i' => $queue[$cursor]['i'], 'qty' => $take];
                }

                if ($contents === []) {
                    break;
                }

                [$packages, $total, $freight, $reasons] =
                    $this->addPackage($packages, $total, $freight, $reasons, $contents, $pending, $box, $capacity);
            }

            // Whatever is left over is less than a full box, so each product's remainder
            // goes in the smallest box it has a rule for.
            foreach ($queue as $q) {
                $left = $q['left'];

                if ($left <= 0.0001) {
                    continue;
                }

                $small = $pending[$q['i']]['rules'][count($pending[$q['i']]['rules']) - 1];
                $cap   = max(0.0001, (float)$small['cases_per_box']);

                while ($left > 0.0001) {
                    $take = min($cap, $left);
                    [$packages, $total, $freight, $reasons] = $this->addPackage(
                        $packages, $total, $freight, $reasons,
                        [['i' => $q['i'], 'qty' => $take]], $pending, $small, $cap
                    );
                    $left -= $take;
                }
            }
        }

        return [
            'packages'         => $packages,
            'freight_required' => $freight,
            'reasons'          => $reasons,
            'missing'          => array_values(array_unique($missing)),
            'total_weight'     => $weighed && $packages !== [] ? round($total, 2) : null,
        ];
    }

    /**
     * Build one package, which may hold cases of more than one product, and fold it
     * into the running totals.
     *
     * Returns the four values it touches rather than mutating them, so the caller's
     * state stays visible at the call site instead of being changed from in here.
     *
     * @param list<array{i:int,qty:float}> $contents
     * @return array{0:array,1:float,2:bool,3:array}
     */
    private function addPackage(
        array $packages,
        float $total,
        bool $freight,
        array $reasons,
        array $contents,
        array $pending,
        array $box,
        float $capacity
    ): array {
        $weight = 0.0;
        $known  = true;
        $items  = [];
        $labels = [];
        $qty    = 0.0;

        foreach ($contents as $c) {
            $pl = $pending[$c['i']];

            if ($pl['case_weight'] === null) {
                $known = false;
            } else {
                $weight += $pl['case_weight'] * $c['qty'];
            }

            $qty += $c['qty'];
            $labels[] = $pl['label'];
            $items[]  = [
                'product_id' => $pl['product_id'],
                'sku'        => (string)($pl['product']['sku'] ?? ''),
                'qty'        => $c['qty'],
            ];
        }

        $boxWeight = $known ? round($weight + (float)($box['tare_lb'] ?? 0), 2) : null;

        if ($boxWeight !== null && ($box['max_lb'] ?? null) !== null && $boxWeight > (float)$box['max_lb']) {
            $freight   = true;
            $reasons[] = sprintf(
                '%s: a %s would weigh %s lb, over the %s lb this box takes.',
                implode(' + ', array_unique($labels)),
                $box['name'],
                $this->fmt($boxWeight),
                $this->fmt((float)$box['max_lb'])
            );
        }

        $first = $pending[$contents[0]['i']];

        $pkg = $this->package(
            $first['product_id'],
            (string)$box['code'],
            $qty,
            $boxWeight,
            $box,
            $first['product']
        );

        // A mixed box has no single SKU. Naming one would put a box on the packing
        // list under a product that is only part of what is inside it.
        $pkg['contents'] = $items;

        if (count($items) > 1) {
            $pkg['sku'] = implode(' + ', array_map(fn($i) => $i['sku'], $items));
        }

        $packages[] = $pkg;
        $total     += $boxWeight ?? 0;

        return [$packages, $total, $freight, $reasons];
    }

    /** @return array<string,mixed> */
    private function package(int $productId, ?string $box, float $qty, ?float $weight, ?array $rule, array $product): array
    {
        return [
            'product_id' => $productId,
            'sku'        => (string)($product['sku'] ?? ''),
            'box'        => $box,
            'qty'        => $qty,
            'weight'     => $weight,
            'length'     => $rule !== null ? $this->num($rule['length_in']) : $this->num($product['case_length']),
            'width'      => $rule !== null ? $this->num($rule['width_in'])  : $this->num($product['case_width']),
            'height'     => $rule !== null ? $this->num($rule['height_in']) : $this->num($product['case_height']),
            'hazmat'     => trim((string)($product['hazmat_class'] ?? '')) ?: null,
        ];
    }

    private function num(mixed $v): ?float
    {
        return ($v === null || (float)$v <= 0) ? null : (float)$v;
    }

    private function fmt(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2), '0'), '.');
    }

    /** The boxes on file, for the admin screen and for checking what still needs measuring. */
    public function boxes(): array
    {
        return Database::select("
            SELECT b.*,
                   (SELECT COUNT(*) FROM product_packing_rules r WHERE r.shipping_box_id = b.id) AS products
            FROM shipping_boxes b
            ORDER BY b.code
        ");
    }
}

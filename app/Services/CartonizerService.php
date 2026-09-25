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
 * The packing is a greedy fill — biggest box that still fits, repeat — driven from rows
 * rather than code, so a triple box or a different rule for a different product is data.
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

            // Greedy: biggest box that still fits, repeat. Three cases with rules for 2 and
            // 1 gives a double and a single, which is what actually leaves the bench.
            $remaining = $qty;

            while ($remaining > 0.0001) {
                $chosen = null;

                foreach ($rules as $rule) {
                    if ((float)$rule['cases_per_box'] <= $remaining + 0.0001) {
                        $chosen = $rule;
                        break;
                    }
                }

                // Less left than the smallest box holds — it still needs a box.
                $chosen ??= $rules[count($rules) - 1];

                $inBox      = min($remaining, (float)$chosen['cases_per_box']);
                $boxWeight  = $caseWeight !== null
                    ? round($caseWeight * $inBox + (float)($chosen['tare_lb'] ?? 0), 2)
                    : null;

                if ($boxWeight !== null && $chosen['max_lb'] !== null && $boxWeight > (float)$chosen['max_lb']) {
                    $freight   = true;
                    $reasons[] = sprintf(
                        '%s: a %s would weigh %s lb, over the %s lb this box takes.',
                        $label,
                        $chosen['name'],
                        $this->fmt($boxWeight),
                        $this->fmt((float)$chosen['max_lb'])
                    );
                }

                $packages[] = $this->package($productId, (string)$chosen['code'], $inBox, $boxWeight, $chosen, $product);
                $total     += $boxWeight ?? 0;
                $remaining -= $inBox;
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

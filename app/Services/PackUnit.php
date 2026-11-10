<?php

declare(strict_types=1);

namespace App\Services;

/**
 * What a quantity on a pick or pack line actually counts.
 *
 * `uom_code` describes the SMALLEST unit the catalogue knows about - '18 oz Can' -
 * while the line is counted in whatever the product is sold as. DSW is a case of
 * twelve cans, so a line of 9 is nine cases, not nine cans. Printing the uom_code
 * beside the number said "9 · 18 oz Can", which reads as nine cans and is wrong by a
 * factor of twelve.
 *
 * Nothing here changes a number. It names the thing being counted, and says how many
 * boxes that comes to, because "9" and "4 double boxes and a single" are the same fact
 * said to two different people.
 */
class PackUnit
{
    /**
     * The unit one of these is: 'case', 'pail', or whatever the catalogue calls it.
     */
    public static function label(array $line, float $qty = 1.0): string
    {
        $perCase = (int)($line['units_per_case'] ?? 0);
        $uom     = trim((string)($line['uom_code'] ?? ''));

        if ($perCase > 1) {
            $word = $qty == 1.0 ? 'case' : 'cases';

            return $uom !== ''
                ? $word . ' of ' . $perCase . ' × ' . $uom
                : $word;
        }

        // Sold one at a time. '5 Gal Pail' already says what one is.
        return $uom !== '' ? $uom : ($qty == 1.0 ? 'each' : 'each');
    }

    /**
     * "9 cases of 12 × 18 oz Can" — the quantity said in full.
     */
    public static function describe(array $line, float $qty): string
    {
        $n = rtrim(rtrim(number_format($qty, 2), '0'), '.');

        return $n . ' ' . self::label($line, $qty);
    }

    /**
     * How the boxes break down, in the packer's words.
     *
     * Given the cartonizer's package list for one product, says "4 double boxes + 1
     * single" rather than leaving somebody to work it out from a count of cases.
     *
     * @param array<int,array<string,mixed>> $packages the cartonizer's packages
     * @return string empty when there is nothing useful to say
     */
    /**
     * @param array<string,string> $names box code => the name people use for it
     */
    public static function boxBreakdown(array $packages, int $productId, array $names = []): string
    {
        $counts = [];

        foreach ($packages as $pkg) {
            $code = trim((string)($pkg['box'] ?? ''));

            if ($code === '') {
                continue;   // loose, no box rule — nothing useful to name
            }

            // A mixed box lists what is in it; a single-product box does not bother.
            $contents = (array)($pkg['contents'] ?? []);

            if ($contents === []) {
                $contents = [[
                    'product_id' => (int)($pkg['product_id'] ?? 0),
                    'qty'        => (float)($pkg['qty'] ?? 0),
                ]];
            }

            foreach ($contents as $c) {
                if ((int)($c['product_id'] ?? 0) !== $productId) {
                    continue;
                }

                $per = rtrim(rtrim(number_format((float)($c['qty'] ?? 0), 2), '0'), '.');
                $key = $code . '|' . $per;

                $counts[$key] = ($counts[$key] ?? 0) + 1;
            }
        }

        if ($counts === []) {
            return '';
        }

        $parts = [];

        foreach ($counts as $key => $n) {
            [$code, $per] = explode('|', $key, 2);

            // 'Aerosol double box' is what the people packing it call the thing.
            // AERO-2 is what the database calls it, and they are not at the bench.
            $label = $names[$code] ?? $code;
            $parts[] = $n . ' × ' . $label . ($per !== '1' ? ' (' . $per . ' each)' : '');
        }

        return implode(' + ', $parts);
    }
}

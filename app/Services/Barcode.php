<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Barcode symbols as SVG. No library, no image extension, no temp files.
 *
 * WHY HAND-ROLLED. A barcode is a width table and a checksum — a few hundred lines
 * of arithmetic. Pulling in a package to do it would add a dependency to update
 * forever for something that has not changed since 1973.
 *
 * WHY SVG. It is vector, so the printer renders bar edges at its own resolution
 * instead of at ours. A PNG scaled to a label is the usual reason a scanner
 * struggles: the bar edges land between pixels and the bar widths stop being the
 * exact ratios the symbology requires. SVG has no such failure.
 *
 * Three symbols, because we have three kinds of code to print:
 *
 *   upcA()    gtin12 — the retail unit code, what Amazon and a store shelf expect.
 *   gs1_128() gtin14 — the case code, carried as GS1-128 with AI (01). ITF-14 is
 *             the other legal choice; GS1-128 is read by the same scanners, holds
 *             the application identifier, and does not need bearer bars to scan
 *             reliably, which matters when we are printing on a plain label.
 *   code128() any text — our SKU. This is the one that matters most today:
 *             738 of 885 active products have no GTIN at all, and a SKU barcode
 *             makes every one of them scannable because resolveScan() already
 *             falls back to matching on sku.
 */
final class Barcode
{
    /** Left-hand odd parity patterns, digits 0-9. Right-hand is the complement. */
    private const UPC_L = [
        '0001101', '0011001', '0010011', '0111101', '0100011',
        '0110001', '0101111', '0111011', '0110111', '0001011',
    ];

    /**
     * Code 128 bar/space widths, values 0-106. Each character is six alternating
     * runs (bar, space, bar, space, bar, space) totalling 11 modules; the stop
     * pattern is the one exception at seven runs and 13 modules.
     */
    private const C128 = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312',
        '132212', '221213', '221312', '231212', '112232', '122132', '122231', '113222',
        '123122', '123221', '223211', '221132', '221231', '213212', '223112', '312131',
        '311222', '321122', '321221', '312212', '322112', '322211', '212123', '212321',
        '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121',
        '313121', '211331', '231131', '213113', '213311', '213131', '311123', '311321',
        '331121', '312113', '312311', '332111', '314111', '221411', '431111', '111224',
        '111422', '121124', '121421', '141122', '141221', '112214', '112412', '122114',
        '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112',
        '421211', '212141', '214121', '412121', '111143', '111341', '131141', '114113',
        '114311', '411113', '411311', '113141', '114131', '311141', '411131', '211412',
        '211214', '211232', '2331112',
    ];

    private const C128_START_B = 104;
    private const C128_START_C = 105;
    private const C128_FNC1    = 102;
    private const C128_STOP    = 106;

    /**
     * The GS1 check digit, used by GTIN-8/12/13/14 alike. Weights alternate 3 and 1
     * from the RIGHT, which is why this walks the string backwards — doing it
     * forwards gives the wrong answer on even-length codes and the right answer on
     * odd-length ones, so it tests fine on a UPC and fails on a case code.
     */
    public static function gtinCheckDigit(string $digits): int
    {
        $sum = 0;
        $weight = 3;

        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $sum += (int)$digits[$i] * $weight;
            $weight = $weight === 3 ? 1 : 3;
        }

        return (10 - ($sum % 10)) % 10;
    }

    /** True when a GTIN is the right length and its last digit agrees with the rest. */
    public static function gtinIsValid(string $gtin): bool
    {
        $gtin = preg_replace('/\D/', '', $gtin) ?? '';

        if (!in_array(strlen($gtin), [8, 12, 13, 14], true)) {
            return false;
        }

        return self::gtinCheckDigit(substr($gtin, 0, -1)) === (int)substr($gtin, -1);
    }

    /**
     * UPC-A from a 12-digit GTIN-12. Give it 11 digits and it computes the check
     * digit; give it 12 and the check digit must already be right, because printing
     * a code whose check digit disagrees with its own digits produces a label that
     * no scanner anywhere will read.
     */
    public static function upcA(string $gtin12, float $module = 0.33, float $height = 22.0): string
    {
        $d = preg_replace('/\D/', '', $gtin12) ?? '';

        if (strlen($d) === 11) {
            $d .= (string)self::gtinCheckDigit($d);
        }

        if (strlen($d) !== 12) {
            throw new \InvalidArgumentException("UPC-A needs 11 or 12 digits, got " . strlen($d));
        }

        if (self::gtinCheckDigit(substr($d, 0, 11)) !== (int)$d[11]) {
            throw new \InvalidArgumentException("UPC-A check digit is wrong for {$d}");
        }

        $bits = '101';                                  // start guard

        for ($i = 0; $i < 6; $i++) {
            $bits .= self::UPC_L[(int)$d[$i]];
        }

        $bits .= '01010';                               // centre guard

        for ($i = 6; $i < 12; $i++) {
            // Right-hand digits use even parity, which is the left pattern inverted.
            $bits .= strtr(self::UPC_L[(int)$d[$i]], '01', '10');
        }

        $bits .= '101';                                 // end guard

        // The guard bars run longer than the data bars, which is how a scanner finds
        // the edges of the symbol and how the digits get somewhere to sit.
        $long = array_merge(
            range(0, 2), range(45, 49), range(92, 94)
        );

        return self::renderBits($bits, $module, $height, $long, self::upcText($d, $module, $height));
    }

    /**
     * GS1-128 carrying AI (01) and a GTIN-14 — the case label. Code set C packs two
     * digits per symbol character, and "01" plus 14 digits is 16 digits exactly, so
     * it stays in set C from the first character to the last with nothing left over.
     */
    public static function gs1_128(string $gtin14, float $module = 0.33, float $height = 22.0): string
    {
        $d = preg_replace('/\D/', '', $gtin14) ?? '';

        if (strlen($d) === 13) {
            $d .= (string)self::gtinCheckDigit($d);
        }

        if (strlen($d) !== 14) {
            throw new \InvalidArgumentException("GTIN-14 needs 13 or 14 digits, got " . strlen($d));
        }

        if (self::gtinCheckDigit(substr($d, 0, 13)) !== (int)$d[13]) {
            throw new \InvalidArgumentException("GTIN-14 check digit is wrong for {$d}");
        }

        $values = [self::C128_START_C, self::C128_FNC1];
        $payload = '01' . $d;

        for ($i = 0; $i < strlen($payload); $i += 2) {
            $values[] = (int)substr($payload, $i, 2);
        }

        return self::renderC128($values, $module, $height, '(01) ' . $d);
    }

    /**
     * Code 128 set B over printable ASCII — our SKUs. Set B covers 32..126, which is
     * every character a SKU of ours uses; anything outside that is rejected rather
     * than silently dropped, because a barcode that scans as a DIFFERENT SKU than
     * the one printed underneath it is worse than no barcode.
     */
    public static function code128(string $text, float $module = 0.28, float $height = 18.0): string
    {
        if ($text === '') {
            throw new \InvalidArgumentException('Nothing to encode');
        }

        $values = [self::C128_START_B];

        foreach (str_split($text) as $ch) {
            $o = ord($ch);

            if ($o < 32 || $o > 126) {
                throw new \InvalidArgumentException("Code 128B cannot carry " . bin2hex($ch));
            }

            $values[] = $o - 32;
        }

        return self::renderC128($values, $module, $height, $text);
    }

    /** Append the modulo-103 check character, expand to bars, render. */
    private static function renderC128(array $values, float $module, float $height, string $label): string
    {
        // The start character counts as position 1; every later character is weighted
        // by its position. Weighting the start character too is the classic off-by-one
        // here and produces a symbol that scans as nothing at all.
        $sum = $values[0];

        for ($i = 1; $i < count($values); $i++) {
            $sum += $values[$i] * $i;
        }

        $values[] = $sum % 103;
        $values[] = self::C128_STOP;

        $bits = '';
        $bar  = true;

        foreach ($values as $v) {
            foreach (str_split(self::C128[$v]) as $run) {
                $bits .= str_repeat($bar ? '1' : '0', (int)$run);
                $bar = !$bar;
            }
            $bar = true;                                 // every character starts on a bar
        }

        return self::renderBits($bits, $module, $height, [], self::centredText($label, $bits, $module, $height));
    }

    /**
     * Turn a module string into SVG. Runs of 1s become one rect each rather than one
     * rect per module — a UPC is 95 modules and a long SKU far more, and some label
     * printers choke on the element count if you are careless about it.
     *
     * @param int[] $longBars indices that extend below the data bars (UPC guards)
     */
    private static function renderBits(
        string $bits,
        float $module,
        float $height,
        array $longBars,
        string $textSvg
    ): string {
        $quiet   = $module * 10;                         // GS1 minimum quiet zone
        $extra   = $longBars === [] ? 0.0 : $module * 5; // guard bar overhang
        $textGap = 3.2;

        $width  = $quiet * 2 + strlen($bits) * $module;
        $total  = $height + $extra + $textGap;

        $rects = '';
        $i = 0;
        $n = strlen($bits);

        while ($i < $n) {
            if ($bits[$i] !== '1') {
                $i++;
                continue;
            }

            $run = 0;
            $long = false;

            while ($i + $run < $n && $bits[$i + $run] === '1') {
                if (in_array($i + $run, $longBars, true)) {
                    $long = true;
                }
                $run++;
            }

            $rects .= sprintf(
                '<rect x="%.4f" y="0" width="%.4f" height="%.4f"/>',
                $quiet + $i * $module,
                $run * $module,
                $height + ($long ? $extra : 0.0)
            );

            $i += $run;
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%1$.3fmm" height="%2$.3fmm" '
            . 'viewBox="0 0 %1$.3f %2$.3f" shape-rendering="crispEdges">'
            . '<rect width="%1$.3f" height="%2$.3f" fill="#fff"/><g fill="#000">%3$s</g>%4$s</svg>',
            $width,
            $total,
            $rects,
            $textSvg
        );
    }

    /** UPC digits sit in the quiet zone and the two guard gaps, as the standard draws them. */
    private static function upcText(string $d, float $module, float $height): string
    {
        $quiet = $module * 10;
        $y     = $height + $module * 5 + 2.6;
        $size  = $module * 7;

        $at = function (float $x, string $s) use ($y, $size): string {
            return sprintf(
                '<text x="%.3f" y="%.3f" font-family="monospace" font-size="%.3f" fill="#000">%s</text>',
                $x, $y, $size, $s
            );
        };

        $out  = $at($quiet - $module * 9, $d[0]);
        $out .= $at($quiet + $module * 13, substr($d, 1, 5));
        $out .= $at($quiet + $module * 51, substr($d, 6, 5));
        $out .= $at($quiet + $module * 96, $d[11]);

        return $out;
    }

    private static function centredText(string $label, string $bits, float $module, float $height): string
    {
        $width = $module * 10 * 2 + strlen($bits) * $module;

        return sprintf(
            '<text x="%.3f" y="%.3f" text-anchor="middle" font-family="monospace" '
            . 'font-size="%.3f" fill="#000">%s</text>',
            $width / 2,
            $height + 2.6,
            max(2.2, $module * 9),
            htmlspecialchars($label, ENT_QUOTES | ENT_XML1)
        );
    }
}

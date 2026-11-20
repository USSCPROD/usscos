<?php

declare(strict_types=1);

namespace App\Services\Stencils;

/**
 * The proof page from the online designers: the stencil shop's proof.py, as SVG.
 *
 * Letter landscape, laid out like USSC's own proof sheets: the design on the field across
 * the full width of the page, a caption with its size, then a row with who it was prepared
 * for, the project, the stencil details and the online price; then the approval and date
 * lines, the trademark terms and the address bar.
 *
 * The shop turned this SVG into a PDF with rsvg-convert. This server has none, so the
 * page is served inside a print-ready HTML document (DesignerController::proof) and the
 * customer saves it as PDF from the print dialog - how USSCOS prints its invoices too.
 * The page is 792 x 612 points, exactly a letter sheet turned sideways.
 */
class StencilProof
{
    private const W = 792;
    private const H = 612;
    private const TURF = '#3b7a2a';
    private const TURF2 = '#357024';
    private const LINE = '#f6f6f2';
    private const POST = '#e87722';
    private const TITLE_FONT = 'Liberation Sans Narrow, Arial Narrow, DejaVu Sans Condensed, sans-serif';
    private const BODY_FONT = 'Liberation Sans, Arial, DejaVu Sans, sans-serif';

    private const TERMS = "TRADE MARKS. Buyer agrees, upon receipt of notification, to assume full responsibility for the defense of any suit "
        . "or proceedings which may be brought against U.S. Specialty Coatings, and its subsidiaries, affiliated companies, "
        . "agents or vendees by reason of the use or sales of any materials furnished on this order which are not the buyer's "
        . "design, for alleged patent infringement or for alleged unfair competition resulting from similarity of design, "
        . "trademark or appearance of goods; and buyer further agrees to indemnify U.S. Specialty Coatings, its subsidiaries, "
        . "affiliated companies, agents and vendees against any and all expense, loss, royalties, profits and damages, "
        . "including court costs and attorney's fees, resulting from the bringing of such suit or proceedings and/or from any "
        . "settlement, decree or judgment therein. U.S. Specialty Coatings reserves the right to control any such suit or "
        . "proceeding and may be represented by its own counsel in any such suit proceedings, if it so desires.";

    private const MATERIAL_NOTE = 'Cut from heavy-duty 10-mil polyethylene that lies flat on grass or turf for crisp, clean lines. '
        . 'Foldable and reusable: rinse, fold and store it after painting, and use it season after season.';

    public static function endzone(array $q, string $fill, string $line, array $for): string
    {
        [$drawing, $view] = self::endzoneDrawing($q, $fill, $line);
        $ft = [EndzoneDesigner::class, 'ft'];
        $sections = (int)($q['sections'] ?? 1);

        if ($q['format'] === 'letters') {
            $cut    = $q['letters'] . ' individual letter stencils, each ' . $ft($q['letterHeight']) . ' tall';
            $pieces = $q['letters'] . ' letter stencils';
        } else {
            $cut    = 'One large stencil, ' . $ft($q['sheet']['width']) . ' x ' . $ft($q['sheet']['height']) . ($sections > 1 ? ", in {$sections} sections" : '');
            $pieces = '1 stencil' . ($sections > 1 ? ", {$sections} sections" : '');
        }

        $specs = [['Lettering', $ft($q['lettering']['width']) . ' × ' . $ft($q['lettering']['height'])], ['Pieces', $pieces],
                  ['Ships', $q['weight'] . ' lbs · ' . $q['boxes'] . ' box' . ($q['boxes'] > 1 ? 'es' : '')]];
        $note = self::MATERIAL_NOTE . ($q['style'] === 'outline' ? ' Outline letters are cut as outlines; paint the inside by hand in a second color.' : '');
        $caption = '“' . $q['text'] . '”: ' . $ft($q['lettering']['width']) . ' wide x ' . $ft($q['lettering']['height']) . ' tall ('
                 . strtolower($q['styleLabel']) . ' letters are ' . $ft($q['letterHeight']) . ' tall)';
        $project = ['Endzone lettering: “' . $q['text'] . '”', $q['styleLabel'] . ' letters, ' . $ft($q['letterHeight']) . ' tall', $cut];

        return self::page($drawing, $view, sprintf('EZ-%06d', time() % 1000000), $caption, self::preparedFor($for), $project,
                          $specs, $note, (float)$q['price']);
    }

    public static function midfield(array $q, string $logoPng, array $for): string
    {
        [$drawing, $view] = self::midfieldDrawing($q, $logoPng);
        $ft  = [EndzoneDesigner::class, 'ft'];
        $mid = round(($q['fieldSize']['length'] - 2 * $q['fieldSize']['endZone']) / 6);

        $specs = [['Logo', $ft($q['width']) . ' × ' . $ft($q['height'])],
                  ['Stencil (1 piece)', $ft($q['sheet']['width']) . ' × ' . $ft($q['sheet']['height'])],
                  ['Ships', $q['weight'] . ' lbs · ' . $q['boxes'] . ' box' . ($q['boxes'] > 1 ? 'es' : '')]];
        $project = ['Midfield logo', $q['fieldLabel'] . ' field, centered on the ' . $mid . '-yard line',
                    'Logo ' . $ft($q['width']) . ' wide x ' . $ft($q['height']) . ' tall'];
        $caption = 'Midfield logo: ' . $ft($q['width']) . ' wide x ' . $ft($q['height']) . ' tall, ' . strtolower($q['fieldLabel']) . ' field';

        return self::page($drawing, $view, 'MF-' . strtoupper(substr($q['logo'], 0, 6)), $caption, self::preparedFor($for), $project,
                          $specs, self::MATERIAL_NOTE, (float)$q['price']);
    }

    // ── Field drawings, in feet ──────────────────────────────────────────────

    private static function endzoneDrawing(array $q, string $fill, string $line): array
    {
        $W = $q['endZone']['width'];
        $D = $q['endZone']['height'];
        $m = 8;
        $field = 30;
        $ox = ($W - $q['lettering']['width']) / 2;
        $oy = ($D - $q['lettering']['height']) / 2;
        $L = self::LINE;

        $stripes = '';
        for ($i = 0; $i < 2; $i++) {
            $stripes .= sprintf('<rect x="0" y="%s" width="%s" height="15" fill="%s"/>', $D + $i * 15, $W, $i % 2 ? self::TURF2 : self::TURF);
        }

        $hashes = '';
        for ($y = (int)$D + 3; $y < (int)$D + $field; $y += 3) {
            if (($y - (int)$D) % 15 === 0) {
                continue;
            }
            foreach ([0.67, $W - 2.67, 53.33, $W - 53.33 - 2] as $x) {
                $hashes .= "M{$x} {$y}H" . ($x + 2);
            }
        }

        $letters = $q['style'] === 'outline'
            ? sprintf('<path d="%s" fill="%s" fill-rule="evenodd"/><path d="%s" fill="%s" fill-rule="evenodd"/>', $q['fill'], self::e($fill), $q['path'], self::e($line))
            : sprintf('<path d="%s" fill="%s" fill-rule="evenodd"/>', $q['path'], self::e($fill));
        $post = sprintf('<path d="M%s -3H%sM%s -3V-7" stroke="%s" stroke-width=".9" fill="none" stroke-linecap="round"/>',
            $W / 2 - 11.67, $W / 2 + 11.67, $W / 2, self::POST);
        $ft = EndzoneDesigner::ft(...);

        $body = sprintf('<rect x="%s" y="%s" width="%s" height="%s" fill="%s"/>', -$m, -$m, $W + 2 * $m, $D + $field + 2 * $m, self::TURF)
            . sprintf('<rect x="0" y="0" width="%s" height="%s" fill="%s"/>%s', $W, $D, self::TURF2, $stripes)
            . sprintf('<path d="M0 0H%1$sM0 %2$sH%1$sM0 %3$sH%1$sM0 %4$sH%1$sM0 0V%5$sM%1$s 0V%5$s" stroke="%6$s" stroke-width=".45" fill="none"/>',
                $W, $D, $D + 15, $D + 30, $D + $field, $L)
            . sprintf('<path d="%s" stroke="%s" stroke-width=".35" fill="none"/>', $hashes, $L)
            . $post . sprintf('<g transform="translate(%.3f %.3f)">%s</g>', $ox, $oy, $letters)
            . sprintf('<text x="%s" y="%s" font-size="2.2" text-anchor="end" fill="%s" font-family="%s">End zone %s x %s</text>', $W - 1, $D - 1.2, $L, self::BODY_FONT, $ft($W), $ft($D))
            . sprintf('<text x="%s" y="%s" font-size="2.2" text-anchor="end" fill="%s" font-family="%s" opacity=".8">5</text>', $W - 1, $D + 13.8, $L, self::BODY_FONT)
            . sprintf('<text x="%s" y="%s" font-size="2.2" text-anchor="end" fill="%s" font-family="%s" opacity=".8">10</text>', $W - 1, $D + 28.8, $L, self::BODY_FONT);

        return [$body, [-$m, -$m, $W + 2 * $m, $D + $field + 2 * $m]];
    }

    private static function midfieldDrawing(array $q, string $logoPng): array
    {
        $L  = $q['fieldSize']['length'];
        $W  = $q['fieldSize']['width'];
        $E  = (int)$q['fieldSize']['endZone'];
        $mid = (int)round(($L - 2 * $E) / 6);
        $hashIn = $q['hash'];
        $cx = $L / 2;
        $cy = $W / 2;
        // From the 20 to the 20 (the whole field between the end zones on an 80-yard field),
        // so the drawing is as wide as the page it sits on.
        $x0 = max(0, $cx - 150);
        $x1 = min($L, $cx + 150);
        $m = 5;
        $LN = self::LINE;

        $parts = [sprintf('<rect x="%s" y="%s" width="%s" height="%s" fill="#356b26"/>', $x0, -$m, $x1 - $x0, $W + 2 * $m)];

        for ($x = $E; $x < (int)$L - $E; $x += 15) {
            if ($x + 15 > $x0 && $x < $x1) {
                $parts[] = sprintf('<rect x="%s" y="0" width="%s" height="%s" fill="%s"/>',
                    max($x, $x0), min($x + 15, $x1) - max($x, $x0), $W, intdiv($x, 15) % 2 ? '#3f8030' : '#3a7629');
            }
        }

        $yards = '';
        for ($x = $E; $x <= (int)$L - $E; $x += 15) {
            if ($x0 <= $x && $x <= $x1) {
                $yards .= "M{$x} 0V{$W}";
            }
        }

        $ticks = '';
        for ($x = $E; $x <= (int)$L - $E; $x += 3) {
            if ($x0 < $x && $x < $x1 && ($x - $E) % 15) {
                $ticks .= sprintf('M%1$s .67V2.67M%1$s %2$sV%3$sM%1$s %4$.2fV%5$.2fM%1$s %6$.2fV%7$.2f',
                    $x, $W - 2.67, $W - .67, $hashIn - 2, $hashIn, $W - $hashIn, $W - $hashIn + 2);
            }
        }

        $parts[] = sprintf('<path d="%sM%s 0H%sM%s %sH%s" stroke="%s" stroke-width=".45" fill="none"/>', $yards, $x0, $x1, $x0, $W, $x1, $LN);
        $parts[] = sprintf('<path d="%s" stroke="%s" stroke-width=".35" fill="none"/>', $ticks, $LN);

        for ($yd = 10; $yd <= $mid; $yd += 10) {
            foreach ([-1, 1] as $side) {
                $x = $cx + $side * ($mid - $yd) * 3;

                if ($x < $x0 || $x > $x1 || ($yd === $mid && $side === 1)) {
                    continue;
                }

                $t = sprintf('<text x="%s" y="%s" font-size="8.3" font-weight="700" fill="%s" text-anchor="middle" letter-spacing="1.2" font-family="%s">%d</text>',
                    $x, $W - 21, $LN, self::TITLE_FONT, $yd);
                $parts[] = $t . sprintf('<g transform="rotate(180 %s %s)">%s</g>', $x, $W / 2, $t);
            }
        }

        $lw = $q['width'];
        $lh = $q['height'];
        $parts[] = sprintf('<image href="data:image/png;base64,%s" x="%.3f" y="%.3f" width="%s" height="%s" preserveAspectRatio="none"/>',
            base64_encode($logoPng), $cx - $lw / 2, $cy - $lh / 2, $lw, $lh);
        $dimY = $cy + $lh / 2 + 2.5;
        $ft = EndzoneDesigner::ft(...);
        $parts[] = sprintf('<path d="M%1$s %3$sH%2$sM%1$s %4$sV%5$sM%2$s %4$sV%5$s" stroke="#fff" stroke-width=".3"/>',
                $cx - $lw / 2, $cx + $lw / 2, $dimY, $dimY - .9, $dimY + .9)
            . sprintf('<text x="%s" y="%s" font-size="4" text-anchor="middle" fill="#fff" font-weight="700" font-family="%s">%s x %s</text>',
                $cx, $dimY + 4.4, self::BODY_FONT, $ft($lw), $ft($lh));
        $parts[] = sprintf('<text x="%s" y="%.2f" font-size="2.6" text-anchor="end" fill="#fff" font-family="%s">Hashmarks %s apart (%s field)</text>',
            $x1 - 1.5, $hashIn - 3, self::BODY_FONT, $ft($W - 2 * $hashIn), self::e(strtolower($q['fieldLabel'])));

        return [implode('', $parts), [$x0, -$m, $x1 - $x0, $W + 2 * $m]];
    }

    // ── Page ─────────────────────────────────────────────────────────────────

    private static function page(string $drawing, array $view, string $proofId, string $caption, array $for, array $project,
                                 array $specs, string $note, float $price): string
    {
        $B = self::BODY_FONT;

        // The drawing: the full 720-point width, as tall as leaves room for the row below.
        $top = 64;
        $maxW = 720;
        $maxH = 268;
        $k  = min($maxW / $view[2], $maxH / $view[3]);
        $dw = $view[2] * $k;
        $dh = $view[3] * $k;
        $dx = 36 + ($maxW - $dw) / 2;
        $dy = $top;

        $art = sprintf('<defs><clipPath id="field"><rect x="%.2f" y="%.2f" width="%.2f" height="%.2f" rx="6"/></clipPath></defs>', $dx, $dy, $dw, $dh)
             . sprintf('<g clip-path="url(#field)"><g transform="translate(%.2f %.2f) scale(%.5f) translate(%s %s)">%s</g></g>',
                 $dx, $dy, $k, -$view[0], -$view[1], $drawing);

        // The caption, in spaced capitals like the shop's proofs.
        $y = $dy + $dh + 20;
        $art .= sprintf('<text x="36" y="%.2f" font-size="10.5" fill="#222" letter-spacing="1.6" font-family="%s">%s</text>', $y, $B, self::e(mb_strtoupper($caption)));

        // One row of four: prepared for, project, stencil, price.
        $y += 22;
        $cols = [[36, 168], [216, 196], [424, 172], [608, 148]];
        $art .= self::column($cols[0][0], $y, $cols[0][1], $for[0], $for[1]);
        $art .= self::column($cols[1][0], $y, $cols[1][1], 'Project', $project);
        $art .= self::column($cols[2][0], $y, $cols[2][1], 'Stencil', array_map(static fn($s) => $s[0] . ': ' . $s[1], $specs), false);
        $art .= sprintf('<text x="%s" y="%.2f" font-size="7.5" font-weight="700" fill="#7a807b" letter-spacing="1.2" font-family="%s">PRICE</text>', $cols[3][0], $y, $B)
              . sprintf('<text x="%s" y="%.2f" font-size="26" font-weight="700" fill="#111" font-family="%s">%s</text>', $cols[3][0], $y + 27, self::TITLE_FONT, self::e(self::money($price)));
        foreach (self::wrap(self::priceNote(), 34) as $i => $part) {
            $art .= sprintf('<text x="%s" y="%.2f" font-size="7.5" fill="#666" font-family="%s">%s</text>', $cols[3][0], $y + 41 + 10 * $i, $B, self::e($part));
        }
        foreach ([1, 2, 3] as $i) {
            $art .= sprintf('<line x1="%s" y1="%.2f" x2="%s" y2="%.2f" stroke="#e2e2de" stroke-width=".75"/>', $cols[$i][0] - 12, $y - 9, $cols[$i][0] - 12, $y + 62);
        }

        // The material note, one or two lines across the page.
        $ny = $y + 74;
        $rows = self::wrap($note, 190);
        $art .= sprintf('<rect x="36" y="%.2f" width="720" height="%s" fill="#f4f4f2"/><rect x="36" y="%.2f" width="3" height="%s" fill="#7cb82f"/>',
            $ny, 8 + 10 * count($rows), $ny, 8 + 10 * count($rows));
        foreach ($rows as $i => $r) {
            $art .= sprintf('<text x="47" y="%.2f" font-size="7.8" fill="#333" font-family="%s">%s</text>', $ny + 12 + 10 * $i, $B, self::e($r));
        }

        $terms = '';
        foreach (self::wrap(self::TERMS, 178) as $i => $t) {
            $terms .= sprintf('<tspan x="226" dy="%s">%s</tspan>', $i === 0 ? 0 : 6.6, self::e($t));
        }

        $co  = config('site.company');
        $bar = implode('   |   ', array_filter([
            trim(($co['address']['city'] ?? '') . ', ' . ($co['address']['state'] ?? ''), ', '),
            'Phone: ' . ($co['phone'] ?? ''), $co['email'] ?? '',
        ]));
        $logoFile = BASE_PATH . '/public/assets/images/ussc-logo.png';
        $logo = is_file($logoFile)
            ? sprintf('<image href="data:image/png;base64,%s" x="36" y="510" width="168" height="34.9"/>', base64_encode((string)file_get_contents($logoFile)))
            : '';
        $date = date('n/j/y');

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="792pt" height="612pt" viewBox="0 0 792 612">
  <rect width="792" height="612" fill="#fff"/>
  <text x="36" y="44" font-size="26" font-weight="900" fill="#111" font-family="{$B}" letter-spacing=".3">STENCIL PROOF</text>
  <text x="756" y="34" font-size="9.5" fill="#333" text-anchor="end" font-family="{$B}">Date Created {$date}   Rev. 1</text>
  <text x="756" y="47" font-size="8" fill="#777" text-anchor="end" font-family="{$B}">Proof {$proofId}</text>
  <line x1="36" y1="54" x2="756" y2="54" stroke="#e2e2de" stroke-width=".75"/>
  {$art}
  <line x1="36" y1="498" x2="756" y2="498" stroke="#ddd" stroke-width=".75"/>
  {$logo}
  <text x="226" y="519" font-size="10.5" font-weight="700" fill="#111" font-family="{$B}">Proof Approval:</text>
  <line x1="306" y1="520" x2="600" y2="520" stroke="#111" stroke-width=".75"/>
  <text x="616" y="519" font-size="10.5" font-weight="700" fill="#111" font-family="{$B}">Date:</text>
  <line x1="646" y1="520" x2="756" y2="520" stroke="#111" stroke-width=".75"/>
  <text y="531" font-size="5.4" fill="#555" font-family="{$B}">{$terms}</text>
  <rect x="0" y="586" width="792" height="26" fill="#111"/>
  <text x="396" y="602.5" font-size="9.5" fill="#fff" text-anchor="middle" font-family="{$B}">{$bar}</text>
</svg>
SVG;
    }

    /** A heading and its lines; the first line bold unless $boldFirst is false. */
    private static function column(float $x, float $y, float $w, string $heading, array $lines, bool $boldFirst = true): string
    {
        $B = self::BODY_FONT;
        $out = sprintf('<text x="%.2f" y="%.2f" font-size="7.5" font-weight="700" fill="#7a807b" letter-spacing="1.2" font-family="%s">%s</text>', $x, $y, $B, self::e(strtoupper($heading)));
        $y += 14;
        $n = 0;

        foreach (array_values($lines) as $i => $line) {
            $bold = $boldFirst && $i === 0;
            foreach (self::wrap($line, max(20, (int)($w / ($bold ? 5.4 : 4.9)))) ?: [''] as $part) {
                if (++$n > 5) {
                    break 2;
                }
                $out .= sprintf('<text x="%.2f" y="%.2f" font-size="%s"%s fill="%s" font-family="%s">%s</text>',
                    $x, $y, $bold ? 9.5 : 8.5, $bold ? ' font-weight="700"' : '', $bold ? '#111' : '#444', $B, self::e($part));
                $y += 11.5;
            }
        }

        return $out;
    }

    /** Who the proof is for, from what the customer typed. Every field optional. */
    private static function preparedFor(array $for): array
    {
        $lines = array_values(array_filter([
            trim((string)($for['name'] ?? '')), trim((string)($for['org'] ?? '')),
            trim((string)($for['email'] ?? '')), trim((string)($for['phone'] ?? '')),
        ], static fn($v) => $v !== ''));

        return ['Prepared for', $lines !== [] ? $lines : ['Your team or school']];
    }

    private static function priceNote(): string
    {
        return 'Online price as of ' . date('F j, Y') . '. Shipping and any applicable tax are additional.';
    }

    private static function money(float $n): string
    {
        return '$' . number_format($n, 2);
    }

    /** Python's textwrap.wrap: lines of at most $width characters, words kept whole. */
    private static function wrap(string $text, int $width): array
    {
        $text = trim($text);

        return $text === '' ? [] : explode("\n", wordwrap($text, $width, "\n", true));
    }

    private static function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}

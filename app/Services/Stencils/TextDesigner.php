<?php

declare(strict_types=1);

namespace App\Services\Stencils;

use App\Repositories\StorefrontRepository;

/**
 * The custom stencil builder: a customer's text, laid out in our stencil font and priced.
 *
 * A port of the stencil shop's custom_text() (server.py), fontsvg.py and roadfont.py, so
 * a custom stencil prices and draws the same here as it did there.
 *
 * THE FONT NEVER LEAVES THE SERVER. Its license does not allow it in web pages, and this
 * repository is public. Its outlines live in storage/stencil-data/stencil_font.json
 * (exported by database/tools/export_stencil_font.py) and road_letters.json; the browser
 * only ever receives the outlines of the text the customer typed.
 *
 * Pricing is the Stencil Calculator, the formula the stock word stencils are priced by:
 * (width + margin) x (height + margin) x rate x (1 + waste) x 2.75. Sizes 48" and taller
 * are cut in road letters, each letter its own stencil, priced as that many letters.
 *
 * Letter sizes come from the Single Letter or Number listing (STCHR-*) in the catalog.
 */
class TextDesigner
{
    public const MAX_LINES      = 3;
    public const MAX_LINE_CHARS = 30;
    public const BASE_WIDTH_RATIO = 0.5;     // nominal width of the plain sizes (4"-12"), e.g. 12" x 6"
    public const MARGIN    = 7;
    public const WASTE     = 0.05;
    public const RETAIL    = 2.75;
    public const RATE      = ['1/8' => 0.015, '3/32' => 0.0134];
    public const LBS       = ['1/8' => 0.00415, '3/32' => 0.00312];
    public const SHEET     = [48, 120];
    public const SETUP_FEE = 0.0;            // the shop's setting, 0 on 19 Nov 2026
    public const MATERIALS = ['1/8' => '1/8" Heavy-Duty', '3/32' => '3/32" Standard'];

    // Road letters (roadfont.py)
    private const ROAD_MIN = 48.0;
    private const ROAD_SIDE = 4.0;
    private const ROAD_END = 3.0;
    private const ROAD_GAP = 2.0;
    private const ROAD_SKIP = " -";

    private array $font;
    private array $road;
    private float $letterRatio;

    public function __construct(private StorefrontRepository $shop = new StorefrontRepository())
    {
        $dir = BASE_PATH . '/storage/stencil-data/';
        $this->font = is_file($dir . 'stencil_font.json') ? (json_decode((string)file_get_contents($dir . 'stencil_font.json'), true) ?: []) : [];
        $this->road = is_file($dir . 'road_letters.json') ? (json_decode((string)file_get_contents($dir . 'road_letters.json'), true) ?: []) : [];

        // Average width of the capitals as a share of their height: sizes that state a width
        // (18" x 9") are scaled so a typical letter is that wide.
        $sum = 0;
        foreach (str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ') as $c) {
            $b = $this->font['chars'][$c]['bbox'] ?? [0, 0, 0, 0];
            $sum += $b[2] - $b[0];
        }
        $this->letterRatio = $this->ready() ? $sum / 26 / $this->font['capHeight'] : 0.5;
    }

    public function ready(): bool
    {
        return isset($this->font['chars']) && $this->sizes() !== [];
    }

    /** Letter sizes from the Single Letter or Number listing; curb sizes are not offered. */
    public function sizes(): array
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $rows = $this->shop->singleCharacterSizes();

        $sizes = [];

        foreach ($rows as $r) {
            $parts = explode(' · ', (string)$r['variant_label']);
            $label = trim($parts[0]);
            $mat   = str_replace('"', '', trim((string)end($parts)));

            if (count($parts) < 2 || stripos($label, 'curb') !== false || !isset(self::RATE[$mat])) {
                continue;
            }

            if (!preg_match('/^([\d.]+)"(?:\s*x\s*([\d.]+)")?$/i', $label, $m)) {
                continue;
            }

            $key = strtoupper((string)preg_replace('/-(8|32)$/', '', $r['sku']));
            $h   = (float)$m[1];

            $sizes[$key] ??= ['key' => $key, 'label' => $label, 'height' => $h,
                              'width' => isset($m[2]) ? (float)$m[2] : $h * self::BASE_WIDTH_RATIO,
                              'widthStated' => isset($m[2]), 'prices' => []];
            $sizes[$key]['prices'][$mat] = (float)$r['website_price'];
        }

        return $cache = array_values($sizes);
    }

    public function has(string $ch): bool
    {
        return $ch === ' ' || isset($this->font['chars'][$ch]);
    }

    /** The Stencil Calculator's single-stencil retail price for a lettering size. */
    public static function calculatorPrice(float $w, float $h, string $thickness): float
    {
        $area = ($w + self::MARGIN) * ($h + self::MARGIN);

        return round($area * self::RATE[$thickness] * (1 + self::WASTE) * self::RETAIL, 2);
    }

    /** @throws DesignerError */
    public function quote(string $text, string $sizeKey, string $thickness): array
    {
        if (!isset($this->font['chars'])) {
            throw new DesignerError('The custom stencil builder is not available just now. Please call us for a quote.');
        }

        $lines = array_values(array_filter(
            array_map(static fn($l) => mb_strtoupper(trim($l)), explode("\n", str_replace("\r", '', $text))),
            static fn($l) => $l !== ''
        ));

        if ($lines === []) {
            throw new DesignerError('Type the text for your stencil.');
        }

        if (count($lines) > self::MAX_LINES) {
            throw new DesignerError('Up to ' . self::MAX_LINES . ' lines per stencil.');
        }

        foreach ($lines as $l) {
            if (mb_strlen($l) > self::MAX_LINE_CHARS) {
                throw new DesignerError('Up to ' . self::MAX_LINE_CHARS . ' characters per line.');
            }
        }

        $size = null;
        foreach ($this->sizes() as $s) {
            if ($s['key'] === strtoupper($sizeKey)) {
                $size = $s;
            }
        }

        if ($size === null || !isset($size['prices'][$thickness], self::RATE[$thickness])) {
            throw new DesignerError('Choose a letter size and material.');
        }

        if ($this->roadApplies($lines, $size['width'], $size['height'])) {
            return $this->roadText($lines, $size, $thickness);
        }

        $bad = [];
        foreach ($lines as $l) {
            foreach (mb_str_split($l) as $ch) {
                if (!$this->has($ch)) {
                    $bad[$ch] = true;
                }
            }
        }

        if ($bad !== []) {
            $bad = array_keys($bad);
            sort($bad);
            throw new DesignerError("These characters aren't available in our stencil font: " . implode(' ', $bad));
        }

        $letters = 0;
        foreach ($lines as $l) {
            $letters += mb_strlen(str_replace(' ', '', $l));
        }

        // Layout in inches. Plain sizes (4"-12") keep the font's own proportions, which
        // match the stock stencils; sizes with a stated width are scaled to it.
        $h     = $size['height'];
        $scale = $h / $this->font['capHeight'];
        $sx    = $size['widthStated'] ? $size['width'] / ($this->letterRatio * $h) : 1.0;
        $gap   = $h * 0.5;

        $widths = [];
        $extents = [];
        foreach ($lines as $l) {
            [$left, $right] = $this->inkExtent($l);
            $extents[] = $left;
            $widths[]  = ($right - $left) * $scale * $sx;
        }

        $textW  = max($widths);
        $textH  = count($lines) * $h + (count($lines) - 1) * $gap;
        $plateW = round($textW + self::MARGIN, 1);
        $plateH = round($textH + self::MARGIN, 1);
        $stencilPrice = self::calculatorPrice($textW, $textH, $thickness);

        $paths = [];
        foreach ($lines as $i => $l) {
            $x0 = ($plateW - $widths[$i]) / 2 - $extents[$i] * $scale * $sx;
            $baseline = self::MARGIN / 2 + $h * ($i + 1) + $gap * $i;
            $paths[] = $this->path($l, $x0, $baseline, $scale, $sx);
        }

        $long  = max($plateW, $plateH);
        $short = min($plateW, $plateH);
        $pieces = (int)(ceil($long / self::SHEET[1]) * ceil($short / self::SHEET[0]));

        return [
            'lines' => $lines, 'letters' => $letters, 'size' => $size['label'], 'sizeKey' => $size['key'],
            'thickness' => $thickness, 'material' => self::MATERIALS[$thickness],
            'letterHeight' => $h, 'letterWidth' => round($this->letterRatio * $h * $sx, 1),
            'stencilPrice' => $stencilPrice, 'setupFee' => self::SETUP_FEE, 'price' => round($stencilPrice + self::SETUP_FEE, 2),
            'plate' => ['width' => $plateW, 'height' => $plateH], 'pieces' => $pieces,
            'text' => ['width' => round($textW, 1), 'height' => round($textH, 1),
                       'x' => round(($plateW - $textW) / 2, 2), 'y' => round(self::MARGIN / 2, 2)],
            'weight' => round($plateW * $plateH * (self::LBS[$thickness] ?? 0.00415), 2),
            'path' => implode(' ', $paths),
        ];
    }

    // ── Font (fontsvg.py) ────────────────────────────────────────────────────

    private function advance(string $ch): float
    {
        if ($ch === ' ') {
            return $this->font['spaceAdvance'];
        }

        return $this->font['chars'][$ch]['adv'] ?? $this->font['missingAdvance'];
    }

    /** Left and right edges of the actual lettering, in font units. */
    private function inkExtent(string $text): array
    {
        $x = 0;
        $left = null;
        $right = null;

        foreach (mb_str_split($text) as $ch) {
            if ($ch !== ' ' && isset($this->font['chars'][$ch])) {
                [$xmin, , $xmax] = $this->font['chars'][$ch]['bbox'];
                $left  = $left === null ? $x + $xmin : min($left, $x + $xmin);
                $right = $right === null ? $x + $xmax : max($right, $x + $xmax);
            }

            $x += $this->advance($ch);
        }

        return [$left ?? 0, $right ?? 0];
    }

    /** SVG path data for one line. Font y points up; SVG y points down. */
    private function path(string $text, float $x0, float $baseline, float $scale, float $sx): string
    {
        $parts = [];
        $x = 0;

        foreach (mb_str_split($text) as $ch) {
            if ($ch !== ' ') {
                foreach ($this->font['chars'][$ch]['c'] ?? [] as $contour) {
                    $pts = array_map(static fn($p) => [$x0 + ($x + $p[0]) * $scale * $sx, $baseline - $p[1] * $scale, (bool)$p[2]], $contour);
                    $parts[] = self::contourToPath($pts);
                }
            }

            $x += $this->advance($ch);
        }

        return implode(' ', $parts);
    }

    /** A quadratic TrueType contour as an SVG path, implied on-curve midpoints added. */
    private static function contourToPath(array $pts): string
    {
        if ($pts === []) {
            return '';
        }

        if (!$pts[0][2]) {
            $on = null;
            foreach ($pts as $i => $p) {
                if ($p[2]) { $on = $i; break; }
            }

            if ($on === null) {
                $a = $pts[0];
                $b = $pts[1];
                array_unshift($pts, [($a[0] + $b[0]) / 2, ($a[1] + $b[1]) / 2, true]);
            } else {
                $pts = array_merge(array_slice($pts, $on), array_slice($pts, 0, $on));
            }
        }

        $f = static fn(float $v) => rtrim(rtrim(sprintf('%.2f', $v), '0'), '.');
        $out = 'M' . $f($pts[0][0]) . ' ' . $f($pts[0][1]);
        $n = count($pts);
        $i = 1;

        while ($i <= $n) {
            $p = $pts[$i % $n];

            if ($p[2]) {
                $out .= 'L' . $f($p[0]) . ' ' . $f($p[1]);
                $i++;
                continue;
            }

            $next = $pts[($i + 1) % $n];

            if ($next[2]) {
                $end = $next;
                $i += 2;
            } else {
                $end = [($p[0] + $next[0]) / 2, ($p[1] + $next[1]) / 2];
                $i++;
            }

            $out .= 'Q' . $f($p[0]) . ' ' . $f($p[1]) . ' ' . $f($end[0]) . ' ' . $f($end[1]);
        }

        return $out . 'Z';
    }

    // ── Road letters (roadfont.py) ───────────────────────────────────────────

    private function roadApplies(array $lines, float $width, float $height): bool
    {
        if ($height < self::ROAD_MIN || $width <= 0 || !isset($this->road['letters'])) {
            return false;
        }

        $chars = [];
        foreach ($lines as $l) {
            foreach (mb_str_split($l) as $c) {
                if (!str_contains(self::ROAD_SKIP, $c)) {
                    $chars[] = $c;
                }
            }
        }

        foreach ($chars as $c) {
            if (!isset($this->road['letters'][$c])) {
                return false;
            }
        }

        return $chars !== [];
    }

    private function roadLetterPath(string $ch, float $x, float $y, float $width, float $height): string
    {
        $sx = $width / $this->road['standard'];
        $sy = $height / $this->road['cap'];
        $out = '';

        foreach ($this->road['letters'][$ch]['path'] as $seg) {
            $nums = array_slice($seg, 1);
            $xy = [];
            for ($i = 0; $i + 1 < count($nums); $i += 2) {
                $xy[] = sprintf('%.2f %.2f', $x + $nums[$i] * $sx, $y + $nums[$i + 1] * $sy);
            }
            $out .= $seg[0] . implode(' ', $xy);
        }

        return $out;
    }

    /** Each letter its own stencil, priced like one stock letter (STOP 96" = 4 letters). */
    private function roadText(array $lines, array $size, string $thickness): array
    {
        $h = $size['height'];
        $w = $size['width'];
        $sheetW = $w + 2 * self::ROAD_SIDE;
        $sheetH = $h + 2 * self::ROAD_END;

        $rows = [];
        foreach ($lines as $l) {
            $x = 0.0;
            $row = [];
            foreach (mb_str_split($l) as $ch) {
                if (str_contains(self::ROAD_SKIP, $ch)) {
                    $x += $sheetW / 2;
                    continue;
                }
                $row[] = [$ch, $x];
                $x += $sheetW + self::ROAD_GAP;
            }
            $rows[] = [$row, $row !== [] ? $x - self::ROAD_GAP : 0];
        }

        $totalW = max(array_column($rows, 1));
        $sheets = [];
        $parts = [];

        foreach ($rows as $i => [$row, $rowW]) {
            $y = $i * ($sheetH + self::ROAD_GAP);
            $shift = ($totalW - $rowW) / 2;

            foreach ($row as [$ch, $x]) {
                $sx = $shift + $x;
                $lw = $this->road['letters'][$ch]['w'] * $w / $this->road['standard'];
                $sheets[] = ['x' => round($sx, 3), 'y' => round($y, 3), 'width' => $sheetW, 'height' => $sheetH, 'char' => $ch];
                $parts[] = $this->roadLetterPath($ch, $sx + ($sheetW - $lw) / 2, $y + self::ROAD_END, $w, $h);
            }
        }

        $x1 = min(array_column($sheets, 'x')) + self::ROAD_SIDE;
        $x2 = max(array_column($sheets, 'x')) + self::ROAD_SIDE + $w;
        $totalH = count($rows) * $sheetH + (count($rows) - 1) * self::ROAD_GAP;
        $y1 = self::ROAD_END;
        $y2 = $totalH - self::ROAD_END;
        $n = count($sheets);

        $stencilPrice = round($n * self::calculatorPrice($w, $h, $thickness), 2);

        return [
            'lines' => $lines, 'letters' => $n, 'size' => $size['label'], 'sizeKey' => $size['key'],
            'thickness' => $thickness, 'material' => self::MATERIALS[$thickness],
            'letterHeight' => $h, 'letterWidth' => $w, 'stencilPrice' => $stencilPrice, 'setupFee' => self::SETUP_FEE,
            'price' => round($stencilPrice + self::SETUP_FEE, 2),
            'plate' => ['width' => round($totalW, 1), 'height' => round($totalH, 1)], 'pieces' => $n,
            'text' => ['width' => round($x2 - $x1, 1), 'height' => round($y2 - $y1, 1), 'x' => round($x1, 2), 'y' => round($y1, 2)],
            'sheets' => $sheets,
            'weight' => round($n * $sheetW * $sheetH * (self::LBS[$thickness] ?? 0.00415), 2),
            'path' => implode('', $parts),
        ];
    }
}

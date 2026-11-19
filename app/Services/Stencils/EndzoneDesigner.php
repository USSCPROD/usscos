<?php

declare(strict_types=1);

namespace App\Services\Stencils;

use App\Repositories\StorefrontRepository;

/**
 * Custom endzone lettering: layout in our football letters, and its price.
 *
 * A port of the stencil shop's endzone.py, line for line where it matters, so a design
 * prices the same here as it did there. Everything is in feet.
 *
 * Letters come from storage/stencil-data/endzone_letters.json, two styles: outline (the
 * default) and solid. That file is the outlines of our stencil lettering: it lives on the
 * server, never in this repository, which is public. Only the outlines of the text a
 * customer types ever leave the server, exactly as the shop did.
 *
 * Two ways to buy:
 *   letters  each letter its own stencil, priced like one Football Block Letter of that
 *            height (STBL-15 … STBL-25 in USSCOS, so the price follows the catalog)
 *   stencil  the whole word as one large stencil, priced with the Stencil Quote
 *            Calculator (stencil-quote-full.php): 2' border, 9.5' x 25.5' panels,
 *            material + labor + overhead, retail = cost x 2.5, plus boxes at cost. Up to
 *            6 panels ship as 1 section, up to 12 as 2, more as 3.
 */
class EndzoneDesigner
{
    public const DATA       = '/storage/stencil-data/endzone_letters.json';
    public const MATERIAL   = '10mil';
    public const END_ZONE   = [160.0, 30.0];
    public const MAX_CHARS  = 30;
    public const SPACE      = 0.45;        // a word space, as a share of the letter height
    public const STYLES     = ['outline' => 'Outline', 'solid' => 'Solid'];
    public const FORMATS    = ['letters' => 'Individual letters', 'stencil' => 'One large stencil'];

    // Stencil Quote Calculator settings (stencil-quote-full.php)
    public const PLASTIC        = 1.76;    // per linear foot: $611 roll / 400' + 15% waste
    public const GROMMET        = 0.36;
    public const SEAM_TAPE      = 0.24;
    public const TAPE           = 0.11;
    public const MAN_HOUR       = 70;
    public const BASE_HOURS     = 1;
    public const HOURS_PER_PANEL = 0.5;
    public const OVERHEAD       = 15;
    public const BOX_COST       = 4;
    public const PANEL_W        = 9.5;
    public const PANEL_L        = 25.5;
    public const BORDER         = 2;
    public const LBS_PER_SQ_FT  = 0.048;
    public const RETAIL         = 2.5;
    public const WHOLESALE      = 1.75;
    public const PANELS_PER_BOX = 6;
    public const LETTERS_PER_BOX = 6;
    public const BOX_DIMS       = [24, 18, 12];

    private array $data;

    public function __construct(private StorefrontRepository $shop = new StorefrontRepository())
    {
        $file = BASE_PATH . self::DATA;
        $this->data = is_file($file) ? (json_decode((string)file_get_contents($file), true) ?: []) : [];
    }

    public function ready(): bool
    {
        return isset($this->data['solid'], $this->data['outline']);
    }

    /** Letter heights a customer can pick, from the Football Block Letters in the catalog. */
    public function sizes(): array
    {
        $out = [];

        foreach ($this->shop->footballLetterSizes() as $r) {
            if (!preg_match('/^STBL-(\d+)$/i', (string)$r['sku'], $m) || (float)$r['website_price'] <= 0) {
                continue;
            }

            $h = (float)$m[1];
            $out[] = ['key' => strtoupper($r['sku']), 'label' => self::ft($h), 'height' => $h,
                      'letterPrice' => round((float)$r['website_price'], 2), 'sku' => $r['sku']];
        }

        usort($out, static fn($a, $b) => $a['height'] <=> $b['height']);

        return $out;
    }

    public function has(string $ch): bool
    {
        return $ch === ' ' || isset($this->data['solid']['letters'][$ch]);
    }

    /** Letters set in one line, $height feet tall: glyph positions and SVG path data, in feet. */
    public function layout(string $text, float $height, string $style): array
    {
        $font  = $this->data[$style];
        $solid = $this->data['solid'];
        $k     = $height / $font['cap'];
        $gap   = $font['gap'] * $k;
        $band  = ($font['cap'] - $solid['cap']) / 2;   // outline letters are the solid letters plus this band

        $x = 0.0;
        $glyphs = [];
        $parts = [];
        $fill = [];

        foreach (mb_str_split($text) as $ch) {
            if ($ch === ' ') {
                $x += self::SPACE * $height;
                continue;
            }

            $g = $font['letters'][$ch];
            $w = $g['w'] * $k;
            $d = '';

            foreach ($g['path'] as $seg) {
                $d .= $seg[0] . self::pairs(array_slice($seg, 1), $x, 0.0, $k);
            }

            $glyphs[] = ['char' => $ch, 'x' => round($x, 3), 'width' => round($w, 3), 'd' => $d];
            $parts[]  = $d;

            if ($style === 'outline') {
                // The inside of an outline letter, painted by hand in a second color: the
                // solid letter, centred.
                $s  = $solid['letters'][$ch];
                $dx = $x + ($g['w'] - $s['w']) / 2 * $k;

                foreach ($s['path'] as $seg) {
                    $fill[] = $seg[0] . self::pairs(array_slice($seg, 1), $dx, $band, $k);
                }
            }

            $x += $w + $gap;
        }

        $last  = end($glyphs);
        $width = $last ? $last['x'] + $last['width'] : 0;

        return ['glyphs' => $glyphs, 'path' => implode('', $parts), 'fill' => implode('', $fill),
                'width' => round($width, 2), 'height' => $height];
    }

    /**
     * "x y x y" for a segment's numbers. Letter x is offset by $dx after scaling; for the
     * fill, y is shifted by $band BEFORE scaling, which is how endzone.py does it.
     */
    private static function pairs(array $nums, float $dx, float $band, float $k): string
    {
        $out = [];

        for ($i = 0; $i + 1 < count($nums); $i += 2) {
            $out[] = sprintf('%.3f %.3f', $dx + $nums[$i] * $k, ($nums[$i + 1] + $band) * $k);
        }

        return implode(' ', $out);
    }

    /** The Stencil Quote Calculator on lettering length x height, panels laid as they are cut. */
    public static function calculator(float $length, float $height): array
    {
        $h = $height + self::BORDER;
        $l = $length + self::BORDER;
        $tall   = (int)ceil($h / self::PANEL_L);
        $along  = (int)ceil($l / self::PANEL_W);
        $panels = $tall * $along;
        $panelH = $h / $tall;
        $linear = $panels * $panelH;
        $labor  = self::BASE_HOURS + $panels * self::HOURS_PER_PANEL;
        $boxes  = ($h > 25 || $l > 25) ? 2 : 1;

        $cost = $linear * self::PLASTIC + ($panels * 2 + 2) * self::GROMMET + $linear * self::SEAM_TAPE
              + ($panels * 2 + 6) * self::TAPE + $labor * self::MAN_HOUR + self::OVERHEAD;
        $boxCost = $boxes * self::BOX_COST;    // boxes at cost, without the retail markup

        return ['width' => round($l, 2), 'height' => round($h, 2), 'tall' => $tall, 'along' => $along,
                'panels' => $panels, 'panelWidth' => round($l / $along, 2), 'panelHeight' => round($panelH, 2),
                'weight' => (int)ceil($h * $l * self::LBS_PER_SQ_FT),
                'price' => round($cost * self::RETAIL + $boxCost, 2),
                'wholesale' => round($cost * self::WHOLESALE + $boxCost, 2)];
    }

    public static function sections(int $panels): int
    {
        return $panels <= 6 ? 1 : ($panels <= 12 ? 2 : 3);
    }

    /** Panels in each section; an odd position goes to the first sections. */
    public static function sectionPanels(int $panels, int $tall = 1): array
    {
        $n     = self::sections($panels);
        $along = intdiv($panels, $tall);
        $out   = [];

        for ($i = 0; $i < $n; $i++) {
            $out[] = (intdiv($along, $n) + ($i < $along % $n ? 1 : 0)) * $tall;
        }

        return $out;
    }

    /** @throws DesignerError */
    public function quote(string $text, string $sizeKey, string $style, string $format): array
    {
        if (!$this->ready()) {
            throw new DesignerError('The endzone designer is not available just now. Please call us for a quote.');
        }

        $text = mb_strtoupper(trim((string)preg_replace('/\s+/u', ' ', $text)));

        if ($text === '') {
            throw new DesignerError('Type your endzone text.');
        }

        if (mb_strlen($text) > self::MAX_CHARS) {
            throw new DesignerError('Up to ' . self::MAX_CHARS . ' characters.');
        }

        $bad = array_values(array_unique(array_filter(mb_str_split($text), fn($c) => !$this->has($c))));
        sort($bad);

        if ($bad !== []) {
            throw new DesignerError("These characters aren't available in our endzone lettering: " . implode(' ', $bad));
        }

        if (!isset(self::STYLES[$style]) || !isset(self::FORMATS[$format])) {
            throw new DesignerError("Choose a lettering style and how you'd like it cut.");
        }

        $size = null;

        foreach ($this->sizes() as $s) {
            if ($s['key'] === strtoupper($sizeKey)) {
                $size = $s;
            }
        }

        if ($size === null) {
            throw new DesignerError('Choose a letter height.');
        }

        $h   = $size['height'];
        $lay = $this->layout($text, $h, $style);
        $n   = count($lay['glyphs']);

        if ($lay['width'] > self::END_ZONE[0]) {
            throw new DesignerError(sprintf(
                "At %s tall this is %.0f' long, more than a %s end zone. Choose a smaller letter height or shorten the text.",
                $size['label'], $lay['width'], self::ft(self::END_ZONE[0])
            ));
        }

        $out = ['text' => $text, 'style' => $style, 'styleLabel' => self::STYLES[$style], 'format' => $format,
                'formatLabel' => self::FORMATS[$format], 'size' => $size['label'], 'sizeKey' => $size['key'],
                'letterHeight' => $h, 'letters' => $n, 'lettering' => ['width' => $lay['width'], 'height' => $h],
                'glyphs' => $lay['glyphs'], 'path' => $lay['path'], 'fill' => $lay['fill'],
                'endZone' => ['width' => self::END_ZONE[0], 'height' => self::END_ZONE[1]], 'material' => self::MATERIAL,
                'boxDims' => self::BOX_DIMS];

        if ($format === 'letters') {
            $weight = 0.0;

            foreach ($lay['glyphs'] as $g) {
                $weight += ($g['width'] + self::BORDER) * ($h + self::BORDER) * self::LBS_PER_SQ_FT;
            }

            // Wholesale as the shop did it: each letter at the price sheet's wholesale.
            $out += ['price' => round($n * $size['letterPrice'], 2), 'letterPrice' => $size['letterPrice'],
                     'wholesale' => round($n * round($size['letterPrice'] * 2 / 2.75, 2), 2),
                     'pieces' => $n, 'weight' => (int)ceil($weight), 'boxes' => (int)ceil($n / self::LETTERS_PER_BOX),
                     'border' => self::BORDER / 2];
        } else {
            $c = self::calculator($lay['width'], $h);
            $out += ['price' => $c['price'], 'wholesale' => $c['wholesale'], 'pieces' => self::sections($c['panels']),
                     'sections' => self::sections($c['panels']), 'sectionPanels' => self::sectionPanels($c['panels'], $c['tall']),
                     'panels' => $c['panels'], 'tall' => $c['tall'], 'along' => $c['along'],
                     'panelWidth' => $c['panelWidth'], 'panelHeight' => $c['panelHeight'],
                     'sheet' => ['width' => $c['width'], 'height' => $c['height']], 'border' => self::BORDER / 2,
                     'weight' => $c['weight'], 'boxes' => (int)ceil($c['panels'] / self::PANELS_PER_BOX)];
        }

        return $out;
    }

    /** 15 → "15'", 15.5 → "15.5'" (Python's f"{n:g}'"). */
    public static function ft(float $n): string
    {
        return rtrim(rtrim(number_format(round($n, 1), 1, '.', ''), '0'), '.') . "'";
    }
}

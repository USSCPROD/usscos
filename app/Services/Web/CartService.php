<?php

declare(strict_types=1);

namespace App\Services\Web;

use App\Repositories\StorefrontRepository;
use App\Services\Stencils\DesignerError;
use App\Services\Stencils\EndzoneDesigner;
use App\Services\Stencils\MidfieldDesigner;
use App\Services\Stencils\TextDesigner;

/**
 * The website cart, priced. A port of the stencil shop's price_cart().
 *
 * NEVER TRUST THE BROWSER'S PRICES. The cart lives in the customer's browser and arrives
 * as what was chosen, not what it costs: every line is priced here, from the catalog or
 * from the designer that made it, every time.
 *
 * What can be bought online: stencils, and paint that goes by FedEx. A stock stencil is
 * any priced, published SKU filed under Stencils; custom text, endzone and midfield come
 * from their designers. Aluminum ships by truck freight and is ordered by phone, as on the
 * shop. Paint is any other priced, published product USSCOS can pack for FedEx
 * (StorefrontRepository::parcelProduct); its boxes come from the order desk's own packing
 * rules (CartonizerService), and an order too big for FedEx is not taken online.
 *
 * Items, as the browser sends them:
 *   {kind: 'stock',    sku, qty}
 *   {kind: 'text',     text, size, thickness, qty}
 *   {kind: 'endzone',  text, size, style, format, fill, line, qty}
 *   {kind: 'midfield', logo, field, width, qty}
 *
 * Price tiers. Retail is the catalog price. A distributor (or stocking distributor, which
 * has no stencil prices of its own) pays wholesale, by the old shop's rules, which are the
 * price sheet's: stock stencils and custom text at retail x 2 / 2.75, endzone letters at
 * each letter's wholesale, and calculator-priced endzones and midfields at cost x 1.75.
 * The stencil shop's catalog is the authority for stencil prices, which is why this does
 * not read the products' distributor columns the way the order desk does. Paint does:
 * a distributor pays its CustomerPricing price, never more than the website price.
 */
class CartService
{
    public const MAX_QTY   = 500;
    public const MAX_LINES = 100;
    private const MARGIN   = 7;          // plastic around the lettering, inches
    private const SHEET    = [48, 120];  // largest sheet, inches
    private const MATERIAL = ['1-8' => '1/8', '3-32' => '3/32', '10-mil' => '10mil', 'aluminum' => 'aluminum'];
    public const MATERIAL_LABEL = ['1/8' => '1/8" plastic', '3/32' => '3/32" plastic', '10mil' => '10-mil polyethylene'];
    public const WHOLESALE_RATIO = 2 / 2.75;

    public function __construct(private StorefrontRepository $shop = new StorefrontRepository())
    {
    }

    /**
     * @return array{lines: array<int,array<string,mixed>>, subtotal: float, pieces: array<int,array<string,mixed>>}
     * @throws DesignerError with a message for the customer
     */
    public function price(array $items, string $tier = \App\Services\CustomerPricing::RETAIL): array
    {
        $trade = $tier !== \App\Services\CustomerPricing::RETAIL;

        $items = array_slice(array_values(array_filter($items, 'is_array')), 0, self::MAX_LINES);

        if ($items === []) {
            throw new DesignerError('Your cart is empty.');
        }

        $lines = [];
        $pieces = [];
        $parcels = [];
        $subtotal = 0.0;

        foreach ($items as $item) {
            $qty = (int)($item['qty'] ?? 0);

            if ($qty < 1 || $qty > self::MAX_QTY) {
                throw new DesignerError('Quantity must be between 1 and ' . self::MAX_QTY . '.');
            }

            $line = match ((string)($item['kind'] ?? '')) {
                'stock'    => $this->stock($item, $tier),
                'text'     => $this->text($item),
                'endzone'  => $this->endzone($item),
                'midfield' => $this->midfield($item),
                default    => throw new DesignerError('Something in your cart looks wrong. Please refresh and try again.'),
            };

            $line['qty']    = $qty;
            $line['retail'] = $line['unit'];
            $line['unit']   = $trade ? $line['_wholesale'] : $line['unit'];
            unset($line['_wholesale']);
            $lines[] = $line;
            $subtotal += $line['unit'] * $qty;

            if (isset($line['_parcel'])) {
                $parcels[] = ['product_id' => $line['_parcel'], 'qty' => $qty];
            }

            foreach (array_fill(0, $qty, null) as $_) {
                foreach ($line['_pieces'] as $p) {
                    $pieces[] = $p;
                }
            }
        }

        foreach ($lines as &$l) {
            unset($l['_pieces'], $l['_parcel']);
        }

        // pieces: rolled stencils (StencilPacking). parcels: paint, by product and cases,
        // for CartonizerService.
        return ['lines' => $lines, 'subtotal' => round($subtotal, 2), 'pieces' => $pieces, 'parcels' => $parcels, 'tier' => $tier];
    }

    // ── Kinds ────────────────────────────────────────────────────────────────

    private function stock(array $item, string $tier = \App\Services\CustomerPricing::RETAIL): array
    {
        $p = $this->shop->cartProduct((string)($item['sku'] ?? ''));

        if ($p === false) {
            $paint = $this->shop->parcelProduct((string)($item['sku'] ?? ''));

            if ($paint !== false) {
                return $this->paint($paint, $tier);
            }
        }

        if ($p === false || (float)$p['website_price'] <= 0) {
            throw new DesignerError('An item in your cart is no longer available. Please remove it and try again.');
        }

        $mat = self::MATERIAL[StorefrontRepository::materialSlug((string)$p['variant_label'])] ?? '1/8';

        if ($mat === 'aluminum') {
            throw new DesignerError('Aluminum stencils ship by truck freight and are ordered by phone. Please call us to order.');
        }

        $desc  = html_entity_decode(strip_tags((string)$p['website_short_description']), ENT_QUOTES | ENT_HTML5);
        [$w, $h] = self::dimensions($desc);
        $count = preg_match('/(\d+)\s+pieces/', $desc, $m) ? max(1, (int)$m[1]) : 1;
        $weight = (float)($p['net_weight'] ?: $p['case_weight_gross'] ?: 0);
        $roll  = min(min($w ?? 12, $h ?? 12) + self::MARGIN, self::SHEET[0]);
        $title = trim((string)$p['website_name']) ?: (string)$p['name'];
        $label = trim((string)$p['variant_label']);

        return [
            'kind' => 'stock', 'sku' => $p['sku'], 'product_id' => (int)$p['id'],
            'name' => $title . ($label !== '' ? ' – ' . $label : ''),
            'detail' => $desc, 'unit' => round((float)$p['website_price'], 2),
            '_wholesale' => self::wholesale((float)$p['website_price']),
            'url' => StorefrontRepository::urlFor($p, true), 'image' => StorefrontRepository::imageFor($p),
            '_pieces' => array_fill(0, $count, ['weight' => $weight / $count, 'short_side' => $roll, 'thickness' => $mat]),
        ];
    }

    /** Paint (and anything else sold by the case or pail): one line, packed by the cartonizer. */
    private function paint(array $p, string $tier): array
    {
        $retail = round((float)$p['website_price'], 2);
        $trade  = \App\Services\CustomerPricing::priceFor($p, $tier)['price'];
        $title  = trim((string)$p['website_name']) ?: (string)$p['name'];
        $label  = trim((string)$p['variant_label']);

        return [
            'kind' => 'stock', 'sku' => $p['sku'], 'product_id' => (int)$p['id'],
            'name' => $title . ($label !== '' && stripos($title, $label) === false ? ' – ' . $label : ''),
            'detail' => trim(html_entity_decode(strip_tags((string)$p['website_short_description']), ENT_QUOTES | ENT_HTML5)),
            'unit' => $retail,
            '_wholesale' => $trade > 0 ? min($retail, $trade) : $retail,
            'url' => StorefrontRepository::urlFor($p, true), 'image' => StorefrontRepository::imageFor($p),
            '_pieces' => [], '_parcel' => (int)$p['id'],
        ];
    }

    private function text(array $item): array
    {
        $q = (new TextDesigner($this->shop))->quote((string)($item['text'] ?? ''), (string)($item['size'] ?? ''), (string)($item['thickness'] ?? ''));
        $each = isset($q['sheets']) ? $q['sheets'][0] : $q['plate'];
        $roll = min(min($each['width'], $each['height']), self::SHEET[0]);

        return [
            'kind' => 'text', 'sku' => 'STEN-CUSTOM-TEXT',
            'name' => 'Custom Stencil “' . implode(' / ', $q['lines']) . '” – ' . $q['size'] . ' letters (' . self::MATERIAL_LABEL[$q['thickness']] . ')',
            'detail' => sprintf('Lettering %s" × %s"', $q['text']['width'], $q['text']['height'])
                      . (isset($q['sheets']) ? ' · ' . $q['pieces'] . ' letter stencils' : sprintf(' · sheet %s" × %s"', $q['plate']['width'], $q['plate']['height'])),
            'unit' => $q['price'], '_wholesale' => self::wholesale($q['price']), 'url' => '/custom',
            'design' => ['text' => implode("\n", $q['lines']), 'size' => $q['sizeKey'], 'thickness' => $q['thickness']],
            '_pieces' => array_fill(0, $q['pieces'], ['weight' => $q['weight'] / $q['pieces'], 'short_side' => $roll, 'thickness' => $q['thickness']]),
        ];
    }

    private function endzone(array $item): array
    {
        $q = (new EndzoneDesigner($this->shop))->quote((string)($item['text'] ?? ''), (string)($item['size'] ?? ''),
                                                       (string)($item['style'] ?? ''), (string)($item['format'] ?? ''));
        $how = $q['format'] === 'letters' ? $q['letters'] . ' individual letter stencils'
             : 'one large stencil in ' . $q['sections'] . ' section' . ($q['sections'] > 1 ? 's' : '') . ' (' . $q['panels'] . ' panels)';

        return [
            'kind' => 'endzone', 'sku' => 'STEN-CUSTOM-ENDZONE',
            'name' => 'Custom Endzone “' . $q['text'] . '” – ' . $q['size'] . ' ' . strtolower($q['styleLabel']) . ' letters, ' . $how . ' (10-mil polyethylene)',
            'detail' => sprintf("Lettering %s × %s", EndzoneDesigner::ft($q['lettering']['width']), EndzoneDesigner::ft($q['lettering']['height'])),
            'unit' => $q['price'], '_wholesale' => $q['wholesale'], 'url' => '/custom/endzone',
            'design' => ['text' => $q['text'], 'size' => $q['sizeKey'], 'style' => $q['style'], 'format' => $q['format'],
                         'fill' => (string)($item['fill'] ?? ''), 'line' => (string)($item['line'] ?? '')],
            '_pieces' => [['weight' => $q['weight'], 'thickness' => '10mil', 'fixed' => ['boxes' => $q['boxes'], 'dims' => $q['boxDims']]]],
        ];
    }

    private function midfield(array $item): array
    {
        $q = (new MidfieldDesigner())->quote((string)($item['logo'] ?? ''), (string)($item['field'] ?? ''), $item['width'] ?? null);

        return [
            'kind' => 'midfield', 'sku' => 'STEN-CUSTOM-MIDFIELD',
            'name' => sprintf('Custom Midfield Logo – %s × %s on a %s field (10-mil polyethylene)',
                EndzoneDesigner::ft($q['width']), EndzoneDesigner::ft($q['height']), strtolower($q['fieldLabel'])),
            'detail' => sprintf("Stencil %s × %s, %d panels", EndzoneDesigner::ft($q['sheet']['width']), EndzoneDesigner::ft($q['sheet']['height']), $q['panels']),
            'unit' => $q['price'], '_wholesale' => $q['wholesale'], 'url' => '/custom/midfield', 'image' => $q['logoUrl'],
            'design' => ['logo' => $q['logo'], 'field' => $q['field'], 'width' => $q['width']],
            '_pieces' => [['weight' => $q['weight'], 'thickness' => '10mil', 'fixed' => ['boxes' => $q['boxes'], 'dims' => $q['boxDims']]]],
        ];
    }

    /** The price sheet's wholesale for a retail price, rounded as the shop rounded it. */
    public static function wholesale(float $retail): float
    {
        return round(round($retail, 2) * self::WHOLESALE_RATIO, 2);
    }

    /** '28" wide × 4" tall' → [28, 4]; feet become inches. [null, null] when there are none. */
    public static function dimensions(string $text): array
    {
        preg_match_all('/(\d+(?:\.\d+)?)\s*(["”″\'’′])/u', $text, $m, PREG_SET_ORDER);
        $vals = array_map(static fn($x) => in_array($x[2], ["'", '’', '′'], true) ? (float)$x[1] * 12 : (float)$x[1], $m);

        return [$vals[0] ?? null, $vals[1] ?? null];
    }
}

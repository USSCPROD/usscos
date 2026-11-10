<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;

/**
 * What to charge for shipping, before the order is saved.
 *
 * Asks FedEx what it would cost to send these goods to this address, works the boxes
 * out from the lines the same way the label screen does, and adds USSC's markup. The
 * customer sees one shipping and handling figure; the breakdown is for whoever is
 * typing the order.
 *
 * Freight is not quoted here. It comes from Keubix by hand and there is no API to ask,
 * so the markup is applied to whatever figure gets typed in.
 */
class ShippingQuoteService
{
    public function parcelMarkup(): float
    {
        return (float)(Config::get('shipping.parcel_markup') ?? 10.00);
    }

    public function freightMarkup(): float
    {
        return (float)(Config::get('shipping.freight_markup') ?? 20.00);
    }

    /**
     * @param array{line1?:string,city?:string,state?:string,zip?:string,residential?:bool} $to
     * @param array<int,array{product_id?:mixed,qty?:mixed}> $lines
     * @return array{ok:bool, message?:string, boxes?:int, weight?:float, rates?:array<int,array<string,mixed>>}
     */
    public function quote(array $to, array $lines): array
    {
        $zip = trim((string)($to['zip'] ?? ''));

        if ($zip === '') {
            return ['ok' => false, 'message' => 'Add the delivery ZIP code first — FedEx prices by where it is going.'];
        }

        // The same cartonizer the label screen uses, so the quote and the label are
        // describing the same parcels rather than two guesses.
        $forPacking = [];

        foreach ($lines as $l) {
            $qty = (float)($l['qty'] ?? 0);

            if ($qty > 0) {
                $forPacking[] = $l + ['qty' => $qty];
            }
        }

        if ($forPacking === []) {
            return ['ok' => false, 'message' => 'Add the items first — there is nothing to price.'];
        }

        $plan     = (new CartonizerService())->pack($forPacking);
        $packages = $plan['packages'] ?? [];

        if ($packages === []) {
            return ['ok' => false, 'message' => 'No packing information for these products yet, so the boxes cannot be worked out.'];
        }

        if (!empty($plan['freight'])) {
            return [
                'ok'      => false,
                'message' => 'This looks like freight, not parcel. Get the quote from Keubix and add $'
                           . number_format($this->freightMarkup(), 2) . '.',
            ];
        }

        $company = Database::selectOne("SELECT * FROM companies WHERE id = 1") ?: [];
        $fedex   = new FedExClient();

        if (!$fedex->configured()) {
            return ['ok' => false, 'message' => 'FedEx is not configured on this server.'];
        }

        $items  = [];
        $weight = 0.0;

        foreach ($packages as $pkg) {
            $lb = (float)($pkg['weight'] ?? 0);

            if ($lb <= 0) {
                return ['ok' => false, 'message' => 'One of these products has no weight recorded, so FedEx cannot price it.'];
            }

            $weight += $lb;
            $item = ['weight' => ['units' => 'LB', 'value' => round($lb, 2)]];

            if (($pkg['length'] ?? null) && ($pkg['width'] ?? null) && ($pkg['height'] ?? null)) {
                $item['dimensions'] = [
                    'length' => (int)round((float)$pkg['length']),
                    'width'  => (int)round((float)$pkg['width']),
                    'height' => (int)round((float)$pkg['height']),
                    'units'  => 'IN',
                ];
            }

            $items[] = $item;
        }

        $res = $fedex->call('POST', '/rate/v1/rates/quotes', [
            'accountNumber'     => ['value' => trim((string)($_ENV['FEDEX_ACCOUNT_NUMBER'] ?? ''))],
            'requestedShipment' => [
                // postal_code, not zip: that is what the companies table calls it, and
                // guessing the column name sent FedEx an empty origin.
                'shipper' => ['address' => [
                    'postalCode'          => (string)($company['postal_code'] ?? ''),
                    'stateOrProvinceCode' => (string)($company['state']       ?? ''),
                    'city'                => (string)($company['city']        ?? ''),
                    'countryCode'         => 'US',
                ]],
                'recipient' => ['address' => array_filter([
                    'postalCode'          => $zip,
                    'stateOrProvinceCode' => trim((string)($to['state'] ?? '')),
                    'city'                => trim((string)($to['city'] ?? '')),
                    'countryCode'         => 'US',
                    'residential'         => !empty($to['residential']),
                ], fn ($v) => $v !== '')],
                'pickupType'                => 'USE_SCHEDULED_PICKUP',
                'rateRequestType'           => ['ACCOUNT', 'LIST'],
                'requestedPackageLineItems' => $items,
            ],
        ]);

        if (!$res['ok']) {
            return ['ok' => false, 'message' => 'FedEx could not price it: ' . $fedex->errorOf($res)];
        }

        $wanted = (array)(Config::get('shipping.services') ?? []);
        $markup = $this->parcelMarkup();
        $rates  = [];

        foreach ($res['body']['output']['rateReplyDetails'] ?? [] as $d) {
            $type = (string)($d['serviceType'] ?? '');

            if ($wanted !== [] && !isset($wanted[$type])) {
                continue;
            }

            // The ACCOUNT rate, which is what USSC is actually billed. FedEx returns
            // both that and LIST, and taking the first one blindly meant quoting
            // whichever order they happened to arrive in — list price is well above
            // what a commercial account pays, so that is a charge nobody would win.
            //
            // In the sandbox the two are identical, because a test account has no
            // negotiated rates. That is why this was invisible here.
            $cost     = null;
            $rateType = null;

            foreach ($d['ratedShipmentDetails'] ?? [] as $rs) {
                $net = $rs['totalNetCharge'] ?? null;

                if ($net === null) {
                    continue;
                }

                if (($rs['rateType'] ?? '') === 'ACCOUNT') {
                    $cost     = (float)$net;
                    $rateType = 'ACCOUNT';
                    break;
                }

                if ($cost === null) {
                    $cost     = (float)$net;
                    $rateType = (string)($rs['rateType'] ?? 'UNKNOWN');
                }
            }

            if ($cost === null) {
                continue;
            }

            $rates[] = [
                'service'   => $type,
                'label'     => $wanted[$type] ?? (string)($d['serviceName'] ?? $type),
                'cost'      => round($cost, 2),
                'charge'    => round($cost + $markup, 2),
                'rate_type' => $rateType,
            ];
        }

        if ($rates === []) {
            return ['ok' => false, 'message' => 'FedEx returned no price for this shipment.'];
        }

        // Config order, not FedEx's: ground first, because it is nearly always the answer.
        $order = array_keys($wanted);
        usort($rates, fn ($a, $b) => array_search($a['service'], $order, true) <=> array_search($b['service'], $order, true));

        return [
            'ok'        => true,
            'boxes'     => count($items),
            'weight'    => round($weight, 2),
            'markup'    => $markup,
            'sandbox'   => $fedex->environment() === 'sandbox',
            'rate_type' => $rates[0]['rate_type'] ?? null,
            'rates'     => $rates,
        ];
    }
}

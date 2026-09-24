<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TaxRepository;

/**
 * Working out what tax to charge, and being able to say why.
 *
 * USSCOS raises the invoice now, so it is the calculator of record — the figure on the
 * customer's invoice is the one that has to be right and the one an auditor asks about.
 * Every decision this makes therefore comes back with a reason attached, and the reason
 * is stored on the invoice alongside the rate.
 *
 * The order of the checks matters. Each one is a reason not to charge, and the first that
 * applies wins:
 *
 *   1. A marketplace already collected it   — Amazon's money, not ours
 *   2. The customer is exempt               — certificate on file
 *   3. Nothing taxable on the order         — all lines non-taxable
 *   4. No nexus in the destination state    — having a rate is not owing tax
 *   5. No jurisdiction resolved             — better to charge nothing than guess
 */
class TaxService
{
    /** Channels where the marketplace collects and remits, so we must not. */
    private const MARKETPLACE_CHANNELS = ['amazon'];

    public function __construct(
        private TaxRepository $repo = new TaxRepository(),
    ) {
    }

    /**
     * Decide the rate for one order or invoice.
     *
     * @param array{state?:?string, zip?:?string} $shipTo   the DELIVERY address, never the billing one
     * @param array{tax_exempt?:mixed}            $customer
     *
     * @return array{
     *   rate:float, tax_rate_id:?int, source:string, reason:string,
     *   jurisdiction:?string, needs_review:bool
     * }
     */
    public function resolve(array $shipTo, array $customer = [], ?string $channel = null, ?string $onDate = null): array
    {
        $onDate = $onDate ?: date('Y-m-d');
        $none   = fn(string $source, string $reason) => [
            'rate'         => 0.0,
            'tax_rate_id'  => null,
            'source'       => $source,
            'reason'       => $reason,
            'jurisdiction' => null,
            'needs_review' => false,
        ];

        if ($channel !== null && in_array(strtolower($channel), self::MARKETPLACE_CHANNELS, true)) {
            return $none('marketplace', ucfirst(strtolower($channel)) . ' collected and remitted the tax');
        }

        if (!empty($customer['tax_exempt'])) {
            $cert = trim((string)($customer['resale_number'] ?? ''));

            return $none('none', $cert !== ''
                ? 'Customer is tax exempt — certificate ' . $cert
                : 'Customer is marked tax exempt');
        }

        $state = strtoupper(trim((string)($shipTo['state'] ?? '')));

        if ($state === '') {
            return $none('none', 'No delivery state on the order — tax cannot be determined');
        }

        if (!$this->repo->collectsIn($state)) {
            return $none('none', 'No nexus in ' . $state . ' — not registered to collect there');
        }

        // The ZIP first, because tax follows the delivery address down to the county.
        $j = $this->repo->byZip((string)($shipTo['zip'] ?? ''), $onDate);
        $fromZip = $j !== null;

        if ($j === null) {
            $j = $this->repo->stateDefault($state, $onDate);
        }

        if ($j === null) {
            // No tax rather than a guess, and said loudly. This shows on the invoice and
            // on the tax report, so it gets fixed instead of quietly under-collecting.
            $unresolved = $none('none',
                'Could not determine the tax jurisdiction for ' . $state . ' ' .
                ($shipTo['zip'] ?? '(no ZIP)') . ' — no tax charged. Map this ZIP before invoicing.');
            $unresolved['needs_review'] = true;

            return $unresolved;
        }

        $reason = $fromZip
            ? 'Delivery ZIP resolved to ' . $j['name']
            : 'No ZIP mapping — fell back to the ' . $state . ' default rate';

        if (!empty($j['is_ambiguous'])) {
            $reason .= '. That ZIP spans more than one jurisdiction — confirm the county';
        }

        if (!empty($j['needs_review'])) {
            $reason .= '. This rate is unverified';
        }

        return [
            'rate'         => (float)$j['rate'],
            'tax_rate_id'  => (int)$j['id'],
            'source'       => 'usscos',
            'reason'       => $reason,
            'jurisdiction' => (string)$j['name'],
            'needs_review' => !empty($j['needs_review']) || !empty($j['is_ambiguous']),
        ];
    }

    /**
     * The taxable base of a set of lines.
     *
     * Only lines marked taxable count. Discounts come off before tax, because the customer
     * is taxed on what they actually pay.
     *
     * @param list<array{qty:mixed, unit_price:mixed, discount_pct?:mixed, is_taxable?:mixed}> $lines
     */
    public function taxableBase(array $lines): float
    {
        $base = 0.0;

        foreach ($lines as $l) {
            if (empty($l['is_taxable'])) {
                continue;
            }

            $gross = (float)$l['qty'] * (float)$l['unit_price'];
            $base += $gross - ($gross * ((float)($l['discount_pct'] ?? 0) / 100));
        }

        return round($base, 2);
    }

    /** Tax on a base at a rate held as a fraction — 0.07 means 7%. */
    public function taxOn(float $base, float $rate): float
    {
        return round($base * $rate, 2);
    }

    public function liability(string $from, string $to): array
    {
        return $this->repo->liabilityByJurisdiction($from, $to);
    }

    public function marketplace(string $from, string $to): array
    {
        return $this->repo->marketplaceSummary($from, $to);
    }

    public function salesByState(string $from, string $to): array
    {
        return $this->repo->salesByState($from, $to);
    }

    public function unverifiedRates(): array
    {
        return $this->repo->unverifiedRates();
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;

/**
 * Creating FedEx labels, and getting the tracking number back without anybody typing it.
 *
 * This is the point of the whole exercise: the number is known the moment the label is
 * made, so it goes straight onto the invoice and out to the customer. Nobody reads it off
 * a screen and retypes it, and nobody has to remember to.
 *
 * The ship-from comes from the company record rather than being hardcoded — 1000 McFarland
 * 400 Blvd is a fact about the business, not about this integration, and it is already
 * stored once.
 *
 * FedEx failing never stops a shipment. The paint is on the truck either way, so a failed
 * label returns a message the bench can act on rather than an exception that rolls back an
 * order that physically went out.
 */
class FedExShippingService
{
    /** What FedEx calls the services USSC actually uses. */
    public const SERVICES = [
        'FEDEX_GROUND'        => 'FedEx Ground',
        'GROUND_HOME_DELIVERY'=> 'FedEx Home Delivery',
        'FEDEX_EXPRESS_SAVER' => 'FedEx Express Saver (3 day)',
        'FEDEX_2_DAY'         => 'FedEx 2Day',
        'STANDARD_OVERNIGHT'  => 'FedEx Standard Overnight',
        'PRIORITY_OVERNIGHT'  => 'FedEx Priority Overnight',
    ];

    public function __construct(
        private FedExClient $fedex = new FedExClient(),
        private ShipmentNotificationService $notify = new ShipmentNotificationService(),
    ) {
    }

    public function configured(): bool
    {
        return $this->fedex->configured() && $this->fedex->accountNumber() !== '';
    }

    public function isSandbox(): bool
    {
        return $this->fedex->environment() === 'sandbox';
    }

    /**
     * Create a label for an invoice and record what comes back.
     *
     * @param array{service?:string, weight?:float, packages?:int, reference?:string} $options
     * @return array{ok:bool, message:string, tracking?:list<string>, label_path?:?string}
     */
    public function createLabel(int $invoiceId, array $options = []): array
    {
        if (!$this->configured()) {
            return ['ok' => false, 'message' => 'FedEx is not configured on this server.'];
        }

        $invoice = Database::selectOne("
            SELECT i.*, c.company_name, c.phone AS customer_phone,
                   c.bill_address_1, c.bill_address_2, c.bill_city, c.bill_state, c.bill_zip
            FROM invoices i
            JOIN customers c ON c.id = i.customer_id
            WHERE i.id = ?
        ", [$invoiceId]);

        if ($invoice === false) {
            return ['ok' => false, 'message' => 'Invoice not found.'];
        }

        $company = Database::selectOne("SELECT * FROM companies WHERE id = 1");

        if ($company === false) {
            return ['ok' => false, 'message' => 'No company record to ship from.'];
        }

        // Ship-to falls back to the billing address, because an order collected at the
        // counter often carries no separate delivery address.
        $to = [
            'line1' => $invoice['ship_address_1'] ?: $invoice['bill_address_1'],
            'line2' => $invoice['ship_address_2'] ?: $invoice['bill_address_2'],
            'city'  => $invoice['ship_city']      ?: $invoice['bill_city'],
            'state' => $invoice['ship_state']     ?: $invoice['bill_state'],
            'zip'   => $invoice['ship_zip']       ?: $invoice['bill_zip'],
        ];

        foreach (['line1' => 'street address', 'city' => 'city', 'state' => 'state', 'zip' => 'ZIP'] as $k => $label) {
            if (trim((string)$to[$k]) === '') {
                return ['ok' => false, 'message' => 'No ' . $label . ' to ship to — fix the address on the invoice first.'];
            }
        }

        $weight   = (float)($options['weight'] ?? 0);
        $packages = max(1, (int)($options['packages'] ?? 1));
        $service  = (string)($options['service'] ?? 'FEDEX_GROUND');

        if ($weight <= 0) {
            return ['ok' => false, 'message' => 'Enter the package weight — FedEx will not make a label without one.'];
        }
        if (!isset(self::SERVICES[$service])) {
            return ['ok' => false, 'message' => 'Unknown FedEx service.'];
        }

        // One line item per package, each carrying its share of the weight. FedEx returns
        // a tracking number per piece, which is exactly why tracking is rows now.
        $perPackage = round($weight / $packages, 2);
        $lineItems  = [];

        for ($i = 0; $i < $packages; $i++) {
            $lineItems[] = [
                'weight'         => ['units' => 'LB', 'value' => max(0.1, $perPackage)],
                'customerReferences' => [[
                    'customerReferenceType' => 'CUSTOMER_REFERENCE',
                    'value' => substr((string)($options['reference'] ?? $invoice['invoice_number']), 0, 40),
                ]],
            ];
        }

        $payload = [
            'labelResponseOptions' => 'LABEL',
            'accountNumber'        => ['value' => $this->fedex->accountNumber()],
            'requestedShipment'    => [
                'shipper' => [
                    'contact' => [
                        'personName'  => (string)$company['name'],
                        'phoneNumber' => preg_replace('/\D/', '', (string)$company['phone']) ?: '0000000000',
                        'companyName' => (string)$company['name'],
                    ],
                    'address' => [
                        'streetLines'         => array_values(array_filter([
                            (string)$company['address_line1'],
                            (string)($company['address_line2'] ?? ''),
                        ])),
                        'city'                => (string)$company['city'],
                        'stateOrProvinceCode' => (string)$company['state'],
                        'postalCode'          => (string)$company['postal_code'],
                        'countryCode'         => (string)($company['country'] ?: 'US'),
                    ],
                ],
                'recipients' => [[
                    'contact' => [
                        'personName'  => (string)$invoice['company_name'],
                        'phoneNumber' => preg_replace('/\D/', '', (string)$invoice['customer_phone']) ?: '0000000000',
                        'companyName' => (string)$invoice['company_name'],
                    ],
                    'address' => [
                        'streetLines'         => array_values(array_filter([$to['line1'], $to['line2']])),
                        'city'                => (string)$to['city'],
                        'stateOrProvinceCode' => strtoupper(substr((string)$to['state'], 0, 2)),
                        'postalCode'          => (string)$to['zip'],
                        'countryCode'         => 'US',
                    ],
                ]],
                'shipDatestamp'   => date('Y-m-d'),
                'serviceType'     => $service,
                'packagingType'   => 'YOUR_PACKAGING',
                'pickupType'      => 'USE_SCHEDULED_PICKUP',
                'blockInsightVisibility' => false,
                'shippingChargesPayment' => [
                    'paymentType' => 'SENDER',
                    'payor'       => ['responsibleParty' => ['accountNumber' => ['value' => $this->fedex->accountNumber()]]],
                ],
                'labelSpecification' => [
                    'imageType'     => 'PDF',
                    'labelStockType'=> 'PAPER_4X6',
                ],
                'requestedPackageLineItems' => $lineItems,
            ],
        ];

        $res = $this->fedex->call('POST', '/ship/v1/shipments', $payload);

        if (!$res['ok']) {
            return ['ok' => false, 'message' => 'FedEx refused the label: ' . $this->fedex->errorOf($res)];
        }

        $shipment = $res['body']['output']['transactionShipments'][0] ?? null;

        if ($shipment === null) {
            return ['ok' => false, 'message' => 'FedEx accepted the request but returned no shipment.'];
        }

        $numbers   = [];
        $labelPath = null;

        foreach ($shipment['pieceResponses'] ?? [] as $piece) {
            $number = trim((string)($piece['trackingNumber'] ?? ''));

            if ($number !== '') {
                $numbers[] = $number;
            }

            foreach ($piece['packageDocuments'] ?? [] as $doc) {
                $saved = $this->storeLabel($invoiceId, $number, $doc);
                $labelPath ??= $saved;
            }
        }

        if ($numbers === []) {
            return ['ok' => false, 'message' => 'FedEx returned a shipment with no tracking number.'];
        }

        // Straight into the path that already exists: rows on the invoice and a link per
        // carrier. The customer is NOT told here — the invoice goes to the review queue,
        // and approving it there is what sends the tracking.
        $via = $this->notify->shipViaByName('FedEx');
        $this->notify->addTracking($invoiceId, implode(' ', $numbers), $via['id'] ?? null, 'parcel');

        Database::statement(
            "UPDATE invoices
             SET ship_via = COALESCE(NULLIF(ship_via, ''), 'FedEx'),
                 review_status = CASE WHEN review_status = 'approved' THEN review_status ELSE 'pending' END
             WHERE id = ?",
            [$invoiceId]
        );

        return [
            'ok'         => true,
            'tracking'   => $numbers,
            'label_path' => $labelPath,
            'message'    => sprintf(
                '%s label%s created%s. Queued for review before the customer is told.',
                count($numbers),
                count($numbers) === 1 ? '' : 's',
                $this->isSandbox() ? ' (sandbox — not valid for shipping)' : ''
            ),
        ];
    }

    /**
     * Save a label PDF.
     *
     * Under public/uploads like every other document, where nginx refuses to execute
     * anything, with a generated filename rather than anything FedEx chose.
     */
    private function storeLabel(int $invoiceId, string $tracking, array $doc): ?string
    {
        $encoded = (string)($doc['encodedLabel'] ?? '');

        if ($encoded === '') {
            return null;
        }

        $bytes = base64_decode($encoded, true);

        if ($bytes === false || $bytes === '') {
            return null;
        }

        $dir = BASE_PATH . '/public/uploads/labels/' . $invoiceId;

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return null;
        }

        $name = 'fedex-' . ($tracking !== '' ? preg_replace('/\W/', '', $tracking) : bin2hex(random_bytes(4))) . '.pdf';

        if (@file_put_contents($dir . '/' . $name, $bytes) === false) {
            return null;
        }

        @chmod($dir . '/' . $name, 0644);

        return '/uploads/labels/' . $invoiceId . '/' . $name;
    }

    /**
     * Check a delivery address against FedEx before anything is printed.
     *
     * Answers the shipping clerk's question — whether the sales team typed the address
     * correctly — at the point it is cheap to fix rather than after a truck has gone.
     *
     * NOT USABLE IN SANDBOX. FedEx answers every address check there with a canned
     * "Virtual Response" — it placed 1000 McFarland 400 Blvd in Chile — so this reports
     * that it proved nothing rather than returning a verdict nobody should believe.
     *
     * @return array{ok:bool, message:string, resolved?:array, classification?:string}
     */
    public function validateAddress(array $address): array
    {
        if (!$this->configured()) {
            return ['ok' => false, 'message' => 'FedEx is not configured on this server.'];
        }

        $res = $this->fedex->call('POST', '/address/v1/addresses/resolve', [
            'addressesToValidate' => [[
                'address' => [
                    'streetLines'         => array_values(array_filter([
                        (string)($address['line1'] ?? ''),
                        (string)($address['line2'] ?? ''),
                    ])),
                    'city'                => (string)($address['city'] ?? ''),
                    'stateOrProvinceCode' => strtoupper(substr((string)($address['state'] ?? ''), 0, 2)),
                    'postalCode'          => (string)($address['zip'] ?? ''),
                    'countryCode'         => 'US',
                ],
            ]],
        ]);

        if (!$res['ok']) {
            return ['ok' => false, 'message' => $this->fedex->errorOf($res)];
        }

        $resolved = $res['body']['output']['resolvedAddresses'][0] ?? null;

        if ($resolved === null) {
            return ['ok' => false, 'message' => 'FedEx could not resolve that address.'];
        }

        // The sandbox answers every address with a canned "Virtual Response" — it placed
        // 1000 McFarland 400 Blvd in Chile, sourced from Correos de Chile. Saying so is the
        // only honest option: a validation that always passes is worse than none, because
        // people would start trusting it.
        foreach ($res['body']['output']['alerts'] ?? [] as $alert) {
            if (($alert['code'] ?? '') === 'VIRTUAL.RESPONSE') {
                return [
                    'ok'             => false,
                    'resolved'       => $resolved,
                    'classification' => 'UNKNOWN',
                    'simulated'      => true,
                    'message'        => 'FedEx sandbox returns a canned answer for address checks — '
                                      . 'this proves nothing until the account is in production.',
                ];
            }
        }

        $attr = $resolved['attributes'] ?? [];
        $true = static fn($v): bool => $v === true || $v === 'true';

        // DPV — Delivery Point Validation — is the figure that matters for a US address:
        // it means the postal service believes mail is actually deliverable there, which is
        // a stronger claim than the address merely being well formed.
        $dpv        = $true($attr['DPV'] ?? $resolved['normalizedStatusNameDPV'] ?? false);
        $matched    = $true($attr['Matched'] ?? false);
        $wellFormed = $true($attr['ValidlyFormed'] ?? false);

        $class = strtolower((string)($resolved['classification'] ?? 'unknown'));

        return [
            'ok'             => $dpv || $matched,
            'resolved'       => $resolved,
            'classification' => (string)($resolved['classification'] ?? 'UNKNOWN'),
            'simulated'      => false,
            'message'        => match (true) {
                $dpv        => 'Address confirmed deliverable by FedEx' . ($class !== 'unknown' ? ' (' . $class . ')' : '') . '.',
                $matched    => 'Address matched, but not confirmed as a delivery point — worth a second look.',
                $wellFormed => 'Address looks well formed but FedEx cannot match it — check it before shipping.',
                default     => 'FedEx does not recognize that address — check it before shipping.',
            },
        ];
    }
}

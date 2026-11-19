<?php

declare(strict_types=1);

namespace App\Services\Web;

/**
 * Talking to Square, for the website checkout.
 *
 * Card details never touch USSCOS: the customer pays on Square's own hosted checkout page
 * (a payment link), exactly as on the stencil shop. USSCOS only creates the link and reads
 * back whether the order was paid.
 *
 * Settings, in .env:
 *   SQUARE_ENVIRONMENT            sandbox (default) or production
 *   SQUARE_ACCESS_TOKEN           the access token for that environment
 *   SQUARE_LOCATION_ID            the location orders go to (production: L656RANWFXGRC,
 *                                 US Specialty Coatings, shared with the stencil shop)
 *   SQUARE_WEBHOOK_SIGNATURE_KEY  from the webhook subscription, to prove a notification
 *                                 really came from Square
 *
 * The account is shared with StencilsPlus and the counter, so every order carries
 * source.name "USSC Products Website", which is what Square shows as its Channel.
 */
class SquareClient
{
    private const VERSION = '2025-01-23';
    public const SOURCE = 'USSC Products Website';

    public function environment(): string
    {
        return strtolower(trim((string)($_ENV['SQUARE_ENVIRONMENT'] ?? 'sandbox'))) === 'production' ? 'production' : 'sandbox';
    }

    public function configured(): bool
    {
        return $this->token() !== '' && $this->location() !== '';
    }

    public function location(): string
    {
        return trim((string)($_ENV['SQUARE_LOCATION_ID'] ?? ''));
    }

    private function token(): string
    {
        return trim((string)($_ENV['SQUARE_ACCESS_TOKEN'] ?? ''));
    }

    private function base(): string
    {
        return $this->environment() === 'production' ? 'https://connect.squareup.com' : 'https://connect.squareupsandbox.com';
    }

    /**
     * A hosted checkout for these lines plus shipping. Square asks for the shipping address.
     *
     * @param array<int,array{name:string,qty:int,unit:float,note?:string,meta?:array}> $lines
     * @return array{url:string, link_id:string, order_id:string}
     * @throws \RuntimeException with a message safe to show the customer
     */
    public function paymentLink(array $lines, array $shipping, string $zip, string $reference, string $redirect): array
    {
        $items = array_map(static fn($l) => array_filter([
            'name'             => mb_substr($l['name'], 0, 500),
            'quantity'         => (string)$l['qty'],
            'note'             => isset($l['note']) ? mb_substr($l['note'], 0, 500) : null,
            'base_price_money' => ['amount' => (int)round($l['unit'] * 100), 'currency' => 'USD'],
            'metadata'         => $l['meta'] ?? null,
        ], static fn($v) => $v !== null && $v !== ''), $lines);

        $res = $this->call('POST', '/v2/online-checkout/payment-links', [
            'idempotency_key' => $reference,
            'order' => [
                'location_id'  => $this->location(),
                'reference_id' => $reference,
                'line_items'   => $items,
                'source'       => ['name' => self::SOURCE],
                'metadata'     => ['checkout' => $reference, 'quoted_zip' => $zip, 'shipping_service' => $shipping['id']],
            ],
            'checkout_options' => [
                'redirect_url'             => $redirect,
                'ask_for_shipping_address' => true,
                'allow_tipping'            => false,
                'shipping_fee'             => [
                    'name'   => mb_substr('Shipping & handling, ' . $shipping['name'] . ' (to ' . $zip . ')', 0, 255),
                    'charge' => ['amount' => (int)round($shipping['price'] * 100), 'currency' => 'USD'],
                ],
            ],
            'pre_populated_data' => ['buyer_address' => ['postal_code' => $zip, 'country' => 'US']],
        ]);

        $link = $res['payment_link'] ?? null;

        if (!$link || empty($link['url'])) {
            throw new \RuntimeException('Checkout is temporarily unavailable. Please try again or call us to order.');
        }

        return ['url' => $link['url'], 'link_id' => (string)$link['id'], 'order_id' => (string)($link['order_id'] ?? '')];
    }

    /** @throws \RuntimeException */
    public function order(string $orderId): array
    {
        return $this->call('GET', '/v2/orders/' . rawurlencode($orderId))['order'] ?? [];
    }

    /** @throws \RuntimeException */
    public function payment(string $paymentId): array
    {
        return $this->call('GET', '/v2/payments/' . rawurlencode($paymentId))['payment'] ?? [];
    }

    /**
     * Is this order paid, in full, with the money taken - not merely authorised?
     * (docs/FULFILLMENT_AND_PAYMENT_DECISIONS.md: authorisation is not payment.)
     *
     * @return array{paid:bool, payment_id:string, amount:float, reason:string}
     */
    public function paidState(array $order): array
    {
        $tenders = $order['tenders'] ?? [];
        $due     = (int)($order['net_amount_due_money']['amount'] ?? -1);
        $total   = (int)($order['total_money']['amount'] ?? 0);

        if ($tenders === []) {
            return ['paid' => false, 'payment_id' => '', 'amount' => 0.0, 'reason' => 'no payment yet'];
        }

        foreach ($tenders as $t) {
            if (($t['type'] ?? '') === 'CARD' && ($t['card_details']['status'] ?? '') !== 'CAPTURED') {
                return ['paid' => false, 'payment_id' => '', 'amount' => 0.0, 'reason' => 'card not captured'];
            }
        }

        if ($due !== 0) {
            return ['paid' => false, 'payment_id' => '', 'amount' => 0.0, 'reason' => 'amount still due'];
        }

        return ['paid' => true, 'payment_id' => (string)($tenders[0]['payment_id'] ?? $tenders[0]['id'] ?? ''),
                'amount' => $total / 100, 'reason' => ''];
    }

    /**
     * Did Square sign this notification? HMAC-SHA256 of the notification URL followed by the
     * raw body, with the subscription's signature key, base64: Square's documented scheme.
     */
    public function verifyWebhook(string $body, string $signature, string $notificationUrl): bool
    {
        $key = trim((string)($_ENV['SQUARE_WEBHOOK_SIGNATURE_KEY'] ?? ''));

        if ($key === '' || $signature === '') {
            return false;
        }

        return hash_equals(base64_encode(hash_hmac('sha256', $notificationUrl . $body, $key, true)), $signature);
    }

    /** @throws \RuntimeException */
    private function call(string $method, string $path, ?array $body = null): array
    {
        $ch = curl_init($this->base() . $path);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $this->token(), 'Square-Version: ' . self::VERSION,
                                       'Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_POSTFIELDS     => $body !== null ? json_encode($body) : null,
        ]);

        $raw  = (string)curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        $json = json_decode($raw, true) ?: [];

        if ($code < 200 || $code >= 300) {
            error_log('Square ' . $method . ' ' . $path . ' failed: ' . $code . ' ' . substr($raw, 0, 600));
            throw new \RuntimeException('Checkout is temporarily unavailable. Please try again or call us to order.');
        }

        return $json;
    }
}

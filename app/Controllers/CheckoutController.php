<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\WebCheckoutRepository;
use App\Services\Stencils\DesignerError;
use App\Services\Web\CartService;
use App\Services\Web\SquareClient;
use App\Services\Web\WebOrderService;

/**
 * The website cart and checkout. Public: no authentication.
 *
 * The cart itself lives in the customer's browser (public/assets/js/cart.js), as it did on
 * the stencil shop. Everything that costs money is decided here: prices, shipping and the
 * total handed to Square. See App\Services\Web\WebOrderService for how a paid checkout
 * becomes a sales order.
 */
class CheckoutController extends Controller
{
    public function cart(Request $request, Response $response): Response
    {
        return $this->view('site.cart', [
            'title'   => 'Your Cart',
            'nav'     => (new SiteController())->navForDesigner(),
            'enabled' => (new WebOrderService())->enabled(),
            'sandbox' => (new SquareClient())->environment() === 'sandbox',
        ]);
    }

    /** The cart, priced. {items: [...]} → {lines, subtotal}. */
    public function price(Request $request, Response $response): Response
    {
        try {
            $priced = (new CartService())->price($this->body()['items'] ?? []);
        } catch (DesignerError $e) {
            return $response->json(['error' => $e->getMessage()], 422);
        }

        return $response->json(['lines' => $priced['lines'], 'subtotal' => $priced['subtotal']]);
    }

    /** {items, zip, residential} → {zip, options: [{id, name, price}]} */
    public function shipping(Request $request, Response $response): Response
    {
        $in = $this->body();

        try {
            return $response->json((new WebOrderService())->shipping($in['items'] ?? [], (string)($in['zip'] ?? ''), !empty($in['residential'])));
        } catch (DesignerError $e) {
            return $response->json(['error' => $e->getMessage()], 422);
        }
    }

    /** {items, zip, residential, service} → {url}: Square's hosted checkout. */
    public function start(Request $request, Response $response): Response
    {
        $in = $this->body();

        try {
            $out = (new WebOrderService())->start($in['items'] ?? [], (string)($in['zip'] ?? ''), !empty($in['residential']),
                                                  (string)($in['service'] ?? ''), 'https://' . $request->host());
        } catch (DesignerError $e) {
            return $response->json(['error' => $e->getMessage()], 422);
        }

        return $response->json(['url' => $out['url']]);
    }

    /**
     * Where Square sends the customer back. Checks with Square, and makes the order if it
     * is paid; the webhook and the reconcile script cover anyone who never comes back.
     */
    public function thanks(Request $request, Response $response): Response
    {
        $checkout = (new WebCheckoutRepository())->findByToken((string)$request->raw('c', ''));
        $result   = $checkout !== false ? (new WebOrderService())->complete($checkout) : ['state' => 'missing'];

        return $this->view('site.checkout_thanks', [
            'title'  => 'Thank you',
            'nav'    => (new SiteController())->navForDesigner(),
            'state'  => $result['state'],
            'number' => $result['so_number'] ?? null,
            'total'  => $checkout !== false ? (float)$checkout['total'] : 0,
        ]);
    }

    /**
     * Square's notifications. Signed: anything that does not verify is refused, so a
     * stranger cannot announce a payment. A good one only prompts USSCOS to ask Square
     * itself about the order - the notification's own contents are never trusted.
     */
    public function webhook(Request $request, Response $response): Response
    {
        $body = (string)file_get_contents('php://input');
        $url  = 'https://' . $request->host() . '/webhook/square';

        if (!(new SquareClient())->verifyWebhook($body, (string)($_SERVER['HTTP_X_SQUARE_HMACSHA256_SIGNATURE'] ?? ''), $url)) {
            error_log('Square webhook: signature did not verify');
            return $response->json(['error' => 'Unauthorized'], 401);
        }

        $event   = json_decode($body, true) ?: [];
        $object  = $event['data']['object'] ?? [];
        $orderId = (string)($object['payment']['order_id'] ?? $object['order_updated']['order_id'] ?? $object['order']['id'] ?? '');

        if ($orderId !== '') {
            $checkout = (new WebCheckoutRepository())->findBySquareOrder($orderId);

            if ($checkout !== false) {
                (new WebOrderService())->complete($checkout);
            }
        }

        // 200 whatever happened: Square retries anything else, and an order that is not
        // ours (the shared account's counter sales) is simply not our business.
        return $response->json(['ok' => true]);
    }

    /** The JSON request body. Not json(): the base Controller has a json() of its own. */
    private function body(): array
    {
        $raw = (string)file_get_contents('php://input');

        return strlen($raw) > 200_000 ? [] : (json_decode($raw, true) ?: []);
    }
}

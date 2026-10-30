<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\FulfillmentRepository;

final class FulfillmentService
{
    public function __construct(
        private FulfillmentRepository $repo,
        private \Closure $makeInvoice,
        private \Closure $moveStock,
        private \Closure $postPayment,
    ) {
    }

    private function transaction(int $orderId, callable $work): mixed
    {
        $pdo = $this->repo->pdo;
        $pdo->beginTransaction();
        try {
            $order = $this->repo->order($orderId, true);
            if (!$order || $order['status'] === 'cancelled') {
                throw new \DomainException('The order is missing or closed.');
            }
            $result = $work($order);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function plan(int $orderId, array $data, int $userId): int
    {
        return $this->transaction($orderId, function (array $order) use ($orderId, $data, $userId): int {
            if ($order['status'] === 'invoiced') {
                throw new \DomainException('This order is fully fulfilled and invoiced.');
            }
            $method = $data['method'] ?? '';
            $key = $data['request_key'] ?? '';
            if (!isset(FulfillmentPolicy::METHODS[$method]) || !preg_match('/^[a-f0-9]{32}$/D', $key)) {
                throw new \DomainException('Select a fulfillment method and reload the form.');
            }
            $existing = $this->repo->byKey($key);
            if ($existing) {
                if ((int)$existing['sales_order_id'] !== $orderId) {
                    throw new \DomainException('Invalid request key.');
                }
                return (int)$existing['id'];
            }
            $policy = $order['billing_policy'] ?? ($data['billing_policy'] ?? '');
            $termId = (int)($order['fulfillment_term_id'] ?? $order['customer_term_id'] ?? 0);
            if (!in_array($policy, ['cod','terms'], true) || !$this->repo->term($termId)) {
                throw new \DomainException('Confirm COD or terms and set an active payment term on the customer first.');
            }
            $date = trim((string)($data['scheduled_date'] ?? ''));
            if ($date !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date) || date('Y-m-d', strtotime($date)) !== $date)) {
                throw new \DomainException('Enter a valid scheduled date.');
            }
            if ($method === 'delivery' && trim((string)($data['driver'] ?? '')) === '') {
                throw new \DomainException('Assign the USSC delivery driver.');
            }
            if (strlen((string)($data['driver'] ?? '')) > 150 || strlen((string)($data['notes'] ?? '')) > 1000) {
                throw new \DomainException('Driver or instructions exceed the allowed length.');
            }
            if (empty($order['billing_policy'])) {
                $this->repo->adopt($orderId, $policy, $termId, $userId);
            }
            $lines = array_column($this->repo->lines($orderId), null, 'id');
            $selected = [];
            foreach ((array)($data['qty'] ?? []) as $lineId => $raw) {
                if ($raw === '' || (is_numeric($raw) && (float)$raw === 0.0)) {
                    continue;
                }
                $qty = FulfillmentPolicy::quantity($raw);
                $line = $lines[$lineId] ?? null;
                if (!$line || $qty > round((float)$line['qty_ordered'] - (float)$line['allocated'], 4)) {
                    throw new \DomainException('A selected quantity exceeds the unallocated quantity or belongs to another order.');
                }
                $selected[(int)$lineId] = $qty;
            }
            if (!$selected) {
                throw new \DomainException('Select at least one item quantity.');
            }
            $id = $this->repo->create($orderId, [
                'method' => $method,'request_key' => $key,'scheduled_date' => $date,
                'driver' => trim((string)($data['driver'] ?? '')),'notes' => trim((string)($data['notes'] ?? '')),
            ], $userId);
            foreach ($selected as $lineId => $qty) {
                $this->repo->addLine($id, $lineId, $qty);
            }
            $this->repo->event($id, 'planned', 'Billing policy: '.$policy, $userId);
            return $id;
        });
    }

    public function act(int $orderId, int $id, string $action, array $data, int $userId): void
    {
        $this->transaction($orderId, function (array $order) use ($orderId, $id, $action, $data, $userId): void {
            $f = $this->repo->find($id);
            if (!$f || (int)$f['sales_order_id'] !== $orderId) {
                throw new \DomainException('Fulfillment not found for this order.');
            }
            // Repeated completion must not repeat stock or shipment quantities.
            if ($action === 'complete' && $f['status'] === 'completed') {
                return;
            }
            $invoice = empty($f['invoice_id']) ? [] : $this->repo->invoice((int)$f['invoice_id']);
            $receipt = $this->repo->checkReceipt($id);
            $checkCovers = $receipt && round((float)$receipt['amount'], 2) >= round((float)($invoice['balance_due'] ?? 0), 2);
            FulfillmentPolicy::assertTransition($f, $action, $order['billing_policy'], $invoice, (bool)$checkCovers);
            $recipient = trim((string)($data['recipient'] ?? $f['recipient'] ?? ''));
            $tracking = trim((string)($data['tracking'] ?? $f['tracking'] ?? ''));
            $note = trim((string)($data['note'] ?? ''));
            if ($action === 'dispatch' && trim((string)$f['driver']) === '') {
                throw new \DomainException('Assign a driver before dispatch.');
            }
            if ($action === 'complete' && $f['method'] !== 'carrier' && $recipient === '') {
                throw new \DomainException('Record who received the goods.');
            }
            if ($action === 'complete' && $f['method'] === 'carrier' && $tracking === '') {
                throw new \DomainException('Record the carrier tracking or PRO number.');
            }
            if (in_array($action, ['failed','cancel'], true) && $note === '') {
                throw new \DomainException('Record a reason.');
            }
            $items = $this->repo->items($id);
            if ($action === 'ready') {
                $used = [];
                foreach ($this->repo->all($orderId) as $other) {
                    if (!in_array($other['status'], ['ready','out_for_delivery','completed'], true)) {
                        continue;
                    }
                    foreach ($this->repo->items((int)$other['id']) as $li) {
                        $used[$li['id']] = ($used[$li['id']] ?? 0) + (float)$li['qty'];
                    }
                }
                foreach ($items as $li) {
                    if (round(($used[$li['id']] ?? 0) + (float)$li['qty'], 4) > round((float)$li['qty_picked'], 4)) {
                        throw new \DomainException('Record picked quantities before marking these goods ready.');
                    }
                }
            }
            $status = match($action) {
                'ready','failed' => 'ready','dispatch' => 'out_for_delivery','complete' => 'completed','cancel' => 'cancelled'
            };
            if ($action === 'complete') {
                ($this->moveStock)($items, ['type' => 'sale','reference_type' => 'fulfillment','reference_id' => $id,
                    'reference_num' => $order['so_number'],'user_id' => $userId,'date' => date('Y-m-d')]);
                foreach ($items as $li) {
                    $this->repo->incrementCompleted((int)$li['id'], (float)$li['qty']);
                }
                $this->repo->setOrderStatus($orderId, $this->repo->remaining($orderId) > 0.00005 ? 'partially_shipped' : 'shipped');
            }
            $this->repo->transition($id, $status, ['recipient' => $recipient,'tracking' => $tracking]);
            $this->repo->event($id, $action, $note, $userId);
            if ($action === 'complete') {
                if ($f['invoice_id']) {
                    $this->repo->stampInvoiceCompletion((int)$f['invoice_id']);
                }
                $this->refreshOrderStatus($orderId);
            }
        });
    }

    public function invoice(int $orderId, ?int $fulfillmentId, string $exception, int $userId): int
    {
        return $this->transaction($orderId, function (array $order) use ($orderId, $fulfillmentId, $exception, $userId): int {
            if (empty($order['billing_policy'])) {
                throw new \DomainException('Plan the fulfillment first.');
            }
            $all = $this->repo->all($orderId);
            if ($order['billing_policy'] === 'cod') {
                $selected = array_values(array_filter($all, fn ($f) => (int)$f['id'] === $fulfillmentId));
                if (count($selected) !== 1 || !in_array($selected[0]['status'], ['ready','out_for_delivery','completed'], true)) {
                    throw new \DomainException('Select one prepared COD fulfillment.');
                }
                if ($selected[0]['invoice_id']) {
                    return (int)$selected[0]['invoice_id'];
                }
            } else {
                if ($this->repo->remaining($orderId) > 0.00005 && trim($exception) === '') {
                    throw new \DomainException('Terms orders wait for full fulfillment. An authorized partial-billing exception needs a reason.');
                }
                $selected = array_values(array_filter($all, fn ($f) => $f['status'] === 'completed' && !$f['invoice_id']));
                if (!$selected) {
                    throw new \DomainException('No completed, unbilled fulfillments.');
                }
            }
            $lines = [];
            foreach ($selected as $f) {
                foreach ($this->repo->items((int)$f['id']) as $li) {
                    $lines[] = ['product_id' => $li['product_id'],'quickbooks_item' => $li['quickbooks_item'],
                        'description' => $li['description'],'qty' => (float)$li['qty'],'uom_id' => $li['uom_id'],
                        'unit_price' => (float)$li['unit_price'],'discount_pct' => (float)$li['discount_pct'],
                        'is_taxable' => (int)$li['taxable'],
                        'line_total' => round((float)$li['qty'] * (float)$li['unit_price'] * (1 - (float)$li['discount_pct'] / 100), 2)];
                }
            }
            $term = $this->repo->term((int)$order['fulfillment_term_id']);
            if (!$term) {
                throw new \DomainException('The payment term must be active.');
            }
            $order['fulfillment_methods'] = implode(', ', array_unique(array_map(fn ($f) => FulfillmentPolicy::METHODS[$f['method']], $selected)));
            $dates = array_filter(array_column($selected, 'completed_at'));
            $order['fulfillment_ship_date'] = count($dates) === count($selected) ? substr(max($dates), 0, 10) : null;
            $invoiceId = ($this->makeInvoice)($order, $term, $lines, $userId);
            foreach ($selected as $f) {
                $this->repo->linkInvoice((int)$f['id'], $invoiceId);
                $this->repo->event((int)$f['id'], 'invoiced', 'Invoice '.$invoiceId.($exception !== '' ? '; exception: '.$exception : ''), $userId);
            }
            $this->refreshOrderStatus($orderId);
            return $invoiceId;
        });
    }

    private function refreshOrderStatus(int $orderId): void
    {
        if ($this->repo->remaining($orderId) > 0.00005) {
            return;
        }
        $unbilled = array_filter($this->repo->all($orderId), fn ($f) => $f['status'] !== 'cancelled' && !$f['invoice_id']);
        $this->repo->setOrderStatus($orderId, $unbilled ? 'shipped' : 'invoiced');
    }

    public function receiveCheck(int $orderId, int $id, string $number, mixed $amount, int $userId): void
    {
        $this->transaction($orderId, function (array $order) use ($orderId, $id, $number, $amount, $userId): void {
            $f = $this->repo->find($id);
            if (!$f || (int)$f['sales_order_id'] !== $orderId || $f['method'] !== 'delivery'
                || $f['status'] !== 'out_for_delivery' || !$f['invoice_id']) {
                throw new \DomainException('Check collection requires an invoiced USSC delivery out for delivery.');
            }
            $invoice = $this->repo->invoice((int)$f['invoice_id']);
            $number = trim($number);
            $value = FulfillmentPolicy::quantity($amount);
            if ($number === '' || strlen($number) > 100 || abs($value - round($value, 2)) > 0.000001
                || round($value, 2) !== round((float)$invoice['balance_due'], 2) || $invoice['review_status'] !== 'approved' || $invoice['status'] === 'void') {
                throw new \DomainException('Record a check number and the exact outstanding balance on the approved invoice.');
            }
            $existing = $this->repo->checkReceipt($id);
            if ($existing) {
                if ($existing['check_number'] !== $number || round((float)$existing['amount'], 2) !== round($value, 2)) {
                    throw new \DomainException('A different check receipt already exists. Ask accounting to reconcile it.');
                }
                return;
            }
            $this->repo->receiveCheck($id, (int)$f['invoice_id'], $number, $value, $userId);
            $this->repo->event($id, 'check_received', 'Check '.$number.' received, not bank cleared.', $userId);
        });
    }

    public function handCheckToAccounting(int $orderId, int $id, int $userId): void
    {
        $this->transaction($orderId, function (array $order) use ($orderId, $id, $userId): void {
            $f = $this->repo->find($id);
            if (!$f || (int)$f['sales_order_id'] !== $orderId) {
                throw new \DomainException('Fulfillment not found.');
            }
            $receipt = $this->repo->checkReceipt($id);
            if (!$receipt) {
                throw new \DomainException('No check receipt.');
            }
            if ($receipt['posted_payment_id']) {
                return;
            }
            $invoice = $this->repo->invoice((int)$receipt['invoice_id']);
            if ($invoice['status'] === 'void' || round((float)$invoice['balance_due'], 2) !== round((float)$receipt['amount'], 2)) {
                throw new \DomainException('The invoice balance changed. Reconcile the check before posting it.');
            }
            $paymentId = ($this->postPayment)($order,$receipt,$userId);
            $this->repo->markCheckPosted((int)$receipt['id'],$paymentId);
            $this->repo->event($id,'check_handed_to_accounting','Payment recorded. Bank clearance is not asserted.',$userId);
        });
    }
}

<?php

declare(strict_types=1);

use App\Services\FulfillmentPolicy;

ob_start();
?>
<h1><?= e($title) ?></h1>
<p><a href="/shipping">Shipping</a> | <a href="/shipping/fulfillments">All fulfillments</a> | <a href="/shipping/pull">Pull list</a></p>
<p>Ready is not completed. Mark the actual handoff only after the goods leave with the customer or carrier, or the USSC delivery reaches the recipient. Invoice approval does not confirm payment.</p>
<?php if (!$order): ?>
<p><?php foreach (FulfillmentPolicy::METHODS as $key => $label): ?>
<a class="btn btn--secondary" href="?method=<?= e($key) ?>"><?= e($label) ?></a>
<?php endforeach; ?></p>
<table style="width:100%"><thead><tr><th>Order</th><th>Customer</th><th>Method</th><th>Status</th><th>Scheduled</th><th>Billing</th></tr></thead><tbody>
<?php foreach ($rows as $row): ?>
<tr><td><a href="/shipping/fulfillments/order/<?= (int)$row['sales_order_id'] ?>">SO <?= e($row['so_number']) ?></a></td><td><?= e($row['company_name']) ?></td><td><?= e(FulfillmentPolicy::METHODS[$row['method']]) ?></td><td><?= e(str_replace('_', ' ', $row['status'])) ?></td><td><?= e($row['scheduled_date'] ?? '') ?></td><td><?= $row['invoice_id'] ? 'Invoice prepared' : ($row['billing_policy'] === 'terms' && $row['status'] === 'completed' ? ((float)$row['remaining'] > 0 ? 'Waiting for order completion' : 'Ready for invoice review') : 'Not invoiced') ?></td></tr>
<?php endforeach; ?></tbody></table>
<?php if ($checks): ?>
<h2>Driver checks awaiting accounting handoff</h2>
<p>These checks were received by a driver. They have not been posted by this handoff process and are not marked bank cleared.</p>
<?php foreach ($checks as $check): ?>
<p>Invoice <?= e($check['invoice_number']) ?>, <?= e($check['company_name']) ?>, check <?= e($check['check_number']) ?>, <?= money($check['amount']) ?>.
<a href="/invoices/<?= (int)$check['invoice_id'] ?>">Review invoice</a></p>
<?php endforeach; ?>
<?php endif; ?>
<p>To plan a fulfillment, open its sales order and choose Shipping &amp; Fulfillment.</p>
<?php else: ?>
<p><a href="/sales-orders/<?= (int)$order['id'] ?>">SO <?= e($order['so_number']) ?></a>, <?= e($order['company_name']) ?>. Billing: <?= e($order['billing_policy'] ?? 'not confirmed') ?>.</p>
<p>For terms orders, completed portions wait for the remaining goods. Billing creates the invoice after completion, or records a reason for a partial-billing exception.</p>
<table style="width:100%"><thead><tr><th>Item</th><th>Ordered</th><th>Fulfilled</th><th>Outstanding</th><th>Unallocated</th></tr></thead><tbody>
<?php foreach ($lines as $line): ?>
<tr><td><?= e($line['description'] ?? $line['quickbooks_item']) ?></td><td><?= e($line['qty_ordered']) ?></td><td><?= e($line['qty_shipped']) ?></td><td><?= e((float)$line['qty_ordered'] - (float)$line['qty_shipped']) ?></td><td><?= e((float)$line['qty_ordered'] - (float)$line['allocated']) ?></td></tr>
<?php endforeach; ?></tbody></table>
<h2>Plan a pickup, delivery, or shipment</h2>
<p>After the first allocation, order lines are protected from replacement. Existing orders with invoices, payments, or shipment history must be reconciled before using this workflow.</p>
<form method="post">
<?= csrf_field() ?><input type="hidden" name="action" value="plan"><input type="hidden" name="request_key" value="<?= e(bin2hex(random_bytes(16))) ?>">
<label>Method <select name="method"><?php foreach (FulfillmentPolicy::METHODS as $key => $label): ?><option value="<?= e($key) ?>"><?= e($label) ?></option><?php endforeach; ?></select></label>
<?php if (empty($order['billing_policy'])): ?><label>Billing policy <select name="billing_policy" required><option value="">Confirm with customer terms</option><option value="cod">COD, each portion</option><option value="terms">Terms, after order completion</option></select></label><?php endif; ?>
<label>Scheduled date <input type="date" name="scheduled_date"></label>
<label>Driver, required for USSC delivery <input name="driver" maxlength="150"></label>
<label>Instructions <input name="notes" maxlength="1000"></label>
<?php foreach ($lines as $line): $available = max(0, (float)$line['qty_ordered'] - (float)$line['allocated']);
    if ($available <= 0) {
        continue;
    } ?>
<p><label><?= e($line['description'] ?? $line['quickbooks_item']) ?> <input type="number" name="qty[<?= (int)$line['id'] ?>]" min="0" max="<?= e($available) ?>" step="0.0001" value="0"></label></p>
<?php endforeach; ?><button class="btn btn--primary">Save fulfillment plan</button></form>
<?php if ($billing && ($order['billing_policy'] ?? '') === 'terms'): ?>
<h2>Terms billing</h2><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="invoice">
<label>Partial-billing exception reason, only if the order is incomplete <input name="exception" maxlength="1000"></label>
<button class="btn btn--primary">Create invoice for completed, unbilled portions</button></form>
<?php endif; ?>
<?php foreach ($rows as $row): ?>
<div class="card" style="padding:1rem;margin-top:1rem">
<h2>#<?= (int)$row['id'] ?> <?= e(FulfillmentPolicy::METHODS[$row['method']]) ?>, <?= e(str_replace('_', ' ', $row['status'])) ?></h2>
<p>Driver: <?= e($row['driver'] ?? 'Not assigned') ?>. Scheduled: <?= e($row['scheduled_date'] ?? '') ?>. <?= e($row['notes'] ?? '') ?></p>
<ul><?php foreach ($row['items'] as $item): ?><li><?= e($item['qty']) ?> x <?= e($item['description'] ?? $item['quickbooks_item']) ?></li><?php endforeach; ?></ul>
<?php if ($row['invoice_id']): ?><p><a href="/invoices/<?= (int)$row['invoice_id'] ?>">Open invoice</a>. Review and send the invoice from its invoice screen. Record payments through Receive Payment, except driver checks handled below.</p><?php endif; ?>
<?php if (!in_array($row['status'], ['completed','cancelled'], true)): ?>
<form method="post"><?= csrf_field() ?><input type="hidden" name="fulfillment_id" value="<?= (int)$row['id'] ?>">
<label>Recipient <input name="recipient" maxlength="150" value="<?= e($row['recipient'] ?? '') ?>"></label>
<?php if ($row['method'] === 'carrier'): ?><label>Tracking or PRO number(s) <input name="tracking" value="<?= e($row['tracking'] ?? '') ?>"></label><p>Use the carrier's label system and record the tracking here. The existing invoice-based label action is not used for these fulfillments.</p><?php endif; ?>
<label>Reason for a failed delivery or cancellation <input name="note" maxlength="1000"></label>
<?php if ($row['status'] === 'planned'): ?><button name="action" value="ready">Mark ready</button><?php endif; ?>
<?php if ($row['status'] === 'ready' && $row['method'] === 'delivery'): ?><button name="action" value="dispatch">Mark out for delivery</button><?php endif; ?>
<?php if (($row['status'] === 'ready' && $row['method'] !== 'delivery') || $row['status'] === 'out_for_delivery'): ?><button name="action" value="complete"><?= $row['method'] === 'pickup' ? 'Confirm picked up' : ($row['method'] === 'delivery' ? 'Confirm delivered' : 'Confirm handed to carrier') ?></button><?php endif; ?>
<?php if ($row['status'] === 'out_for_delivery'): ?><button name="action" value="failed">Delivery failed, return to ready</button><?php endif; ?>
<?php if (in_array($row['status'], ['planned','ready'], true) && !$row['invoice_id']): ?><button name="action" value="cancel">Cancel this allocation</button><?php endif; ?>
<?php if ($billing && $order['billing_policy'] === 'cod' && !$row['invoice_id'] && $row['status'] === 'ready'): ?><button name="action" value="invoice">Prepare COD invoice</button><?php endif; ?>
</form><?php endif; ?>
<?php if ($row['method'] === 'delivery' && $row['status'] === 'out_for_delivery' && $row['invoice_id'] && !$row['check']): ?>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="receive_check"><input type="hidden" name="fulfillment_id" value="<?= (int)$row['id'] ?>">
<label>Check number <input name="check_number" required maxlength="100"></label><label>Amount received <input type="number" name="amount" min="0.01" step="0.01" required></label>
<button>Record check physically received</button><p>Record this only when the driver has the check. This does not assert bank clearance.</p></form><?php endif; ?>
<?php if ($row['check']): ?><p>Check <?= e($row['check']['check_number']) ?>, <?= money($row['check']['amount']) ?>, collected <?= e($row['check']['collected_at']) ?>. <?= $row['check']['posted_payment_id'] ? 'Posted by accounting. Bank clearance not asserted.' : 'Awaiting handoff to accounting.' ?></p>
<?php if ($billing && !$row['check']['posted_payment_id']): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="post_check"><input type="hidden" name="fulfillment_id" value="<?= (int)$row['id'] ?>"><button>Confirm accounting received check and apply payment</button></form><?php endif; ?>
<?php endif; ?>
</div><?php endforeach; ?>
<?php endif; ?>
<?php $content = ob_get_clean();
include BASE_PATH.'/app/Views/layouts/app.php'; ?>

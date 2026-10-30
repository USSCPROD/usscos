<?php ob_start(); ?>
<?php
$th = 'padding:.5rem .8rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;'
    . 'color:#6b7280;background:#f8f9fb;border-bottom:1px solid #e5e7eb;text-align:left';
$td = 'padding:.7rem .8rem;font-size:.9rem;vertical-align:top';

$waiting = array_sum(array_map(fn($i) => (float)$i['total_amount'], $invoices));
?>

<div class="page-header">
    <div class="page-header__left">
        <div style="font-size:.85rem;color:var(--color-text-muted);margin-bottom:.25rem">
            <a href="/invoices" style="color:inherit">Invoices</a> &rsaquo; Awaiting Review
        </div>
        <h1 class="page-title" style="margin:0">Awaiting Review</h1>
        <p class="page-subtitle" style="margin:.25rem 0 0">
            Review the invoice before customer communication. New fulfillment invoices may be COD bills prepared before handoff. Approval does not confirm payment.
        </p>
    </div>
</div>

<?php if (empty($invoices)): ?>
    <div class="card" style="padding:3rem;text-align:center">
        <div style="font-size:1.05rem;color:#111;margin-bottom:.4rem">Nothing waiting</div>
        <p class="text-muted" style="margin:0;font-size:.9rem">
            No invoices are currently awaiting review. Check individual email delivery status separately.
        </p>
    </div>
<?php else: ?>
    <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:.85rem 1.1rem;margin-bottom:1.25rem;font-size:.88rem;color:#92400e">
        <strong><?= count($invoices) ?> invoice<?= count($invoices) === 1 ? '' : 's' ?></strong>
        worth <?= money($waiting) ?> awaiting review.
        For new fulfillment invoices, approve first, then use the invoice Email action.
    </div>

    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden">
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr>
                    <th style="<?= $th ?>">Invoice</th>
                    <th style="<?= $th ?>">Customer</th>
                    <th style="<?= $th ?>">Fulfillment date</th>
                    <th style="<?= $th ?>">Tracking</th>
                    <th style="<?= $th ?>;text-align:right">Total</th>
                    <th style="<?= $th ?>;text-align:right"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($invoices as $i): ?>
                <?php
                $noEmail = trim((string)($i['customer_email'] ?? '')) === '';
                $noTrack = (int)$i['tracking_count'] === 0;
                ?>
                <tr style="border-bottom:1px solid #f3f4f6">
                    <td style="<?= $td ?>">
                        <a href="/invoices/<?= (int)$i['id'] ?>"
                           style="color:#0A3D91;font-weight:600;text-decoration:none"><?= e($i['invoice_number']) ?></a>
                        <?php if ($i['so_number']): ?>
                            <div class="text-xs text-muted">SO <?= e($i['so_number']) ?></div>
                        <?php endif; ?>
                        <?php if ($i['po_number']): ?>
                            <div class="text-xs text-muted">PO <?= e($i['po_number']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="<?= $td ?>">
                        <?= e($i['company_name']) ?>
                        <?php if ($noEmail): ?>
                            <div style="font-size:.78rem;color:#b45309">No email address — nothing can be sent</div>
                        <?php endif; ?>
                    </td>
                    <td style="<?= $td ?>;color:#6b7280;font-size:.85rem">
                        <?= $i['ship_date'] ? date('j M', strtotime($i['ship_date'])) : 'Before handoff' ?>
                    </td>
                    <td style="<?= $td ?>;font-family:monospace;font-size:.82rem">
                        <?php if (!empty($i['fulfillment_methods'])): ?>
                            <?= e(strtr($i['fulfillment_methods'], ['carrier'=>'Carrier Shipping','pickup'=>'Customer Pickup','delivery'=>'USSC Delivery'])) ?>
                            <div style="font-family:inherit">Details are on the fulfillment record.</div>
                        <?php elseif ($noTrack): ?>
                            <span style="color:#b45309;font-family:inherit">None yet</span>
                        <?php else: ?>
                            <?= e($i['tracking_number']) ?>
                            <?php if ((int)$i['tracking_count'] > 1): ?>
                                <div class="text-xs text-muted" style="font-family:inherit">
                                    +<?= (int)$i['tracking_count'] - 1 ?> more
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td style="<?= $td ?>;text-align:right;font-weight:600"><?= money((float)$i['total_amount']) ?></td>
                    <td style="<?= $td ?>;text-align:right;white-space:nowrap">
                        <a href="/invoices/<?= (int)$i['id'] ?>" class="btn btn--secondary btn--sm"
                           style="margin-right:.35rem">Edit</a>
                        <form method="POST" action="/invoices/<?= (int)$i['id'] ?>/approve" style="display:inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn--primary btn--sm"
                                    onclick="return confirm('Approve <?= e($i['invoice_number']) ?>? This sends the customer their tracking.')">
                                Approve
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <p style="font-size:.78rem;color:#9ca3af;margin:1rem 0 2rem;max-width:46rem">
        Approval marks an invoice reviewed. Existing carrier invoices may send tracking using the configured shipment-email setting. New fulfillment invoices require the invoice Email action after approval. Check the recorded send result before assuming the customer was notified.
    </p>
<?php endif; ?>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';

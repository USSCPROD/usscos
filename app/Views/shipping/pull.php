<?php ob_start(); ?>
<?php
// Variables, not view-local functions — two views declaring the same function name in
// one request is a fatal that renders half a page. See CLAUDE.md.
$plCard = 'background:#fff;border:1px solid #d1d5db;border-radius:8px;margin-bottom:.85rem';
$plHd   = 'font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280';
$plNum  = fn($n) => rtrim(rtrim(number_format((float)$n, 2), '0'), '.');
?>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start">
    <div>
        <h1 class="page-title" style="margin:0">Pull list</h1>
        <p class="page-subtitle" style="margin:.3rem 0 0">
            Everything still owed across every open order, totalled by product — one walk
            of the building instead of one per order. Short items are at the top.
        </p>
    </div>
    <div>
        <a href="/shipping/release" class="btn btn--primary">Ready to ship</a>
        <a href="/shipping" class="btn btn--secondary">Queue</a>
    </div>
</div>

<?php if (empty($list)): ?>
    <div class="card" style="padding:2rem;text-align:center;color:#6b7280">
        Nothing to pull. Every open order is fully picked.
    </div>
<?php else: ?>

    <?php if (!empty($short)): ?>
        <div class="alert" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;margin-bottom:1.25rem">
            <strong><?= count($short) ?> product<?= count($short) === 1 ? '' : 's' ?> short.</strong>
            There is not enough on hand to fill every order that wants them, so somebody has
            to choose who waits. Those are listed first — the shipping clerk decides full or
            partial, not the warehouse.
        </div>
    <?php endif; ?>

    <?php foreach ($list as $p): ?>
        <?php
            $isShort   = $p['qty_on_hand'] < $p['total_needed'];
            $edgeColor = $isShort ? '#dc2626' : '#d1d5db';
        ?>
        <div style="<?= $plCard ?>;border-left:4px solid <?= $edgeColor ?>">

            <!-- the product, and what the whole building owes -->
            <div style="display:flex;justify-content:space-between;align-items:center;padding:.9rem 1.1rem;border-bottom:1px solid #eef0f3">
                <div>
                    <div style="font-size:1.05rem;font-weight:700">
                        <?= e($p['sku'] ?? '(no SKU)') ?>
                        <span style="font-weight:400;color:#6b7280">— <?= e($p['product_name'] ?? '') ?></span>
                    </div>
                    <div style="<?= $plHd ?>;margin-top:.3rem">
                        <?= (int)$p['order_count'] ?> order<?= (int)$p['order_count'] === 1 ? '' : 's' ?>
                        <?php if (!empty($p['most_urgent'])): ?>
                            &nbsp;·&nbsp; earliest ship-by <?= e(date('j M', strtotime((string)$p['most_urgent']))) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="text-align:right">
                    <div style="font-size:1.6rem;font-weight:700;font-family:monospace;line-height:1">
                        <?= $plNum($p['total_needed']) ?>
                    </div>
                    <div style="<?= $plHd ?>;margin-top:.25rem">
                        need &nbsp;·&nbsp;
                        <span style="color:<?= $isShort ? '#dc2626' : '#16a34a' ?>">
                            <?= $plNum($p['qty_on_hand']) ?> on hand
                        </span>
                    </div>
                </div>
            </div>

            <!-- who wants it, so the split is done before anyone is at the bay -->
            <table style="width:100%;border-collapse:collapse">
                <?php foreach ($p['orders'] as $o): ?>
                    <tr style="border-bottom:1px solid #f6f7f9">
                        <td style="padding:.55rem 1.1rem;width:7rem">
                            <a href="/shipping/<?= (int)$o['sales_order_id'] ?>/pick"
                               style="color:#0A3D91;font-weight:600;text-decoration:none">SO #<?= e($o['so_number']) ?></a>
                        </td>
                        <td style="padding:.55rem .5rem;color:#374151;font-size:.9rem">
                            <?= e($o['company_name'] ?? '') ?>
                            <?php if (($o['priority'] ?? 'normal') !== 'normal'): ?>
                                <span class="badge badge--warning" style="margin-left:.4rem"><?= e(strtoupper($o['priority'])) ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:.55rem .5rem;text-align:right;font-family:monospace;font-size:1.05rem;font-weight:700;width:5rem">
                            <?= $plNum($o['qty']) ?>
                        </td>
                        <td style="padding:.55rem .5rem;color:#9ca3af;font-size:.8rem;width:9rem">
                            <?php if ((float)$o['qty_picked'] > 0): ?>
                                <?= $plNum($o['qty_picked']) ?> of <?= $plNum($o['qty_ordered']) ?> already picked
                            <?php endif; ?>
                        </td>
                        <td style="padding:.55rem 1.1rem;text-align:right;white-space:nowrap;width:12rem">
                            <form method="POST" action="/shipping/pull/pick" style="display:inline-flex;gap:.4rem;align-items:center">
                                <?= csrf_field() ?>
                                <input type="hidden" name="line_id" value="<?= (int)$o['line_id'] ?>">
                                <input type="number" name="qty" step="1" min="1"
                                       max="<?= $plNum($o['qty']) ?>" value="<?= $plNum($o['qty']) ?>"
                                       style="width:4.5rem;padding:.45rem;font-size:.95rem;border:1px solid #d1d5db;border-radius:5px;text-align:right">
                                <button type="submit" class="btn btn--secondary btn--sm">Picked</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endforeach; ?>

<?php endif; ?>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';

<?php ob_start(); ?>
<?php
// Uniquely named and guarded — see the note in CLAUDE.md about two views defining the
// same function in one request.
if (!function_exists('pvQty')) {
    function pvQty($n): string
    {
        return rtrim(rtrim(number_format((float)$n, 2), '0'), '.');
    }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <a href="/purchasing" class="back-link">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Purchasing
        </a>
        <h1 class="page-title">Receipt Variances</h1>
        <p class="page-subtitle" style="margin:.25rem 0 0">
            Deliveries that did not match their purchase order. A PO is what we asked for;
            what turned up is what we have.
        </p>
    </div>
</div>

<?php if (empty($items)): ?>
    <div class="card" style="padding:3rem;text-align:center">
        <div style="font-size:1.05rem;color:#111;margin-bottom:.4rem">Nothing to review</div>
        <p class="text-muted" style="margin:0;font-size:.9rem">
            Every receipt so far matches its purchase order.
        </p>
    </div>
<?php else: ?>
    <p style="font-size:.85rem;color:#6b7280;margin:0 0 1rem;max-width:44rem">
        A batch yields what it yields — order 600 cases and 570 may be made. Either set the
        PO to what actually arrived, which is usually right because the PO is what gets
        billed, or explain the difference if the rest is still coming.
    </p>

    <?php foreach ($items as $v): ?>
        <?php
        $difference = (float)$v['difference'];
        $isShort    = $difference < 0;
        ?>
        <div class="card" style="padding:1.25rem;margin-bottom:1rem">
            <table style="width:100%;border-collapse:collapse;margin-bottom:1rem">
                <tr>
                    <td style="vertical-align:top">
                        <a href="/purchasing/<?= (int)$v['po_id'] ?>"
                           style="font-weight:600;color:#0A3D91;text-decoration:none;font-size:1rem">
                            <?= e($v['po_number']) ?>
                        </a>
                        <?php if (!empty($v['vendor_name'])): ?>
                            <span style="color:#6b7280;font-size:.9rem"> · <?= e($v['vendor_name']) ?></span>
                        <?php endif; ?>
                        <div style="margin-top:.3rem;font-size:.92rem">
                            <?= e($v['product_name'] ?? $v['description'] ?? 'Line item') ?>
                            <?php if (!empty($v['sku'])): ?>
                                <span class="font-mono text-xs text-muted"> · <?= e($v['sku']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($v['last_received_at'])): ?>
                            <div style="font-size:.78rem;color:#9ca3af;margin-top:.2rem">
                                Last received <?= date('M j, Y', strtotime($v['last_received_at'])) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="vertical-align:top;text-align:right;white-space:nowrap;width:22rem">
                        <table style="border-collapse:collapse;margin-left:auto">
                            <tr>
                                <?php $cell = 'padding:.15rem .9rem;text-align:right'; ?>
                                <td style="<?= $cell ?>;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#9ca3af">PO says</td>
                                <td style="<?= $cell ?>;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#9ca3af">Arrived</td>
                                <td style="<?= $cell ?>;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#9ca3af">Difference</td>
                            </tr>
                            <tr>
                                <td style="<?= $cell ?>;font-size:1.05rem"><?= pvQty($v['qty_ordered']) ?></td>
                                <td style="<?= $cell ?>;font-size:1.05rem;font-weight:600"><?= pvQty($v['qty_received']) ?></td>
                                <td style="<?= $cell ?>;font-size:1.05rem;font-weight:700;color:<?= $isShort ? '#b45309' : '#0A3D91' ?>">
                                    <?= $isShort ? '' : '+' ?><?= pvQty($difference) ?>
                                </td>
                            </tr>
                        </table>
                        <div style="font-size:.78rem;color:<?= $isShort ? '#b45309' : '#0A3D91' ?>;margin-top:.3rem;padding-right:.9rem">
                            <?= $isShort ? 'Less arrived than the PO says' : 'More arrived than the PO says' ?>
                        </div>
                    </td>
                </tr>
            </table>

            <form method="POST" action="/purchasing/variance/<?= (int)$v['id'] ?>" style="margin:0">
                <?= csrf_field() ?>
                <table style="width:100%;border-collapse:separate;border-spacing:.6rem 0;margin:0 -.6rem">
                    <tr>
                        <td>
                            <input type="text" name="variance_note" maxlength="500"
                                   placeholder="What happened? e.g. batch yielded 570 after canning"
                                   style="width:100%;padding:.5rem .65rem;font-size:.9rem;font-family:inherit;
                                          border:1px solid #d1d5db;border-radius:5px;box-sizing:border-box">
                        </td>
                        <td style="width:15rem;text-align:right;white-space:nowrap">
                            <button type="submit" name="action" value="accept_received" class="btn btn--primary btn--sm">
                                Set PO to <?= pvQty($v['qty_received']) ?>
                            </button>
                            <button type="submit" name="action" value="acknowledge" class="btn btn--secondary btn--sm">
                                Explain &amp; close
                            </button>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
    <?php endforeach; ?>

    <p style="font-size:.78rem;color:#9ca3af;margin:1rem 0 2rem;max-width:44rem">
        Setting the PO to what arrived also corrects the line total, because the PO is what
        gets billed — billing 600 cases when 570 were made would be wrong. Explaining
        instead leaves the PO alone and keeps the line outstanding.
    </p>
<?php endif; ?>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';

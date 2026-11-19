<?php
/**
 * Staff: website customer accounts. Link each to the customer it orders for.
 * A person who says they're a distributor is listed first.
 */
ob_start();
$tabs = ['unlinked' => 'Waiting to link', 'linked' => 'Linked', 'blocked' => 'Paused', 'all' => 'All'];
$tabCount = ['unlinked' => $counts['unlinked'] ?? 0, 'linked' => $counts['linked'] ?? 0, 'blocked' => $counts['blocked'] ?? 0, 'all' => $counts['total'] ?? 0];
?>
<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-title">Website accounts</h1>
        <p class="page-subtitle">People who signed up on the website. Link each one to the customer it orders for:
            that sets its prices, and linking to a parent covers its sub-accounts.</p>
    </div>
</div>

<table style="width:100%;border-collapse:collapse;margin-bottom:1rem"><tr>
    <td>
        <?php foreach ($tabs as $tabKey => $tabLabel): ?>
            <a href="/website-accounts?show=<?= e($tabKey) ?>" class="btn btn--sm <?= $filter === $tabKey ? 'btn--primary' : 'btn--secondary' ?>"
               style="margin-right:.3rem"><?= e($tabLabel) ?> (<?= (int)$tabCount[$tabKey] ?>)</a>
        <?php endforeach; ?>
    </td>
    <td style="text-align:right">
        <form method="GET" action="/website-accounts" style="margin:0">
            <input type="hidden" name="show" value="<?= e($filter) ?>">
            <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search name, email, company"
                   style="padding:.45rem .6rem;border:1px solid #d1d5db;border-radius:6px;width:16rem">
        </form>
    </td>
</tr></table>

<?php if (!$logins): ?>
    <div class="card" style="padding:1.5rem;color:#6b7280">
        <?= $filter === 'unlinked' ? 'Nobody is waiting to be linked.' : 'No accounts here.' ?>
    </div>
<?php else: ?>
<div class="card" style="padding:0">
<table style="width:100%;border-collapse:collapse;font-size:.9rem">
    <tr style="background:#f9fafb;text-align:left;color:#374151">
        <th style="padding:.6rem .8rem">Person</th>
        <th style="padding:.6rem .8rem">Said they are</th>
        <th style="padding:.6rem .8rem">Orders for</th>
        <th style="padding:.6rem .8rem;width:30rem">Link</th>
    </tr>
    <?php foreach ($logins as $acct): ?>
    <tr style="border-top:1px solid #e5e7eb;vertical-align:top">
        <td style="padding:.7rem .8rem">
            <strong><?= e($acct['name']) ?></strong>
            <?php if ($acct['status'] === 'blocked'): ?><span class="badge badge--danger">Paused</span><?php endif; ?>
            <div><a href="mailto:<?= e($acct['email']) ?>"><?= e($acct['email']) ?></a></div>
            <div class="text-muted"><?= e($acct['phone']) ?></div>
            <div class="text-muted" style="font-size:.8rem">Signed up <?= e(date('M j, Y', strtotime((string)$acct['created_at']))) ?>
                <?= $acct['source'] === 'stencil_shop' ? ' · from the old stencil shop' . ($acct['shop_role'] ? ' (' . e($acct['shop_role']) . ')' : '') : '' ?></div>
        </td>
        <td style="padding:.7rem .8rem">
            <?= e($acct['organization']) ?>
            <div class="text-muted"><?= e($types[$acct['buyer_type']] ?? $acct['buyer_type']) ?></div>
            <?php if ($acct['asked_trade']): ?><span class="badge badge--warning">Says distributor</span><?php endif; ?>
        </td>
        <td style="padding:.7rem .8rem">
            <?php if ($acct['customer_id']): ?>
                <a href="/customers/<?= (int)$acct['customer_id'] ?>"><?= e($acct['customer_name']) ?></a>
                <?php if ($acct['parent_name']): ?><div class="text-muted">under <?= e($acct['parent_name']) ?></div><?php endif; ?>
                <div><span class="badge badge--info"><?= e(trim((string)$acct['customer_type']) ?: (trim((string)$acct['parent_type']) ?: 'Retail')) ?> prices</span></div>
                <div class="text-muted" style="font-size:.8rem">by <?= e($acct['linked_by_name'] ?? '—') ?></div>
            <?php else: ?>
                <span class="text-muted">Not linked: retail, own orders only</span>
            <?php endif; ?>
        </td>
        <td style="padding:.7rem .8rem">
            <form method="POST" action="/website-accounts/<?= (int)$acct['id'] ?>/link" class="wa-link" style="margin:0">
                <?= csrf_field() ?>
                <input type="hidden" name="show" value="<?= e($filter) ?>">
                <input type="hidden" name="customer_id" value="">
                <div style="position:relative">
                    <input type="text" class="wa-find" placeholder="Find the customer…" autocomplete="off"
                           value="" style="width:100%;box-sizing:border-box;padding:.45rem .6rem;border:1px solid #d1d5db;border-radius:6px">
                    <div class="wa-results" style="display:none;position:absolute;left:0;right:0;z-index:20;background:#fff;border:1px solid #d1d5db;border-radius:6px;max-height:16rem;overflow:auto;box-shadow:0 6px 20px #0002"></div>
                </div>
                <table style="width:100%;border-collapse:collapse;margin-top:.4rem"><tr>
                    <td>
                        <select name="tier" style="padding:.4rem;border:1px solid #d1d5db;border-radius:6px">
                            <option value="">Keep the customer's price tier</option>
                            <?php foreach ($tiers as $tierName): ?>
                                <option value="<?= e($tierName) ?>">Set to <?= e($tierName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td style="text-align:right"><button type="submit" class="btn btn--primary btn--sm" disabled>Link</button></td>
                </tr></table>
            </form>
            <table style="width:100%;border-collapse:collapse;margin-top:.5rem"><tr>
                <?php if ($acct['customer_id']): ?>
                <td>
                    <form method="POST" action="/website-accounts/<?= (int)$acct['id'] ?>/unlink" style="margin:0"
                          onsubmit="return confirm('Unlink this account? It goes back to retail prices.')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--secondary btn--sm">Unlink</button>
                    </form>
                </td>
                <?php endif; ?>
                <td style="text-align:right">
                    <form method="POST" action="/website-accounts/<?= (int)$acct['id'] ?>/status" style="margin:0"
                          onsubmit="<?= $acct['status'] === 'blocked' ? '' : "this.note.value = prompt('Why pause this account? (only staff see this)') || ''; return true;" ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="note" value="">
                        <input type="hidden" name="status" value="<?= $acct['status'] === 'blocked' ? 'active' : 'blocked' ?>">
                        <button type="submit" class="btn btn--secondary btn--sm"><?= $acct['status'] === 'blocked' ? 'Restore' : 'Pause account' ?></button>
                    </form>
                    <?php if ($acct['status'] === 'blocked' && $acct['blocked_note']): ?>
                        <div class="text-muted" style="font-size:.8rem"><?= e($acct['blocked_note']) ?></div>
                    <?php endif; ?>
                </td>
            </tr></table>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</div>
<?php endif; ?>

<script>
// Pick the customer to link: the same customer search as the order forms.
document.querySelectorAll('form.wa-link').forEach(function (form) {
    var find = form.querySelector('.wa-find'), box = form.querySelector('.wa-results'),
        id = form.querySelector('input[name="customer_id"]'), go = form.querySelector('button[type="submit"]'), t;
    var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; };
    find.addEventListener('input', function () {
        id.value = ''; go.disabled = true; clearTimeout(t);
        var q = find.value.trim();
        if (q.length < 2) { box.style.display = 'none'; return; }
        t = setTimeout(function () {
            fetch('/customers/autocomplete?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (rows) {
                    box.innerHTML = rows.length ? rows.map(function (c) {
                        return '<div data-id="' + c.id + '" data-name="' + esc(c.company_name) + '" style="padding:.45rem .6rem;cursor:pointer;border-bottom:1px solid #f3f4f6">' +
                            '<strong>' + esc(c.company_name) + '</strong><div style="font-size:.8rem;color:#6b7280">' + esc(c.email || '') + (c.phone ? ' · ' + esc(c.phone) : '') + '</div></div>';
                    }).join('') : '<div style="padding:.5rem .6rem;color:#6b7280">No customers match.</div>';
                    box.style.display = 'block';
                    box.querySelectorAll('[data-id]').forEach(function (row) {
                        row.addEventListener('click', function () {
                            id.value = row.dataset.id; find.value = row.dataset.name; box.style.display = 'none'; go.disabled = false;
                        });
                    });
                });
        }, 200);
    });
    document.addEventListener('click', function (e) { if (!form.contains(e.target)) box.style.display = 'none'; });
});
</script>
<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';

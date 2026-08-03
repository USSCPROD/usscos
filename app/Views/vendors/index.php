<?php ob_start(); ?>
<?php
$flash      = \App\Core\Session::getFlash('success');
$flashError = \App\Core\Session::getFlash('error');
$p = $paginator;
?>

<?php if ($flash): ?>
    <div class="alert alert--success" style="margin-bottom:1rem"><?= e($flash) ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert--danger" style="margin-bottom:1rem"><?= e($flashError) ?></div>
<?php endif; ?>

<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-title">Vendors</h1>
        <p class="page-subtitle"><?= number_format($p['total']) ?> vendor<?= $p['total'] !== 1 ? 's' : '' ?></p>
    </div>
    <div class="page-header__right">
        <a href="/vendors/create" class="btn btn--primary">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add Vendor
        </a>
    </div>
</div>

<form method="GET" action="/vendors" style="margin-bottom:1rem">
    <table style="width:100%;border-collapse:collapse">
        <tr>
            <td style="padding-right:.5rem">
                <input type="text" name="search" value="<?= e($search) ?>"
                       placeholder="Search company, name, or email…"
                       class="input" style="width:100%">
            </td>
            <td style="width:80px">
                <button type="submit" class="btn btn--primary" style="width:100%">Search</button>
            </td>
        </tr>
    </table>
</form>

<div class="card" style="padding:0;overflow:hidden">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Company</th>
                    <th>Contact</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Account #</th>
                    <th>Terms</th>
                    <th class="text-center">Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($p['data'])): ?>
                    <tr><td colspan="8" class="table__empty">No vendors found.</td></tr>
                <?php else: ?>
                    <?php foreach ($p['data'] as $v): ?>
                        <tr>
                            <td style="font-weight:600"><?= e($v['company_name']) ?></td>
                            <td class="text-muted" style="font-size:.875rem">
                                <?= e(trim(($v['first_name'] ?? '') . ' ' . ($v['last_name'] ?? ''))) ?: '—' ?>
                            </td>
                            <td class="text-muted" style="font-size:.875rem"><?= e($v['phone'] ?? '—') ?></td>
                            <td class="text-muted" style="font-size:.875rem"><?= e($v['email'] ?? '—') ?></td>
                            <td class="text-muted" style="font-size:.875rem;font-family:monospace"><?= e($v['account_number'] ?? '—') ?></td>
                            <td class="text-muted" style="font-size:.875rem"><?= e($v['payment_term_name'] ?? '—') ?></td>
                            <td class="text-center">
                                <span class="badge <?= $v['is_active'] ? 'badge--success' : 'badge--neutral' ?>">
                                    <?= $v['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td class="text-right">
                                <a href="/vendors/<?= (int)$v['id'] ?>/edit" class="btn btn--secondary"
                                   style="padding:.3rem .75rem;font-size:.8rem">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($p['last_page'] > 1): ?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-top:1rem;font-size:.875rem;color:var(--color-text-muted)">
    <div>Showing <?= number_format($p['from']) ?>–<?= number_format($p['to']) ?> of <?= number_format($p['total']) ?></div>
    <div style="display:flex;gap:.35rem">
        <?php for ($pg = 1; $pg <= $p['last_page']; $pg++): ?>
            <a href="?search=<?= urlencode($search) ?>&page=<?= $pg ?>"
               style="padding:.3rem .6rem;border-radius:4px;text-decoration:none;
                      background:<?= $pg === $p['current_page'] ? 'var(--color-primary)' : 'var(--color-bg-subtle)' ?>;
                      color:<?= $pg === $p['current_page'] ? '#fff' : 'var(--color-text)' ?>">
                <?= $pg ?>
            </a>
        <?php endfor; ?>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';

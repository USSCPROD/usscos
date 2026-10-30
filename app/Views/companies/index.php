<?php declare(strict_types=1); ob_start(); ?>
<h1 class="page-title"><?= e(\App\Services\CompanyAccess::COMPANIES[\App\Services\CompanyAccess::current()] ?? 'No company access') ?></h1>
<div class="card" style="padding:1.5rem">
<?php if (\App\Services\CompanyAccess::current() === 'tcc'): ?>
<p>Your Technical Coatings access is set up.</p>
<p>Purchasing, invoices, bills, inventory, and payments are not available in this workspace yet. Their company ownership and integration must be set up first.</p>
<?php elseif (\App\Services\CompanyAccess::current() === 'ussc'): ?>
<a href="/dashboard" class="btn btn--primary">Open USSC</a>
<?php else: ?>
<p>Contact an owner to assign company access.</p>
<?php endif; ?>
</div>
<?php $content = ob_get_clean(); include BASE_PATH . '/app/Views/layouts/app.php'; ?>

<?php ob_start(); ?>

<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-title">Admin</h1>
        <p class="page-subtitle">Manage system settings and lookup tables</p>
    </div>
</div>

<table style="width:100%;border-collapse:collapse;table-layout:fixed">
    <colgroup><col style="width:33.33%"><col style="width:33.33%"><col style="width:33.33%"></colgroup>
    <tr style="vertical-align:top">
        <td style="padding:.5rem">
            <?php adminCard('Ship Via', 'Shipping methods used on Sales Orders and Invoices.', '/admin/ship-via'); ?>
        </td>
        <td style="padding:.5rem">
            <?php adminCard('Payment Terms', 'Net 30, Net 60, Due on Receipt, Credit Card, etc.', '/admin/payment-terms'); ?>
        </td>
        <td style="padding:.5rem">
            <?php adminCard('Tax Rates', 'State and county tax rates applied to invoice line items.', '/admin/tax-rates'); ?>
        </td>
    </tr>
    <tr style="vertical-align:top">
        <td style="padding:.5rem">
            <?php adminCard('Customer Messages', 'Footer messages that appear on Invoices and Sales Orders.', '/admin/customer-messages'); ?>
        </td>
        <td style="padding:.5rem">
            <?php adminCard('Users', 'Manage employee logins, roles, and rep assignments.', '/admin/users'); ?>
        </td>
        <td style="padding:.5rem">
            <?php adminCard('Customer Types', 'Retail, Distributor, Rep, School — classify your customers.', '/admin/customer-types'); ?>
        </td>
    </tr>
    <tr style="vertical-align:top">
        <td style="padding:.5rem">
            <?php adminCard('Company Info', 'Your company name, address, and contact details.', '/admin/company'); ?>
        </td>
        <td style="padding:.5rem">
            <?php adminCard('Departments', 'Stencil Sales, Paint Sales, Production, Bookkeeping — organize your team.', '/admin/departments'); ?>
        </td>
        <td style="padding:.5rem">
            <?php adminCard('Lead Routing', 'Control who gets notified and assigned for each website form type.', '/admin/lead-routing'); ?>
        </td>
    </tr>
</table>

<?php
function adminCard(string $title, string $desc, string $href): void { ?>
    <a href="<?= $href ?>" style="display:block;background:#fff;border:1px solid #d1d5db;border-radius:8px;padding:1.25rem 1.5rem;text-decoration:none;box-shadow:0 1px 3px rgba(0,0,0,.07);transition:box-shadow .15s">
        <div style="font-size:1rem;font-weight:700;color:#0A3D91;margin-bottom:.35rem"><?= $title ?></div>
        <div style="font-size:.875rem;color:#6b7280;line-height:1.5"><?= $desc ?></div>
        <div style="margin-top:.75rem;font-size:.8rem;font-weight:600;color:#0A3D91">Manage &rsaquo;</div>
    </a>
<?php }
?>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';

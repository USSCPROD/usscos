<?php ob_start();

$tier = $customer['customer_type'] ?: 'Retail';
?>

<div class="page-header">
    <div class="page-header__left">
        <a href="/customers/<?= (int)$customer['id'] ?>" class="back-link">Back to <?= e($customer['company_name']) ?></a>
        <h1 class="page-title">Agreed pricing</h1>
    </div>
</div>

<p class="text-muted" style="margin:-.5rem 0 1.25rem;max-width:760px;font-size:.9rem">
    Prices agreed with <strong><?= e($customer['company_name']) ?></strong>, which override the
    <strong><?= e($tier) ?></strong> tier on quotes and orders. A product with nothing agreed
    keeps its tier price. Set a quantity to make it a volume break — the highest break the
    line qualifies for is the one that applies.
</p>

<!-- Agree a price -->
<div class="card" style="padding:1rem 1.15rem;margin-bottom:1.25rem;max-width:760px">
    <div style="font-weight:700;color:#111;margin-bottom:.6rem;font-size:.95rem">Agree a price</div>
    <form method="POST" action="/customers/<?= (int)$customer['id'] ?>/pricing" autocomplete="off">
        <?= csrf_field() ?>
        <table style="width:100%;border-collapse:collapse">
            <tr>
                <td style="padding:0 .6rem .6rem 0;width:48%;vertical-align:top">
                    <label class="form-label">Product</label>
                    <input type="text" id="prodSearch" placeholder="SKU or name&hellip;" class="input"
                           style="width:100%;box-sizing:border-box" autocomplete="off">
                    <input type="hidden" name="product_id" id="prodId">
                    <div id="prodChosen" class="text-muted" style="font-size:.8rem;margin-top:.25rem"></div>
                    <div id="prodResults" style="position:relative"></div>
                </td>
                <td style="padding:0 .6rem .6rem 0;width:17%;vertical-align:top">
                    <label class="form-label">From qty</label>
                    <input type="number" name="min_qty" value="1" min="1" step="1" class="input" style="width:100%;box-sizing:border-box">
                </td>
                <td style="padding:0 .6rem .6rem 0;width:17%;vertical-align:top">
                    <label class="form-label">Unit price</label>
                    <input type="number" name="unit_price" step="0.01" min="0.01" required class="input" style="width:100%;box-sizing:border-box">
                </td>
                <td style="padding:0 0 .6rem 0;vertical-align:bottom">
                    <button type="submit" class="btn btn--primary btn--sm">Save</button>
                </td>
            </tr>
            <tr>
                <td colspan="4" style="padding:0">
                    <label class="form-label">Why (optional)</label>
                    <input type="text" name="note" maxlength="255" class="input" style="width:100%;box-sizing:border-box"
                           placeholder="e.g. matched Pioneer on the 18oz case, agreed with Chip, Nov 2026">
                </td>
            </tr>
        </table>
    </form>
</div>

<!-- What is agreed -->
<div class="card" style="padding:0;overflow:hidden;max-width:980px">
    <div style="padding:.85rem 1.1rem;border-bottom:1px solid #e5e7eb;background:#f8f9fb">
        <h3 style="margin:0;font-size:.95rem;font-weight:700;color:#111">
            Agreed prices<?= $prices !== [] ? ' (' . count($prices) . ')' : '' ?>
        </h3>
    </div>

    <?php if ($prices === []): ?>
        <div style="padding:1.5rem 1.1rem;color:#6b7280;font-size:.9rem">
            Nothing agreed. This account pays <?= e($tier) ?> prices on everything.
        </div>
    <?php else: ?>
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="background:#fafafa;border-bottom:1px solid #e5e7eb">
                    <th style="text-align:left;padding:.55rem 1.1rem;font-size:.75rem;color:#6b7280;text-transform:uppercase">Product</th>
                    <th style="text-align:right;padding:.55rem .5rem;font-size:.75rem;color:#6b7280;text-transform:uppercase">From qty</th>
                    <th style="text-align:right;padding:.55rem .5rem;font-size:.75rem;color:#6b7280;text-transform:uppercase">Agreed</th>
                    <th style="text-align:right;padding:.55rem .5rem;font-size:.75rem;color:#6b7280;text-transform:uppercase">List</th>
                    <th style="text-align:right;padding:.55rem 1.1rem;font-size:.75rem;color:#6b7280;text-transform:uppercase"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($prices as $row):
                $agreedPrice = (float)$row['unit_price'];
                $listPrice   = (float)$row['list_price'];
                /* An agreed price ABOVE list is the one nobody notices. It happens whenever a
                   list price is cut and the deals underneath it are not revisited, and the
                   customer is the one who finds out. Flagged rather than left to be spotted. */
                $aboveList   = $listPrice > 0 && $agreedPrice > $listPrice;
            ?>
                <tr style="border-bottom:1px solid #f1f2f5">
                    <td style="padding:.6rem 1.1rem">
                        <strong style="font-size:.88rem"><?= e($row['sku']) ?></strong>
                        <div class="text-muted" style="font-size:.8rem"><?= e(str_limit((string)$row['product_name'], 70)) ?></div>
                        <?php if (!empty($row['note'])): ?>
                            <div style="font-size:.8rem;color:#374151;margin-top:.15rem"><?= e($row['note']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($row['agreed_by'])): ?>
                            <div class="text-muted" style="font-size:.76rem;margin-top:.1rem">
                                set by <?= e($row['agreed_by']) ?>, <?= date('M j, Y', strtotime((string)$row['created_at'])) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="padding:.6rem .5rem;text-align:right;white-space:nowrap;font-size:.88rem">
                        <?= (float)$row['min_qty'] > 1 ? number_format((float)$row['min_qty']) . '+' : 'any' ?>
                    </td>
                    <td style="padding:.6rem .5rem;text-align:right;white-space:nowrap">
                        <strong style="color:<?= $aboveList ? '#b91c1c' : '#111' ?>"><?= money($agreedPrice) ?></strong>
                        <?php if ($aboveList): ?>
                            <div style="font-size:.74rem;color:#b91c1c;font-weight:600">above list</div>
                        <?php endif; ?>
                    </td>
                    <td style="padding:.6rem .5rem;text-align:right;white-space:nowrap;color:#6b7280;font-size:.88rem">
                        <?= money($listPrice) ?>
                        <?php if ($listPrice > 0 && !$aboveList): ?>
                            <div style="font-size:.74rem;color:#16a34a">
                                &minus;<?= number_format((1 - ($agreedPrice / $listPrice)) * 100, 1) ?>%
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="padding:.6rem 1.1rem;text-align:right">
                        <form method="POST" action="/customers/<?= (int)$customer['id'] ?>/pricing/<?= (int)$row['id'] ?>/delete"
                              onsubmit="return confirm('Remove this agreed price? This account will pay its tier price for <?= e($row['sku']) ?>.');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn--secondary btn--sm">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
/* Product picker. Reuses the quote builder's own autocomplete endpoint so there is one
   definition of what counts as a sellable product - a second search that disagreed about
   raw materials or inactive items would offer prices on things nobody can order. */
(function () {
    const search  = document.getElementById('prodSearch');
    const hidden  = document.getElementById('prodId');
    const chosen  = document.getElementById('prodChosen');
    const results = document.getElementById('prodResults');
    let timer = null;

    const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g,
        c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    function clearList() { results.innerHTML = ''; }

    search.addEventListener('input', function () {
        hidden.value = '';
        chosen.textContent = '';
        clearTimeout(timer);

        const q = search.value.trim();
        if (q.length < 2) { clearList(); return; }

        timer = setTimeout(async () => {
            try {
                const res  = await fetch('/products/autocomplete?q=' + encodeURIComponent(q));
                const rows = await res.json();
                clearList();
                if (!rows.length) { return; }

                const box = document.createElement('div');
                box.style.cssText = 'position:absolute;z-index:20;background:#fff;border:1px solid #d1d5db;'
                    + 'border-radius:6px;box-shadow:0 4px 14px rgba(0,0,0,.09);max-height:16rem;overflow:auto;width:100%';

                rows.forEach(p => {
                    const item = document.createElement('div');
                    item.style.cssText = 'padding:.45rem .6rem;cursor:pointer;font-size:.85rem;border-bottom:1px solid #f1f2f5';
                    item.innerHTML = '<strong>' + esc(p.sku) + '</strong> — ' + esc(p.name)
                        + ' <span style="color:#6b7280">list $' + Number(p.price).toFixed(2) + '</span>';
                    item.addEventListener('mousedown', () => {
                        hidden.value = p.id;
                        search.value = p.sku;
                        chosen.textContent = p.name + ' — list $' + Number(p.price).toFixed(2);
                        clearList();
                    });
                    box.appendChild(item);
                });
                results.appendChild(box);
            } catch (e) { clearList(); }
        }, 220);
    });

    search.addEventListener('blur', () => setTimeout(clearList, 150));
})();
</script>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';

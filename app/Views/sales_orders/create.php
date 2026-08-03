<?php ob_start();
$company_defaults = $company_defaults ?? [];

$termDaysMap = [];
foreach ($payment_terms as $pt) {
    $termDaysMap[(int)$pt['id']] = (int)$pt['days_due'];
}

$defaultTaxId  = (int)($company_defaults['default_tax_rate_id'] ?? 0);
$defaultTaxPct = 0;
if (!$defaultTaxId) {
    foreach ($tax_rates as $tr) {
        if (stripos($tr['name'], 'out of state') !== false) {
            $defaultTaxId  = (int)$tr['id'];
            break;
        }
    }
}
foreach ($tax_rates as $tr) {
    if ((int)$tr['id'] === $defaultTaxId) {
        $defaultTaxPct = (float)$tr['rate'] * 100;
        break;
    }
}

$defaultTermId    = (int)($company_defaults['default_payment_term_id'] ?? 0);
$defaultShipViaId = (int)($company_defaults['default_ship_via_id']     ?? 0);
$currentUser = \App\Core\Auth::user();
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
    <div>
        <a href="/sales-orders" class="back-link">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Sales Orders
        </a>
        <h1 class="page-title" style="margin-top:.25rem">New Sales Order</h1>
    </div>
</div>

<form method="post" action="/sales-orders" id="soForm">

<!-- ═══════════════════════════════════════════════════════════════════════════
     SALES ORDER DOCUMENT
     ═══════════════════════════════════════════════════════════════════════════ -->
<div style="background:#fff;border:1px solid #d1d5db;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,.08);overflow:visible;font-family:inherit">

    <!-- ── Customer/Job bar ─────────────────────────────────────────────── -->
    <div style="padding:1rem 1.5rem;background:#f8f9fb;border-bottom:1px solid #d1d5db">
        <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
            <label style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;white-space:nowrap">Customer / Job</label>
            <div style="position:relative;flex:1;max-width:520px">
                <input type="text" id="customerSearch" placeholder="Search by name…" autocomplete="off"
                    style="width:100%;padding:.65rem .85rem;font-size:1rem;border:1px solid #d1d5db;border-radius:6px;background:#fff;box-sizing:border-box">
                <input type="hidden" name="customer_id" id="customerId" required>
                <div id="customerSuggestions" style="display:none;position:absolute;top:100%;left:0;right:0;z-index:9999;margin-top:2px;background:#fff;border:1px solid #c8c8c8;border-radius:6px;box-shadow:0 8px 28px rgba(0,0,0,.18);max-height:300px;overflow-y:auto"></div>
            </div>
            <div id="customerSelected" style="display:none;flex:1;max-width:520px;padding:.55rem .85rem;background:#fff;border:1px solid #d1d5db;border-radius:6px;font-size:1rem;font-weight:600;display:none;align-items:center;justify-content:space-between;gap:.5rem"></div>
        </div>
    </div>

    <!-- ── Header: SO title | date+SO# | Bill To | Ship To ──────────────── -->
    <table style="width:100%;border-collapse:collapse;border-bottom:2px solid #d1d5db;table-layout:fixed">
        <colgroup>
            <col style="width:200px">
            <col style="width:175px">
            <col>
            <col>
        </colgroup>
        <tr>
            <!-- Sales Order title + SO# -->
            <td style="width:200px;padding:1.25rem 1.5rem;vertical-align:top;border-right:1px solid #d1d5db">
                <div style="font-size:1.75rem;font-weight:700;color:#111;letter-spacing:-.02em;margin-bottom:1.25rem">Sales Order</div>
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.4rem">S.O. No.</div>
                <div style="padding:.6rem .85rem;background:#f3f4f6;border:1px solid #d1d5db;border-radius:6px;font-size:1rem;font-weight:700;color:#374151"><?= e($next_so_number) ?></div>
            </td>
            <!-- Date + Ship Date -->
            <td style="width:175px;padding:1rem 1.25rem;vertical-align:top;border-right:1px solid #d1d5db">
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.4rem">Date</div>
                <input type="date" name="order_date" value="<?= date('Y-m-d') ?>" required
                    style="width:100%;padding:.65rem .75rem;font-size:.95rem;border:1px solid #d1d5db;border-radius:6px;background:#fff;box-sizing:border-box;margin-bottom:.85rem">
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.4rem">Ship Date</div>
                <input type="date" name="requested_ship_date"
                    style="width:100%;padding:.65rem .75rem;font-size:.95rem;border:1px solid #d1d5db;border-radius:6px;background:#fff;box-sizing:border-box">
            </td>
            <!-- Bill To -->
            <td style="padding:1rem 1.25rem;vertical-align:top;border-right:1px solid #d1d5db">
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.4rem">Name / Address</div>
                <div id="billToBox" style="padding:.75rem 1rem;min-height:110px;background:#f8f9fb;border:1px solid #d1d5db;border-radius:6px;font-size:.9rem;line-height:1.8;color:#374151">
                    <span style="color:#9ca3af">Select a customer above</span>
                </div>
            </td>
            <!-- Ship To -->
            <td style="padding:1rem 1.25rem;vertical-align:top">
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.4rem;display:flex;align-items:center;gap:.5rem">
                    Ship To
                    <label style="font-size:.8rem;font-weight:600;text-transform:none;letter-spacing:0;display:inline-flex;align-items:center;gap:.3rem;cursor:pointer;color:#6b7280">
                        <input type="checkbox" id="sameAsBilling" checked style="accent-color:#0A3D91"> Same as billing
                    </label>
                </div>
                <div id="shipSameBox" style="padding:.75rem 1rem;min-height:110px;background:#f8f9fb;border:1px solid #d1d5db;border-radius:6px;font-size:.9rem;color:#9ca3af">
                    Same as billing address
                </div>
                <div id="shipEditBox" style="display:none">
                    <div style="display:flex;flex-direction:column;gap:.4rem">
                        <input type="text" name="ship_name"      placeholder="Name / Attn"  style="<?= soInpStyle() ?>">
                        <input type="text" name="ship_address_1" placeholder="Address"      style="<?= soInpStyle() ?>">
                        <input type="text" name="ship_address_2" placeholder="Address 2"    style="<?= soInpStyle() ?>">
                        <div style="display:grid;grid-template-columns:1fr 52px 85px;gap:.35rem">
                            <input type="text" name="ship_city"  placeholder="City"         style="<?= soInpStyle() ?>">
                            <input type="text" name="ship_state" placeholder="ST" maxlength="2" style="<?= soInpStyle() ?>;text-transform:uppercase">
                            <input type="text" name="ship_zip"   placeholder="Zip"          style="<?= soInpStyle() ?>">
                        </div>
                        <input type="text" name="ship_phone" placeholder="Phone" style="<?= soInpStyle() ?>">
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- ── Meta row: PO · Terms · Rep · Processed By · Ship Via ──────────── -->
    <table style="width:100%;border-collapse:collapse;border-bottom:2px solid #d1d5db;background:#f8f9fb;table-layout:fixed">
        <tr>
            <td style="padding:.85rem 1.25rem;border-right:1px solid #d1d5db;vertical-align:top;width:20%">
                <div style="<?= soLblStyle() ?>">P.O. No.</div>
                <input type="text" name="po_number" maxlength="100" style="<?= soInpStyle() ?>">
            </td>
            <td style="padding:.85rem 1.25rem;border-right:1px solid #d1d5db;vertical-align:top;width:20%">
                <div style="<?= soLblStyle() ?>">Terms</div>
                <select name="payment_term_id" id="soTermSelect" style="<?= soInpStyle() ?>">
                    <option value="">— Select —</option>
                    <?php foreach ($payment_terms as $pt): ?>
                        <option value="<?= (int)$pt['id'] ?>" <?= (int)$pt['id'] === $defaultTermId ? 'selected' : '' ?>><?= e($pt['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td style="padding:.85rem 1.25rem;border-right:1px solid #d1d5db;vertical-align:top;width:20%">
                <div style="<?= soLblStyle() ?>">Rep</div>
                <select name="rep_id" style="<?= soInpStyle() ?>">
                    <option value="">— None —</option>
                    <?php foreach ($reps as $rep): ?>
                        <option value="<?= (int)$rep['id'] ?>"><?= e($rep['last_name'] . ', ' . $rep['first_name']) ?><?= $rep['rep_code'] ? ' (' . e($rep['rep_code']) . ')' : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td style="padding:.85rem 1.25rem;border-right:1px solid #d1d5db;vertical-align:top;width:20%">
                <div style="<?= soLblStyle() ?>">Processed By</div>
                <select name="processed_by_id" style="<?= soInpStyle() ?>">
                    <?php foreach ($users as $u): ?>
                        <option value="<?= (int)$u['id'] ?>" <?= (int)$u['id'] === (int)$currentUser['id'] ? 'selected' : '' ?>>
                            <?= e($u['first_name'] . ' ' . $u['last_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td style="padding:.85rem 1.25rem;vertical-align:top;width:20%">
                <div style="<?= soLblStyle() ?>">Ship Via</div>
                <select name="ship_via_id" style="<?= soInpStyle() ?>">
                    <option value="">— Select —</option>
                    <?php foreach ($ship_via_options as $sv): ?>
                        <option value="<?= (int)$sv['id'] ?>" <?= (int)$sv['id'] === $defaultShipViaId ? 'selected' : '' ?>><?= e($sv['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
    </table>

    <!-- ── Line Items ───────────────────────────────────────────────────── -->
    <div style="border-bottom:2px solid #d1d5db">
        <div style="display:flex;justify-content:flex-end;padding:.6rem 1rem;background:#f8f9fb;border-bottom:1px solid #d1d5db">
            <button type="button" class="btn btn--sm btn--secondary" id="addLineBtn">+ Add Line</button>
        </div>
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:.925rem">
                <colgroup>
                    <col style="width:155px">
                    <col><!-- desc -->
                    <col style="width:88px">
                    <col style="width:65px">
                    <col style="width:120px">
                    <col style="width:72px">
                    <col style="width:50px">
                    <col style="width:120px">
                    <col style="width:34px">
                </colgroup>
                <thead>
                    <tr style="background:#f8f9fb;border-bottom:1px solid #d1d5db">
                        <th style="<?= soThStyle() ?>">Item</th>
                        <th style="<?= soThStyle() ?>">Description</th>
                        <th style="<?= soThStyle() ?>;text-align:right">Quantity</th>
                        <th style="<?= soThStyle() ?>">U/M</th>
                        <th style="<?= soThStyle() ?>;text-align:right">Price Each</th>
                        <th style="<?= soThStyle() ?>;text-align:right">Disc %</th>
                        <th style="<?= soThStyle() ?>;text-align:center">Tax</th>
                        <th style="<?= soThStyle() ?>;text-align:right">Amount</th>
                        <th style="<?= soThStyle() ?>"></th>
                    </tr>
                </thead>
                <tbody id="lineBody"></tbody>
                <tfoot>
                    <tr style="border-top:1px solid #e5e7eb">
                        <td colspan="7" style="padding:.6rem .75rem;text-align:right;color:#6b7280;font-size:.9rem">Subtotal</td>
                        <td style="padding:.6rem .75rem;text-align:right;font-family:monospace;font-weight:600" id="fSubtotal">$0.00</td>
                        <td></td>
                    </tr>
                    <tr id="fDiscRow" style="display:none">
                        <td colspan="7" style="padding:.4rem .75rem;text-align:right;color:#6b7280;font-size:.9rem">Discount</td>
                        <td style="padding:.4rem .75rem;text-align:right;font-family:monospace;color:#ef4444" id="fDiscount"></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="7" style="padding:.7rem .75rem;text-align:right">
                            <label style="display:inline-flex;align-items:center;gap:.6rem;font-weight:600;color:#374151">
                                Tax
                                <select name="tax_rate_id" id="taxRateSelect"
                                    style="padding:.4rem .6rem;font-size:.875rem;border:1px solid #d1d5db;border-radius:6px;background:#fff">
                                    <option value="" data-rate="0">— None —</option>
                                    <?php foreach ($tax_rates as $tr): ?>
                                        <option value="<?= (int)$tr['id'] ?>" data-rate="<?= (float)$tr['rate'] * 100 ?>"
                                            <?= (int)$tr['id'] === $defaultTaxId ? 'selected' : '' ?>><?= e($tr['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <input type="hidden" name="tax_rate_pct" id="taxRatePct" value="<?= $defaultTaxPct ?>">
                        </td>
                        <td style="padding:.7rem .75rem;text-align:right;font-family:monospace;font-weight:600" id="fTax">$0.00</td>
                        <td></td>
                    </tr>
                    <tr style="border-top:2px solid #d1d5db">
                        <td colspan="7" style="padding:.85rem .75rem;text-align:right;font-weight:700;font-size:1.05rem">Total</td>
                        <td style="padding:.85rem .75rem;text-align:right;font-family:monospace;font-weight:700;font-size:1.1rem" id="fTotal">$0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- ── Footer: Customer Message · Internal Notes · Memo ─────────────── -->
    <div style="display:grid;grid-template-columns:300px 1fr 1fr;gap:1.5rem;padding:1.25rem 1.5rem;border-bottom:1px solid #d1d5db;align-items:start">
        <div>
            <div style="<?= soLblStyle() ?>">Customer Message</div>
            <select name="customer_message_id" style="<?= soInpStyle() ?>;margin-bottom:.85rem">
                <option value="">— None —</option>
                <?php foreach ($customer_messages as $cm): ?>
                    <option value="<?= (int)$cm['id'] ?>"><?= e($cm['message']) ?></option>
                <?php endforeach; ?>
            </select>
            <div style="<?= soLblStyle() ?>">Internal Notes</div>
            <textarea name="internal_notes" rows="3" style="<?= soInpStyle() ?>;resize:vertical"></textarea>
        </div>
        <div>
            <div style="<?= soLblStyle() ?>">Memo</div>
            <textarea name="memo" rows="5" style="<?= soInpStyle() ?>;resize:vertical"></textarea>
        </div>
        <div></div>
    </div>

    <!-- ── Actions ──────────────────────────────────────────────────────── -->
    <div style="display:flex;justify-content:flex-end;gap:.75rem;padding:1rem 1.5rem;background:#f8f9fb">
        <a href="/sales-orders" class="btn btn--secondary">Cancel</a>
        <button type="submit" name="_action" value="save_new" class="btn btn--secondary">Save &amp; New</button>
        <button type="submit" class="btn btn--primary">Save &amp; Close</button>
    </div>

</div><!-- /document -->
</form>

<?php
function soInpStyle(): string {
    return 'width:100%;padding:.62rem .75rem;font-size:.95rem;font-family:inherit;line-height:1.45;background:#fff;color:#111;border:1px solid #d1d5db;border-radius:6px;box-sizing:border-box';
}
function soLblStyle(): string {
    return 'font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.4rem';
}
function soThStyle(): string {
    return 'padding:.6rem .75rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;white-space:nowrap;text-align:left';
}
?>

<script>
(function(){
    function fmt(n){return '$'+(parseFloat(n)||0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,',');}
    function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;');}

    const INP  = 'width:100%;padding:.35rem .5rem;font-size:.9rem;font-family:inherit;background:#fff;border:1px solid #d1d5db;border-radius:5px;box-sizing:border-box';
    const INP_R= INP+';text-align:right';

    function addLine(d){
        d=d||{};
        const tr=document.createElement('tr');
        tr.className='so-line';
        tr.style.borderBottom='1px solid #e5e7eb';
        tr.innerHTML=`
            <td style="padding:.3rem .4rem;vertical-align:middle;position:relative">
                <input type="hidden" name="line_product_id[]" class="f-pid" value="${esc(d.product_id||'')}">
                <input type="text" name="line_item[]" value="${esc(d.sku||d.quickbooks_item||'')}" class="f-code" placeholder="Item" autocomplete="off" style="${INP}">
                <div class="f-drop" style="display:none;position:absolute;top:100%;left:0;min-width:440px;z-index:9999;margin-top:1px;background:#fff;border:1px solid #c8c8c8;border-radius:6px;box-shadow:0 8px 28px rgba(0,0,0,.18);max-height:300px;overflow-y:auto"></div>
            </td>
            <td style="padding:.3rem .4rem;vertical-align:middle"><input type="text" name="line_desc[]" value="${esc(d.name||d.description||'')}" class="f-desc" placeholder="Description" style="${INP}"></td>
            <td style="padding:.3rem .4rem;vertical-align:middle"><input type="number" name="line_qty[]"      value="${d.qty||1}"   class="f-qty f-calc"   step="1" min="0" style="${INP_R}"></td>
            <td style="padding:.3rem .4rem;vertical-align:middle"><input type="text"   name="line_uom[]"      value="${esc(d.uom||'')}" class="f-uom" maxlength="10" style="${INP}"></td>
            <td style="padding:.3rem .4rem;vertical-align:middle"><input type="number" name="line_price[]"    value="${d.price||''}" class="f-price f-calc" step="0.01" min="0" placeholder="0.00" style="${INP_R}"></td>
            <td style="padding:.3rem .4rem;vertical-align:middle"><input type="number" name="line_discount[]" value="${d.disc||0}"  class="f-disc f-calc"  step="any" min="0" max="100" style="${INP_R}"></td>
            <td style="padding:.3rem .4rem;vertical-align:middle;text-align:center"><input type="checkbox" name="line_taxable[]" value="1" class="f-tax f-calc" ${d.taxable?'checked':''}></td>
            <td style="padding:.3rem .75rem;vertical-align:middle;text-align:right;font-family:monospace;font-weight:600" class="f-total">$0.00</td>
            <td style="padding:.3rem .4rem;vertical-align:middle"><button type="button" class="so-del-btn" title="Remove" style="background:none;border:none;font-size:1.3rem;cursor:pointer;color:#9ca3af;line-height:1;padding:0 .3rem;border-radius:4px">&times;</button></td>`;
        document.getElementById('lineBody').appendChild(tr);
        recalcRow(tr); bindRow(tr);
    }

    function recalcRow(tr){
        const q=parseFloat(tr.querySelector('.f-qty').value)||0;
        const p=parseFloat(tr.querySelector('.f-price').value)||0;
        const d=parseFloat(tr.querySelector('.f-disc').value)||0;
        tr.querySelector('.f-total').textContent=fmt(q*p*(1-d/100));
    }
    function recalcAll(){
        let sub=0,disc=0,taxable=0;
        document.querySelectorAll('.so-line').forEach(tr=>{
            const q=parseFloat(tr.querySelector('.f-qty').value)||0;
            const p=parseFloat(tr.querySelector('.f-price').value)||0;
            const d=parseFloat(tr.querySelector('.f-disc').value)||0;
            const gross=q*p,da=gross*d/100,net=gross-da;
            sub+=gross;disc+=da;
            if(tr.querySelector('.f-tax').checked) taxable+=net;
        });
        const rate=parseFloat(document.getElementById('taxRatePct').value)||0;
        const taxAmt=taxable*rate/100;
        document.getElementById('fSubtotal').textContent=fmt(sub);
        const dr=document.getElementById('fDiscRow');
        dr.style.display=disc>0?'':'none';
        if(disc>0) document.getElementById('fDiscount').textContent='−'+fmt(disc);
        document.getElementById('fTax').textContent=fmt(taxAmt);
        document.getElementById('fTotal').textContent=fmt(sub-disc+taxAmt);
    }
    function bindRow(tr){
        tr.querySelectorAll('.f-calc').forEach(el=>{
            el.addEventListener('input',()=>{recalcRow(tr);recalcAll();});
            el.addEventListener('change',()=>{recalcRow(tr);recalcAll();});
        });
        tr.querySelector('.so-del-btn').addEventListener('click',()=>{tr.remove();recalcAll();});
        const codeEl=tr.querySelector('.f-code'),dropEl=tr.querySelector('.f-drop');
        let timer;
        codeEl.addEventListener('input',function(){
            clearTimeout(timer);
            const q=this.value.trim();
            if(!q){dropEl.style.display='none';return;}
            timer=setTimeout(()=>{
                fetch('/products/autocomplete?q='+encodeURIComponent(q))
                    .then(r=>r.json()).then(items=>showDrop(items,dropEl,tr)).catch(()=>{});
            },220);
        });
        codeEl.addEventListener('blur',()=>setTimeout(()=>{dropEl.style.display='none';},180));
    }
    function showDrop(items,dropEl,tr){
        dropEl.innerHTML='';
        if(!items||!items.length){dropEl.style.display='none';return;}
        items.forEach(p=>{
            const d=document.createElement('div');
            d.style.cssText='display:flex;align-items:baseline;gap:.75rem;padding:.6rem 1rem;cursor:pointer;border-bottom:1px solid #eee;font-size:.9rem;color:#111';
            d.innerHTML=`<span style="font-weight:700;font-family:monospace;color:#0A3D91;min-width:110px;flex-shrink:0">${esc(p.sku||p.quickbooks_item)}</span>`+
                `<span style="flex:1;color:#222">${esc(p.name||'')}</span>`+
                (p.price?`<span style="margin-left:auto;color:#6b7280;font-size:.85rem">${fmt(p.price)}</span>`:'');
            d.addEventListener('mouseover',()=>d.style.background='#f0f4ff');
            d.addEventListener('mouseout', ()=>d.style.background='');
            d.addEventListener('mousedown',e=>{
                e.preventDefault();
                tr.querySelector('.f-code').value=p.sku||p.quickbooks_item;
                tr.querySelector('.f-pid').value=p.id;
                tr.querySelector('.f-desc').value=p.name||'';
                if(p.price) tr.querySelector('.f-price').value=parseFloat(p.price).toFixed(2);
                dropEl.style.display='none';
                recalcRow(tr);recalcAll();
                tr.querySelector('.f-qty').focus();
            });
            dropEl.appendChild(d);
        });
        dropEl.style.display='block';
    }

    document.getElementById('taxRateSelect').addEventListener('change',function(){
        document.getElementById('taxRatePct').value=this.options[this.selectedIndex].dataset.rate||0;
        recalcAll();
    });
    document.getElementById('addLineBtn').addEventListener('click',()=>addLine());

    /* Same-as-billing */
    const sameCb=document.getElementById('sameAsBilling');
    function toggleShip(){
        const s=sameCb.checked;
        document.getElementById('shipEditBox').style.display=s?'none':'block';
        document.getElementById('shipSameBox').style.display=s?'block':'none';
    }
    sameCb.addEventListener('change',toggleShip);
    toggleShip();

    /* Customer autocomplete */
    const csearch=document.getElementById('customerSearch');
    const cid=document.getElementById('customerId');
    const csugg=document.getElementById('customerSuggestions');
    const csel=document.getElementById('customerSelected');
    const billBox=document.getElementById('billToBox');
    let ctimer;

    csearch.addEventListener('input',function(){
        clearTimeout(ctimer);
        const q=this.value.trim();
        if(q.length<2){csugg.style.display='none';return;}
        ctimer=setTimeout(()=>{
            fetch('/customers/autocomplete?q='+encodeURIComponent(q))
                .then(r=>r.json()).then(items=>{
                    csugg.innerHTML='';
                    if(!items.length){csugg.style.display='none';return;}
                    items.slice(0,12).forEach(c=>{
                        const d=document.createElement('div');
                        d.style.cssText='padding:.65rem 1rem;cursor:pointer;font-size:.95rem;border-bottom:1px solid #eee;color:#111';
                        d.textContent=c.company_name;
                        d.addEventListener('mouseover',()=>d.style.background='#f0f4ff');
                        d.addEventListener('mouseout', ()=>d.style.background='');
                        d.addEventListener('mousedown',e=>{e.preventDefault();selectCustomer(c);});
                        csugg.appendChild(d);
                    });
                    csugg.style.display='block';
                }).catch(()=>{});
        },250);
    });

    function selectCustomer(c){
        cid.value=c.id;
        csearch.style.display='none';
        csugg.style.display='none';
        csel.style.display='flex';
        csel.innerHTML=`<strong>${esc(c.company_name)}</strong><button type="button" class="btn btn--xs btn--secondary" id="chgCust">Change</button>`;
        document.getElementById('chgCust').addEventListener('click',()=>{
            cid.value='';csearch.value='';
            csearch.style.display='';csel.style.display='none';
            billBox.innerHTML='<span style="color:#9ca3af">Select a customer above</span>';
            csearch.focus();
        });
        fetch('/customers/'+c.id+'/json').then(r=>r.json()).then(cd=>{
            if(!cd) return;
            let h='<strong>'+esc(cd.company_name||'')+'</strong>';
            if(cd.bill_address_1) h+='<br>'+esc(cd.bill_address_1);
            if(cd.bill_address_2) h+='<br>'+esc(cd.bill_address_2);
            if(cd.bill_city) h+='<br>'+esc(cd.bill_city)+(cd.bill_state?', '+esc(cd.bill_state):'')+' '+esc(cd.bill_zip||'');
            if(cd.phone) h+='<br><span style="color:#9ca3af">'+esc(cd.phone)+'</span>';
            billBox.innerHTML=h;
            if(cd.payment_term_id){
                const sel=document.querySelector('[name="payment_term_id"]');
                if(sel) sel.value=cd.payment_term_id;
            }
        }).catch(()=>{});
    }

    const preId=new URLSearchParams(window.location.search).get('customer_id');
    if(preId){
        fetch('/customers/'+preId+'/json').then(r=>r.json())
            .then(cd=>{if(cd&&cd.id) selectCustomer({id:cd.id,company_name:cd.company_name});}).catch(()=>{});
    }
    document.addEventListener('click',e=>{
        if(!csearch.contains(e.target)&&!csugg.contains(e.target)) csugg.style.display='none';
    });

    addLine();addLine();addLine();
    recalcAll();
})();
</script>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';

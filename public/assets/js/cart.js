/*
 * The website cart, as the stencil shop had it: kept in this browser (localStorage), priced
 * by the server every time it is shown. Nothing here decides a price - /cart/price,
 * /cart/shipping and /checkout do, from the catalog and the designers.
 *
 * An item is what was chosen, never what it costs:
 *   {kind: 'stock', sku, qty}
 *   {kind: 'text', text, size, thickness, qty}
 *   {kind: 'endzone', text, size, style, format, fill, line, qty}
 *   {kind: 'midfield', logo, field, width, qty}
 *
 * Signed in (phase 4): the server prices at the account's tier, so a distributor sees
 * wholesale with the list price beside it, can save the cart as a quote, and can order on
 * account with a PO number instead of paying by card.
 */
(function () {
  'use strict';

  var KEY = 'ussc_cart', SHIP = 'ussc_cart_ship', QUOTE = 'ussc_cart_quote', MAX = 500;
  var $ = function (s, el) { return (el || document).querySelector(s); };
  var esc = function (s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  };
  var money = function (n) { return '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };

  function load() { try { return JSON.parse(localStorage.getItem(KEY)) || []; } catch (e) { return []; } }
  function save(items) {
    try { localStorage.setItem(KEY, JSON.stringify(items)); } catch (e) { /* private mode */ }
    badge();
  }
  // The saved quote this cart was opened from, so ordering it marks the quote ordered.
  // Any change to the cart makes it a different order, and forgets the quote.
  function quoteId() { try { return +sessionStorage.getItem(QUOTE) || 0; } catch (e) { return 0; } }
  function setQuote(id) { try { if (id) sessionStorage.setItem(QUOTE, String(id)); else sessionStorage.removeItem(QUOTE); } catch (e) { /* */ } }
  function edit(items) { setQuote(0); save(items); }
  function badge() {
    var n = load().reduce(function (s, i) { return s + (+i.qty || 0); }, 0);
    document.querySelectorAll('[data-cart-count]').forEach(function (el) { el.textContent = n ? '(' + n + ')' : ''; });
  }
  function same(a, b) {
    var keys = ['kind', 'sku', 'text', 'size', 'thickness', 'style', 'format', 'logo', 'field', 'width'];
    return keys.every(function (k) { return String(a[k] || '') === String(b[k] || ''); });
  }

  function add(item, qty) {
    var items = load(), n = Math.max(1, Math.min(MAX, Math.floor(qty) || 1));
    var found = items.filter(function (i) { return same(i, item); })[0];
    if (found) found.qty = Math.min(MAX, found.qty + n);
    else items.push(Object.assign({}, item, { qty: n }));
    edit(items);
    toast('Added to cart. <a href="/cart">View cart →</a>');
  }

  function toast(html) {
    var t = document.createElement('div');
    t.innerHTML = html;
    t.style.cssText = 'position:fixed;right:1rem;bottom:1rem;z-index:50;background:#222b59;color:#fff;padding:.8rem 1rem;border-radius:8px;font-size:.92rem;box-shadow:0 6px 24px #0003';
    var a = t.querySelector('a'); if (a) a.style.cssText = 'color:#fff;font-weight:700;margin-left:.4rem';
    document.body.appendChild(t);
    setTimeout(function () { t.remove(); }, 4500);
  }

  function post(url, data) {
    return fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(data) })
      .then(function (r) { return r.json().then(function (j) { if (!r.ok) throw new Error(j.error || 'Something went wrong. Please try again.'); return j; }); });
  }

  // ── Add-to-cart buttons ──────────────────────────────────────────────

  // A stock stencil on its product page.
  document.querySelectorAll('[data-add-sku]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var qty = document.getElementById(btn.dataset.qtyInput || '');
      add({ kind: 'stock', sku: btn.dataset.addSku }, qty ? +qty.value : 1);
    });
  });

  // The designers: the current design is in the request form's hidden fields.
  document.querySelectorAll('[data-add-design]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var form = document.getElementById('dz-request-form');
      if (!form || btn.disabled) return;
      var v = function (k) { var f = form.querySelector('input[name="' + k + '"]'); return f ? f.value : ''; };
      var kind = v('kind'), item = { kind: kind };
      if (kind === 'text') { item.text = v('text'); item.size = v('size'); item.thickness = v('thickness'); }
      if (kind === 'endzone') { ['text', 'size', 'style', 'format', 'fill', 'line'].forEach(function (k) { item[k] = v(k); }); }
      if (kind === 'midfield') { item.logo = v('logo'); item.field = v('field'); item.width = v('width'); }
      add(item, 1);
    });
  });

  // ── The cart page ────────────────────────────────────────────────────

  function cartPage(root) {
    var enabled = root.dataset.enabled === '1', trade = root.dataset.trade === '1', signedIn = root.dataset.signedIn === '1';
    var ship = {}, options = [], priced = null;
    try { ship = JSON.parse(localStorage.getItem(SHIP)) || {}; } catch (e) { /* private mode */ }
    var saveShip = function () { try { localStorage.setItem(SHIP, JSON.stringify(ship)); } catch (e) { /* */ } };

    function render() {
      var items = load();
      if (!items.length) {
        root.innerHTML = '<div class="note">Your cart is empty.</div><p><a class="b b-p" href="/shop/stencils">Shop stencils</a></p>';
        return;
      }
      root.innerHTML = '<p class="dz-sub">Pricing your cart…</p>';
      post('/cart/price', { items: items }).then(function (res) {
        priced = res;
        draw(items, res);
        if (ship.zip) rates();
      }).catch(function (e) {
        root.innerHTML = '<div class="dz-alert">' + esc(e.message) + '</div><p><button type="button" class="b b-s" id="cart-clear">Empty the cart</button></p>';
        $('#cart-clear').addEventListener('click', function () { edit([]); render(); });
      });
    }

    function draw(items, res) {
      var rows = res.lines.map(function (l, i) {
        return '<tr style="border-bottom:1px solid var(--line)">' +
          '<td style="padding:.8rem .6rem .8rem 0;width:72px;vertical-align:top">' + (l.image ? '<img src="' + esc(l.image) + '" alt="" style="width:64px;height:64px;object-fit:contain;border:1px solid var(--line);border-radius:6px;background:#fff">' : '') + '</td>' +
          '<td style="padding:.8rem .6rem;vertical-align:top"><a href="' + esc(l.url || '#') + '" style="font-weight:700">' + esc(l.name) + '</a>' +
          '<div class="dz-sub">' + esc(l.detail || '') + '</div><div class="dz-sub">' + (l.kind === 'stock' ? 'SKU ' + esc(l.sku) + ' · ' : '') + money(l.unit) + ' each' +
          (l.retail && l.retail > l.unit ? ' <s style="margin-left:.3rem">' + money(l.retail) + '</s>' : '') + '</div></td>' +
          '<td style="padding:.8rem .6rem;vertical-align:top;white-space:nowrap"><input type="number" min="1" max="500" value="' + l.qty + '" data-line="' + i + '" aria-label="Quantity" style="width:4.5rem;padding:.4rem;border:1px solid var(--line);border-radius:6px;font:inherit">' +
          '<div><button type="button" data-remove="' + i + '" style="appearance:none;border:0;background:none;color:var(--blue);padding:.3rem 0;cursor:pointer;font:inherit;font-size:.82rem">Remove</button></div></td>' +
          '<td style="padding:.8rem 0 .8rem .6rem;vertical-align:top;text-align:right;font-weight:700">' + money(l.unit * l.qty) + '</td></tr>';
      }).join('');

      root.innerHTML =
        '<table style="width:100%;border-collapse:collapse"><tr>' +
        '<td style="vertical-align:top;padding-right:2rem"><table style="width:100%;border-collapse:collapse">' + rows + '</table>' +
        '<p style="margin-top:1.2rem"><a href="/shop/stencils">← Continue shopping</a></p></td>' +
        '<td style="vertical-align:top;width:22rem" class="cart-side"><div class="dz-card">' +
        '<h2 style="margin:0 0 1rem;font-size:1.15rem">Order summary</h2>' +
        (trade ? '<p class="dz-sub" style="margin:-.6rem 0 .8rem">' + esc(res.tier) + ' prices</p>' : '') +
        '<table style="width:100%;border-collapse:collapse;font-size:.95rem">' +
        '<tr><td>Subtotal</td><td style="text-align:right">' + money(res.subtotal) + '</td></tr>' +
        '<tr><td>Shipping &amp; handling</td><td style="text-align:right" id="c-ship" class="dz-sub">Enter ZIP</td></tr>' +
        '<tr><td style="padding-top:.6rem;font-weight:800">Total</td><td style="padding-top:.6rem;text-align:right;font-weight:800" id="c-total">' + money(res.subtotal) + '</td></tr></table>' +
        '<label class="dz-label" for="c-zip" style="margin-top:1.2rem">Ship to ZIP code</label>' +
        '<table style="width:100%;border-collapse:collapse"><tr><td style="padding-right:.4rem"><input id="c-zip" inputmode="numeric" maxlength="10" placeholder="e.g. 30303" autocomplete="postal-code" value="' + esc(ship.zip || '') + '" style="width:100%;box-sizing:border-box;padding:.55rem;border:1px solid var(--line);border-radius:6px;font:inherit"></td>' +
        '<td style="width:1%"><button type="button" class="b b-s" id="c-rates">Get rates</button></td></tr></table>' +
        '<label style="display:block;margin-top:.5rem;font-size:.88rem"><input type="checkbox" id="c-res"' + (ship.residential ? ' checked' : '') + '> Delivering to a home</label>' +
        '<div id="c-options" style="margin-top:.6rem"></div>' +
        '<button type="button" class="dz-btn p" id="c-checkout" disabled>Secure checkout</button>' +
        '<p class="dz-sub" style="margin:.5rem 0 0">Card payment is taken securely by Square. Sales tax is not added.</p>' +
        '<div id="c-msg">' + (enabled ? '' : '<div class="dz-alert">Online checkout is being set up. Please call or email us to place your order.</div>') + '</div>' +
        '</div>' + (trade ? termsBox() : '') +
        (trade ? '<p style="margin-top:.8rem"><button type="button" class="b b-s" id="c-save-quote" style="width:100%">Save this cart as a quote</button></p>'
               : signedIn ? '' : '<p class="dz-sub" style="margin-top:.8rem"><a href="/account/sign-in?next=/cart">Sign in</a> for saved designs, and distributor pricing on approved accounts.</p>') +
        '</td></tr></table>';

      root.querySelectorAll('input[data-line]').forEach(function (el) {
        el.addEventListener('change', function () {
          var items = load(); items[+el.dataset.line].qty = Math.max(1, Math.min(MAX, Math.floor(+el.value) || 1)); edit(items); render();
        });
      });
      root.querySelectorAll('[data-remove]').forEach(function (b) {
        b.addEventListener('click', function () { var items = load(); items.splice(+b.dataset.remove, 1); edit(items); render(); });
      });
      if (trade) {
        $('#c-save-quote').addEventListener('click', saveQuote);
        $('#t-place').addEventListener('click', placeTerms);
      }
      $('#c-rates').addEventListener('click', rates);
      $('#c-zip').addEventListener('keydown', function (e) { if (e.key === 'Enter') rates(); });
      $('#c-res').addEventListener('change', function () { if ($('#c-zip').value.trim()) rates(); });
      $('#c-checkout').addEventListener('click', checkout);
    }

    function totals() {
      var chosen = options.filter(function (o) { return o.id === ship.service; })[0];
      $('#c-ship').textContent = chosen ? money(chosen.price) : 'Enter ZIP';
      $('#c-total').textContent = money(priced.subtotal + (chosen ? chosen.price : 0));
      $('#c-checkout').disabled = !enabled || !chosen;
      if (trade) $('#t-place').disabled = !chosen;
    }

    // A distributor's order on account: PO and ship-to here; shipping is priced to the
    // ship-to ZIP on the server, which must match the ZIP the rates above were for.
    function termsBox() {
      var f = function (id, label, attrs) {
        return '<label class="dz-label" for="' + id + '" style="margin-top:.6rem">' + label + '</label><input id="' + id + '" ' + (attrs || '') +
          ' style="width:100%;box-sizing:border-box;padding:.5rem;border:1px solid var(--line);border-radius:6px;font:inherit">';
      };
      return '<div class="dz-card" style="margin-top:1rem"><h2 style="margin:0 0 .4rem;font-size:1.05rem">Or order on account</h2>' +
        '<p class="dz-sub" style="margin:0 0 .4rem">No payment now. We invoice it on your terms. Choose shipping above first.</p>' +
        f('t-po', 'Your PO number', 'maxlength="50"') + f('t-name', 'Ship to (name / attention)', 'maxlength="100" autocomplete="name"') +
        f('t-company', 'Company', 'maxlength="150" autocomplete="organization"') + f('t-a1', 'Street address', 'maxlength="150" autocomplete="address-line1"') +
        f('t-a2', 'Address line 2', 'maxlength="150" autocomplete="address-line2"') +
        '<table style="width:100%;border-collapse:collapse"><tr><td style="padding-right:.4rem">' + f('t-city', 'City', 'maxlength="80" autocomplete="address-level2"') +
        '</td><td style="width:4.5rem">' + f('t-state', 'State', 'maxlength="2" autocomplete="address-level1"') + '</td></tr></table>' +
        f('t-phone', 'Phone', 'maxlength="40" autocomplete="tel"') +
        '<p class="dz-sub" style="margin:.5rem 0 0">Ships to the ZIP code above.</p>' +
        '<button type="button" class="dz-btn p" id="t-place" disabled>Place order on account</button><div id="t-msg"></div></div>';
    }

    function placeTerms() {
      var btn = $('#t-place'), v = function (id) { return $('#' + id).value.trim(); };
      btn.disabled = true; btn.textContent = 'Placing your order…';
      post('/checkout/terms', {
        items: load(), service: ship.service, po: v('t-po'), quote: quoteId() || null,
        ship: { name: v('t-name'), company: v('t-company'), address1: v('t-a1'), address2: v('t-a2'), city: v('t-city'),
                state: v('t-state'), zip: ship.zip, phone: v('t-phone'), residential: ship.residential }
      }).then(function (res) { window.location.href = res.url; }).catch(function (e) {
        $('#t-msg').innerHTML = '<div class="dz-alert">' + esc(e.message) + '</div>';
        btn.disabled = false; btn.textContent = 'Place order on account';
      });
    }

    function saveQuote() {
      var name = window.prompt('Name this quote (for example, the job or school):', '');
      if (name === null) return;
      post('/account/quotes', { name: name, items: load() }).then(function (res) {
        toast('Quote saved. <a href="' + esc(res.url) + '">View it →</a>');
      }).catch(function (e) { $('#c-msg').innerHTML = '<div class="dz-alert">' + esc(e.message) + '</div>'; });
    }

    function rates() {
      ship.zip = $('#c-zip').value.trim();
      ship.residential = $('#c-res').checked;
      saveShip();
      if (!ship.zip) return;
      $('#c-options').innerHTML = '<p class="dz-sub">Getting FedEx rates…</p>';
      post('/cart/shipping', { items: load(), zip: ship.zip, residential: ship.residential }).then(function (res) {
        options = res.options;
        if (!options.some(function (o) { return o.id === ship.service; })) ship.service = options[0].id;
        saveShip();
        $('#c-options').innerHTML = options.map(function (o) {
          return '<label style="display:flex;gap:.5rem;align-items:center;padding:.4rem 0;font-size:.9rem"><input type="radio" name="c-ship" value="' + esc(o.id) + '"' + (o.id === ship.service ? ' checked' : '') + '>' +
            '<span style="flex:1">' + esc(o.name) + '</span><strong>' + money(o.price) + '</strong></label>';
        }).join('');
        root.querySelectorAll('input[name="c-ship"]').forEach(function (r) { r.addEventListener('change', function () { ship.service = r.value; saveShip(); totals(); }); });
        totals();
      }).catch(function (e) {
        options = [];
        $('#c-options').innerHTML = '<div class="dz-alert">' + esc(e.message) + '</div>';
        totals();
      });
    }

    function checkout() {
      var btn = $('#c-checkout');
      btn.disabled = true;
      btn.textContent = 'Connecting to Square…';
      post('/checkout', { items: load(), zip: ship.zip, residential: ship.residential, service: ship.service }).then(function (res) {
        window.location.href = res.url;
      }).catch(function (e) {
        $('#c-msg').innerHTML = '<div class="dz-alert">' + esc(e.message) + '</div>';
        btn.disabled = false;
        btn.textContent = 'Secure checkout';
      });
    }

    render();
  }

  var page = document.getElementById('cart-page');
  if (page) cartPage(page);

  // The thank-you page, once the order is made: the cart has been bought.
  if (document.querySelector('[data-cart-bought]')) edit([]);

  // A saved quote's page: put its items in the cart and go there.
  document.querySelectorAll('[data-load-quote]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var items = [];
      try { items = JSON.parse(btn.dataset.items) || []; } catch (e) { /* */ }
      save(items); setQuote(+btn.dataset.loadQuote); window.location.href = '/cart';
    });
  });

  badge();
  // The header's account link: "My account" for a signed-in customer or staff member.
  // The cookie answers at once for customers; the server answers for staff too.
  var links = document.querySelectorAll('[data-account-link]');
  var mine = function () { links.forEach(function (a) { a.textContent = 'My account'; }); };
  if (/(?:^|;\s*)ussc_signed_in=1/.test(document.cookie)) mine();
  else if (links.length) {
    fetch('/account/status', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); }).then(function (j) { if (j.state !== 'none') mine(); }).catch(function () { /* */ });
  }
  window.usscCart = { add: add, load: load };
}());

/*
 * The online stencil designers: custom endzone lettering and custom midfield logos.
 *
 * The field drawings (endzoneFieldSvg, endzoneStencilSvg, midfieldFieldSvg) are the stencil
 * shop's, carried over so a design looks the same here as it did there. Prices and letter
 * outlines always come from the server (DesignerController); nothing here is trusted for a
 * price.
 *
 * A design ends in "Request this stencil": the form under the designer carries the
 * current design in hidden fields, and the server prices it again before it becomes a lead.
 *
 * Accounts (phase 4): "Save this design" keeps those same fields in the customer's account,
 * and ?design=ID reopens one (the page carries it as data-saved). A signed-in distributor's
 * quotes include yourPrice, shown under the list price.
 */
(function () {
  'use strict';

  var $ = function (s, el) { return (el || document).querySelector(s); };
  var esc = function (s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  };
  var feet = function (n) { return Math.round(n * 10) / 10 + "'"; };
  var money = function (n) { return '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
  var debounce = function (fn, ms) { var t; return function () { clearTimeout(t); t = setTimeout(fn, ms); }; };

  function post(url, data) {
    var body = new URLSearchParams();
    Object.keys(data).forEach(function (k) { body.append(k, data[k]); });
    return fetch(url, { method: 'POST', body: body, headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json().then(function (j) { if (!r.ok) throw new Error(j.error || 'Something went wrong. Please try again.'); return j; }); });
  }

  function chipRow(el, items, current, onPick) {
    el.innerHTML = items.map(function (it) {
      return '<button type="button" class="dz-chip" data-v="' + esc(it[0]) + '" aria-pressed="' + (it[0] === current) + '">' +
        esc(it[1]) + (it[2] ? '<small>' + esc(it[2]) + '</small>' : '') + '</button>';
    }).join('');
    el.querySelectorAll('button').forEach(function (b) {
      b.addEventListener('click', function () {
        el.querySelectorAll('button').forEach(function (x) { x.setAttribute('aria-pressed', x === b); });
        onPick(b.dataset.v);
      });
    });
  }

  function specs(el, rows) {
    el.innerHTML = rows.map(function (r) { return '<div><span>' + esc(r[0]) + '</span><strong>' + esc(r[1]) + '</strong></div>'; }).join('');
  }

  /** The proof link and the request form's hidden fields, from the current design. */
  function setDesign(fields, proofBtn) {
    var form = $('#dz-request-form');
    if (form) {
      Object.keys(fields).forEach(function (k) {
        var input = form.querySelector('input[name="' + k + '"]');
        if (input) input.value = fields[k];
      });
    }
    if (proofBtn) {
      proofBtn.dataset.query = new URLSearchParams(fields).toString();
      proofBtn.disabled = false;
    }
    var send = $('#dz-send');
    if (send) send.disabled = false;
    document.querySelectorAll('[data-add-design],[data-save-design]').forEach(function (b) { b.disabled = false; });
  }

  /** A distributor's own price, under the list price. Empty for everyone else. */
  function yourPrice(priceEl, q) {
    var el = priceEl.nextElementSibling && priceEl.nextElementSibling.hasAttribute('data-your-price') ? priceEl.nextElementSibling : null;
    if (!el) { el = document.createElement('div'); el.setAttribute('data-your-price', ''); el.className = 'dz-sub'; el.style.cssText = 'font-weight:700;color:var(--navy)'; priceEl.parentNode.insertBefore(el, priceEl.nextSibling); }
    el.textContent = q && q.yourPrice != null ? 'Your distributor price: ' + money(q.yourPrice) : '';
  }

  /** The design this page was opened with (?design=ID), or null. */
  function saved(root) {
    try { return JSON.parse(root.dataset.saved || 'null'); } catch (e) { return null; }
  }

  var savedId = 0, savedName = '';
  document.querySelectorAll('[data-save-design]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (btn.disabled) return;
      if (btn.dataset.signedIn !== '1') {
        window.location.href = '/account/sign-in?next=' + encodeURIComponent(location.pathname + location.search);
        return;
      }
      var form = $('#dz-request-form'), data = {};
      if (!form) return;
      form.querySelectorAll('input[type="hidden"]').forEach(function (f) {
        if (['kind', 'text', 'size', 'thickness', 'style', 'format', 'fill', 'line', 'logo', 'field', 'width'].indexOf(f.name) >= 0) data[f.name] = f.value;
      });
      var name = window.prompt('Name this design:', savedName || data.text || 'My design');
      if (name === null) return;
      btn.disabled = true;
      fetch('/account/designs', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ id: savedId || null, kind: data.kind, name: name, data: data }) })
        .then(function (r) { return r.json().then(function (j) { if (!r.ok) throw new Error(j.error || 'Could not save.'); return j; }); })
        .then(function (j) {
          savedId = j.id; savedName = name;
          btn.textContent = 'Saved ✓';
          setTimeout(function () { btn.textContent = 'Save this design'; btn.disabled = false; }, 2500);
        })
        .catch(function (e) { window.alert(e.message); btn.disabled = false; });
    });
  });

  function clearDesign(proofBtn) {
    if (proofBtn) proofBtn.disabled = true;
    var send = $('#dz-send');
    if (send) send.disabled = true;
    document.querySelectorAll('[data-add-design],[data-save-design]').forEach(function (b) { b.disabled = true; });
  }

  function bindProof(btn) {
    if (!btn) return;
    btn.addEventListener('click', function () {
      if (btn.disabled) return;
      var who = new URLSearchParams(btn.dataset.query || '');
      ['name', 'org', 'email', 'phone'].forEach(function (k) {
        var f = $('#dz-request-form input[name="' + k + '"]');
        if (f && f.value.trim()) who.set(k, f.value.trim());
      });
      window.open('/custom/proof?' + who.toString(), '_blank', 'noopener');
    });
  }

  // ============================================================ endzone

  function endzoneFieldSvg(q, fill, line) {
    var W = q.endZone.width, H = q.endZone.height, m = 8, lw = 0.6;
    var ox = (W - q.lettering.width) / 2, oy = (H - q.lettering.height) / 2;
    var stripes = '';
    for (var i = 0; i < 8; i++) stripes += '<rect x="' + (-m + i * 2 * (W + 2 * m) / 16) + '" y="' + (-m) + '" width="' + ((W + 2 * m) / 16) + '" height="' + (H + 2 * m) + '" fill="#4b8f37" opacity=".45"/>';
    var letters = q.style === 'outline'
      ? '<path d="' + q.fill + '" fill="' + fill + '" fill-rule="evenodd"/><path d="' + q.path + '" fill="' + line + '" fill-rule="evenodd"/>'
      : '<path d="' + q.path + '" fill="' + fill + '" fill-rule="evenodd"/>';
    var dimY = oy + q.lettering.height + 2.2;
    return '<svg class="dz-svg" viewBox="' + (-m) + ' ' + (-m) + ' ' + (W + 2 * m) + ' ' + (H + 2 * m + 4) + '" role="img" aria-label="' + esc(q.text) + ' painted on a ' + W + "' by " + H + "' end zone\">" +
      '<rect x="' + (-m) + '" y="' + (-m) + '" width="' + (W + 2 * m) + '" height="' + (H + 2 * m + 4) + '" fill="#3b7a2a"/>' + stripes +
      '<rect x="0" y="0" width="' + W + '" height="' + H + '" fill="none" stroke="#f6f6f2" stroke-width="' + lw + '"/>' +
      '<g transform="translate(' + ox + ' ' + oy + ')">' + letters + '</g>' +
      '<g stroke="#f6f6f2" stroke-width=".25" opacity=".9"><path d="M' + ox + ' ' + dimY + 'H' + (ox + q.lettering.width) + 'M' + ox + ' ' + (dimY - 0.8) + 'V' + (dimY + 0.8) + 'M' + (ox + q.lettering.width) + ' ' + (dimY - 0.8) + 'V' + (dimY + 0.8) + '"/></g>' +
      '<text x="' + (W / 2) + '" y="' + (dimY + 3.2) + '" font-size="2.6" text-anchor="middle" fill="#f6f6f2" font-weight="700">' + feet(q.lettering.width) + ' × ' + feet(q.lettering.height) + ' lettering</text>' +
      '<text x="0" y="' + (-m / 2 + 1) + '" font-size="2.4" fill="#f6f6f2" opacity=".85">End zone ' + W + "' × " + H + "'</text></svg>";
  }

  function endzoneStencilSvg(q) {
    var b = q.border, h = q.letterHeight;
    if (q.format === 'letters') {
      var x = 0, gap = 1.5, sheets = '';
      q.glyphs.forEach(function (g) {
        var w = g.width + 2 * b;
        sheets += '<g transform="translate(' + x + ' 0)"><rect width="' + w + '" height="' + (h + 2 * b) + '" rx=".4" fill="#fff" stroke="#c9cfcb" stroke-width=".15"/>' +
          '<path d="' + g.d + '" transform="translate(' + (b - g.x) + ' ' + b + ')" fill="#2b2f2c" fill-rule="evenodd"/></g>';
        x += w + gap;
      });
      var tw = x - gap, th = h + 2 * b;
      return '<svg viewBox="-1 -1 ' + (tw + 2) + ' ' + (th + 5) + '" role="img" aria-label="' + q.letters + ' letter stencils">' + sheets +
        '<text x="0" y="' + (th + 3.4) + '" font-size="' + Math.max(1.6, tw / 60) + '" fill="#646b66">' + q.letters + ' letter stencils, each ' + feet(h + 2 * b) + ' tall</text></svg>' +
        '<p class="dz-cap">Each letter is its own stencil, so you can space and line them up on the field.</p>';
    }
    var sw = q.sheet.width, sh = q.sheet.height;
    var ox = (sw - q.lettering.width) / 2, oy = (sh - h) / 2;
    var owner = [];
    q.sectionPanels.forEach(function (n, s) { for (var i = 0; i < n; i++) owner.push(s); });
    var tints = ['#e3f1df', '#fff3d6', '#e2ecf8'], inks = ['#2f7d32', '#9a6a00', '#2c5d9b'];
    var pw = sw / q.along, ph = sh / q.tall;
    var panels = owner.map(function (s, i) {
      return '<rect x="' + (Math.floor(i / q.tall) * pw) + '" y="' + ((i % q.tall) * ph) + '" width="' + pw + '" height="' + ph + '" fill="' + tints[s] + '"/>';
    }).join('');
    var seams = '';
    for (var a = 0; a < q.along - 1; a++) seams += 'M' + ((a + 1) * pw) + ' 0V' + sh;
    for (var r = 0; r < q.tall - 1; r++) seams += 'M0 ' + ((r + 1) * ph) + 'H' + sw;
    var fs = Math.max(1.6, sw / 55), start = 0, labels = '';
    q.sectionPanels.forEach(function (n, s) {
      var first = start, last = start + n - 1;
      start += n;
      var x1 = Math.floor(first / q.tall) * pw, x2 = (Math.floor(last / q.tall) + 1) * pw;
      labels += '<text x="' + ((x1 + x2) / 2) + '" y="' + (sh + fs * 1.5) + '" font-size="' + fs + '" text-anchor="middle" fill="' + inks[s] + '" font-weight="700">Section ' + (s + 1) + ' · ' + n + ' panels</text>';
    });
    return '<svg viewBox="-1 -1.5 ' + (sw + 2) + ' ' + (sh + fs * 2.4 + 1.5) + '" role="img" aria-label="One large stencil ' + feet(sw) + ' by ' + feet(sh) + '">' +
      panels + '<rect width="' + sw + '" height="' + sh + '" rx=".4" fill="none" stroke="#c9cfcb" stroke-width=".15"/>' +
      '<path d="' + seams + '" stroke="#c3cac5" stroke-width=".12" fill="none"/>' +
      '<path d="' + q.path + '" transform="translate(' + ox + ' ' + oy + ')" fill="#2b2f2c" fill-rule="evenodd"/>' + labels + '</svg>' +
      '<p class="dz-cap">' + feet(sw) + ' × ' + feet(sh) + ' stencil · ' + q.panels + ' panels, each ' + feet(q.panelWidth) + ' × ' + feet(q.panelHeight) +
      (q.tall > 1 ? ' (top and bottom)' : '') + ' · ships in ' + q.sections + ' section' + (q.sections > 1 ? 's' : '') + '</p>';
  }

  function endzone(root) {
    var sizes = JSON.parse(root.dataset.sizes || '[]');
    var colors = JSON.parse(root.dataset.colors || '[]');
    var text = $('#ez-text'), proofBtn = $('#dz-proof');
    var state = { style: 'outline', size: sizes.length ? sizes[0].key : '', format: 'stencil', fill: colors[0][0], line: colors[7][0] };
    var was = saved(root);
    if (was && was.data) {
      savedId = was.id; savedName = was.name;
      text.value = was.data.text || text.value;
      ['style', 'format', 'fill', 'line'].forEach(function (k) { if (was.data[k]) state[k] = was.data[k]; });
      if (sizes.some(function (s) { return s.key === was.data.size; })) state.size = was.data.size;
    }
    var quote = null, seq = 0;

    chipRow($('#ez-style'), [['outline', 'Outline'], ['solid', 'Solid']], state.style, function (v) {
      state.style = v; $('#ez-line').hidden = v !== 'outline'; update();
    });
    chipRow($('#ez-size'), sizes.map(function (s) { return [s.key, s.label + ' tall', money(s.letterPrice) + ' a letter']; }), state.size, function (v) { state.size = v; update(); });
    chipRow($('#ez-format'), [['stencil', 'One large stencil', 'Your whole word on one stencil, priced by its size. Ships in 1 to 3 sections.'],
      ['letters', 'Individual letters', 'Each letter its own stencil, priced per letter.']], state.format, function (v) { state.format = v; update(); });

    function swatches(el, key, label) {
      el.innerHTML = '<span class="dz-mut">' + label + '</span>' + colors.map(function (c) {
        return '<button type="button" class="dz-swatch" data-c="' + c[0] + '" style="background:' + c[0] + '" aria-label="' + c[1] + '" aria-pressed="' + (state[key] === c[0]) + '"></button>';
      }).join('');
      el.querySelectorAll('button').forEach(function (b) {
        b.addEventListener('click', function () {
          state[key] = b.dataset.c;
          el.querySelectorAll('button').forEach(function (x) { x.setAttribute('aria-pressed', x === b); });
          if (quote) { $('#ez-field').innerHTML = endzoneFieldSvg(quote, state.fill, state.line); remember(); }
        });
      });
    }
    swatches($('#ez-fill'), 'fill', 'Letters');
    swatches($('#ez-line'), 'line', 'Outline');

    function remember() {
      setDesign({ kind: 'endzone', text: quote.text, size: quote.sizeKey, style: quote.style, format: quote.format, fill: state.fill, line: state.line }, proofBtn);
    }

    var update = debounce(function () {
      var my = ++seq;
      clearDesign(proofBtn);
      $('#ez-msg').innerHTML = '';
      post('/custom/endzone/quote', { text: text.value, size: state.size, style: state.style, format: state.format }).then(function (q) {
        if (my !== seq) return;
        quote = q;
        $('#ez-field').innerHTML = endzoneFieldSvg(q, state.fill, state.line);
        $('#ez-stencil').innerHTML = endzoneStencilSvg(q);
        $('#ez-price').textContent = money(q.price);
        yourPrice($('#ez-price'), q);
        $('#ez-breakdown').textContent = q.format === 'letters'
          ? q.letters + ' letters × ' + money(q.letterPrice)
          : feet(q.sheet.width) + ' × ' + feet(q.sheet.height) + ' stencil, ' + q.panels + ' panels';
        $('#ez-chars').textContent = q.text !== text.value.trim().toUpperCase().replace(/\s+/g, ' ') ? 'Will be cut as: ' + q.text : '';
        specs($('#ez-specs'), [
          ['Lettering', feet(q.lettering.width) + ' × ' + feet(q.lettering.height)],
          ['Pieces', q.format === 'letters' ? q.letters + ' letter stencils' : q.sections + ' section' + (q.sections > 1 ? 's' : '') + ' (' + q.panels + ' panels' + (q.sections > 1 ? ': ' + q.sectionPanels.join(' + ') : '') + ')'],
          ['Ships', q.weight + ' lbs · ' + q.boxes + ' box' + (q.boxes > 1 ? 'es' : '')]
        ]);
        remember();
      }).catch(function (e) {
        if (my !== seq) return;
        quote = null;
        $('#ez-price').textContent = '—';
        yourPrice($('#ez-price'), null);
        $('#ez-breakdown').textContent = '';
        $('#ez-msg').innerHTML = '<div class="dz-alert">' + esc(e.message) + '</div>';
      });
    }, 200);

    $('#ez-line').hidden = state.style !== 'outline';
    text.addEventListener('input', update);
    bindProof(proofBtn);
    update();
  }

  // ============================================================ midfield

  function midfieldFieldSvg(opt, field, logo) {
    var f = opt.fields[field], L = f.length, W = f.width, hash = f.hash;
    var E = f.endZone || 30, mid = Math.round((L - 2 * E) / 6);
    var cx = L / 2, cy = W / 2, x0 = cx - 105, x1 = cx + 105, m = 5, x, yd, side;
    var stripes = '', yards = '', ticks = '', nums = '';
    for (x = E; x < L - E; x += 15) if (x + 15 > x0 && x < x1) stripes += '<rect x="' + x + '" y="0" width="15" height="' + W + '" fill="' + (((x - E) / 15) % 2 ? '#3f8030' : '#3a7629') + '"/>';
    for (x = E; x <= L - E; x += 15) if (x >= x0 && x <= x1) yards += 'M' + x + ' 0V' + W;
    for (x = E; x <= L - E; x += 3) {
      if (x < x0 || x > x1 || (x - E) % 15 === 0) continue;
      ticks += 'M' + x + ' .67V2.67M' + x + ' ' + (W - 2.67) + 'V' + (W - 0.67) + 'M' + x + ' ' + (hash - 2) + 'V' + hash + 'M' + x + ' ' + (W - hash) + 'V' + (W - hash + 2);
    }
    for (yd = 10; yd <= mid; yd += 10) for (side = -1; side <= 1; side += 2) {
      x = cx + side * (mid - yd) * 3;
      if (x < x0 || x > x1 || (yd === mid && side === 1)) continue;
      nums += '<text x="' + x + '" y="' + (W - 21) + '" class="dz-num">' + yd + '</text><text x="' + x + '" y="' + (W - 21) + '" class="dz-num" transform="rotate(180 ' + x + ' ' + (W / 2) + ')">' + yd + '</text>';
    }
    var lw = logo ? logo.width : 28, lh = logo ? logo.height : 18;
    var art = logo
      ? '<image href="' + esc(logo.logoUrl) + '" x="' + (cx - lw / 2) + '" y="' + (cy - lh / 2) + '" width="' + lw + '" height="' + lh + '" preserveAspectRatio="none"/>'
      : '<rect x="' + (cx - lw / 2) + '" y="' + (cy - lh / 2) + '" width="' + lw + '" height="' + lh + '" rx="1" fill="none" stroke="#f6f6f2" stroke-width=".35" stroke-dasharray="1.5 1"/>' +
        '<text x="' + cx + '" y="' + (cy + 1.4) + '" font-size="4" text-anchor="middle" fill="#f6f6f2" font-weight="800" opacity=".9">YOUR LOGO</text>';
    var dimY = cy + lh / 2 + 2.5;
    var dims = logo ? '<g stroke="#fff" stroke-width=".25"><path d="M' + (cx - lw / 2) + ' ' + dimY + 'H' + (cx + lw / 2) + 'M' + (cx - lw / 2) + ' ' + (dimY - 0.9) + 'V' + (dimY + 0.9) + 'M' + (cx + lw / 2) + ' ' + (dimY - 0.9) + 'V' + (dimY + 0.9) + '"/></g>' +
      '<text x="' + cx + '" y="' + (dimY + 4.6) + '" font-size="4.2" text-anchor="middle" fill="#fff" font-weight="700">' + feet(lw) + ' × ' + feet(lh) + '</text>' : '';
    var hashLabel = '<text x="' + (x1 - 1) + '" y="' + (hash - 3) + '" font-size="2.4" text-anchor="end" fill="#fff" opacity=".85">Hashmarks · ' + feet(W - 2 * hash) + ' apart</text>';
    return '<svg class="dz-svg" viewBox="' + x0 + ' ' + (-m) + ' ' + (x1 - x0) + ' ' + (W + 2 * m) + '" role="img" aria-label="Your logo at midfield on a ' + esc(f.label.toLowerCase()) + ' football field">' +
      '<rect x="' + x0 + '" y="' + (-m) + '" width="' + (x1 - x0) + '" height="' + (W + 2 * m) + '" fill="#356b26"/>' + stripes +
      '<path d="' + yards + 'M' + x0 + ' 0H' + x1 + 'M' + x0 + ' ' + W + 'H' + x1 + '" stroke="#f6f6f2" stroke-width=".45" fill="none"/>' +
      '<path d="' + ticks + '" stroke="#f6f6f2" stroke-width=".35" fill="none"/>' + nums + hashLabel + art + dims + '</svg>';
  }

  /** An SVG logo drawn to a PNG here, because the server has no SVG renderer. */
  function svgToPng(file) {
    return new Promise(function (resolve, reject) {
      var url = URL.createObjectURL(file), img = new Image();
      img.onload = function () {
        var w = img.naturalWidth || 1200, h = img.naturalHeight || 1200, k = 1200 / Math.max(w, h);
        var c = document.createElement('canvas');
        c.width = Math.max(1, Math.round(w * k)); c.height = Math.max(1, Math.round(h * k));
        c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
        URL.revokeObjectURL(url);
        c.toBlob(function (b) { b ? resolve(b) : reject(new Error('svg')); }, 'image/png');
      };
      img.onerror = function () { URL.revokeObjectURL(url); reject(new Error('svg')); };
      img.src = url;
    });
  }

  function midfield(root) {
    var opt = JSON.parse(root.dataset.options);
    var input = $('#mf-file'), drop = $('#mf-drop'), slider = $('#mf-width'), proofBtn = $('#dz-proof');
    var state = { field: 'hs', width: opt.widthDefault, upload: null, touched: false };
    var quote = null, seq = 0;
    var was = saved(root);
    if (was && was.data && was.data.logo) {
      savedId = was.id; savedName = was.name;
      state.field = opt.fields[was.data.field] ? was.data.field : state.field;
      state.width = +was.data.width || state.width;
      state.touched = true;
      state.upload = { id: was.data.logo, aspect: 1, defaults: {} };   // the first quote fills in the aspect
    }

    var row = $('#mf-field-type');
    chipRow(row, Object.keys(opt.fields).map(function (k) { return [k, opt.fields[k].label, 'Hashmarks ' + feet(opt.fields[k].hashGap) + ' apart']; }), state.field, function (v) {
      state.field = v;
      if (state.upload && !state.touched) setWidth(state.upload.defaults[state.field]);
      update();
    });

    function setWidth(w) { state.width = +w; slider.value = w; }
    function draw() { $('#mf-field').innerHTML = midfieldFieldSvg(opt, state.field, quote && state.upload ? quote : null); }
    function sizeText() {
      var h = state.upload ? Math.round(state.width * state.upload.aspect * 10) / 10 : null;
      $('#mf-size-out').textContent = h ? feet(state.width) + ' wide × ' + feet(h) + ' tall' : feet(state.width) + ' wide';
    }

    function upload(file) {
      if (!file) return;
      $('#mf-upload-msg').innerHTML = '';
      if (file.size > opt.maxBytes) { $('#mf-upload-msg').innerHTML = '<div class="dz-alert">That file is too large. Please keep it under 25 MB.</div>'; return; }
      drop.classList.add('busy');
      $('#mf-drop-title').textContent = 'Reading your logo…';
      $('#mf-drop-text').textContent = file.name + ' · this takes a few seconds';
      var isSvg = /\.svg$/i.test(file.name) || file.type === 'image/svg+xml';
      (isSvg ? svgToPng(file) : Promise.resolve(null)).then(function (preview) {
        var fd = new FormData();
        fd.append('logo', file);
        if (preview) fd.append('preview', preview, 'preview.png');
        return fetch('/custom/midfield/upload', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
      }).then(function (r) {
        return r.json().then(function (j) { if (!r.ok) throw new Error(j.error || "We couldn't process that file. Please try another."); return j; });
      }).then(function (data) {
        state.upload = data;
        state.touched = false;
        slider.disabled = false;
        setWidth(data.defaults[state.field]);
        drop.classList.add('done');
        $('#mf-drop-title').textContent = 'Logo uploaded · click to change';
        $('#mf-drop-text').textContent = file.name;
        if (data.photo) $('#mf-upload-msg').innerHTML = '<div class="dz-alert">This looks like a photo, so its background is included. For the best stencil, upload your logo file (PDF, AI, SVG, or a PNG/JPG of just the logo).</div>';
        update();
      }).catch(function (e) {
        var msg = e.message === 'svg' ? "We couldn't read that SVG. Please try a PDF or PNG of your logo." : e.message;
        $('#mf-drop-title').textContent = state.upload ? 'Logo uploaded · click to change' : 'Upload your logo';
        $('#mf-drop-text').textContent = state.upload ? '' : 'JPG, PNG, SVG, PDF or Illustrator (.ai) · up to 25 MB.';
        $('#mf-upload-msg').innerHTML = '<div class="dz-alert">' + esc(msg) + '</div>';
      }).then(function () { drop.classList.remove('busy'); input.value = ''; });
    }

    input.addEventListener('change', function () { upload(input.files[0]); });
    ['dragenter', 'dragover'].forEach(function (t) { drop.addEventListener(t, function (e) { e.preventDefault(); drop.classList.add('over'); }); });
    ['dragleave', 'drop'].forEach(function (t) { drop.addEventListener(t, function () { drop.classList.remove('over'); }); });
    drop.addEventListener('drop', function (e) { e.preventDefault(); upload(e.dataTransfer.files[0]); });
    slider.addEventListener('input', function () { state.width = +slider.value; state.touched = true; sizeText(); update(); });

    var update = debounce(function () {
      sizeText();
      if (!state.upload) { draw(); return; }
      var my = ++seq;
      clearDesign(proofBtn);
      $('#mf-msg').innerHTML = '';
      post('/custom/midfield/quote', { logo: state.upload.id, field: state.field, width: state.width }).then(function (q) {
        if (my !== seq) return;
        quote = q;
        state.upload.aspect = q.aspect;
        draw();
        $('#mf-price').textContent = money(q.price);
        yourPrice($('#mf-price'), q);
        $('#mf-breakdown').textContent = feet(q.sheet.width) + ' × ' + feet(q.sheet.height) + ' stencil, ' + q.panels + ' panels';
        $('#mf-size-note').textContent = q.insideHashes ? 'Fits between the hashmarks (' + feet(q.hashGap) + ' apart).'
          : 'Runs past the hashmarks (' + feet(q.hashGap) + ' apart on a ' + q.fieldLabel.toLowerCase() + ' field).';
        specs($('#mf-specs'), [['Logo', feet(q.width) + ' × ' + feet(q.height)], ['Stencil', '1 stencil · ' + q.panels + ' panels'],
          ['Ships', q.weight + ' lbs · ' + q.boxes + ' box' + (q.boxes > 1 ? 'es' : '')]]);
        setDesign({ kind: 'midfield', logo: q.logo, field: q.field, width: q.width }, proofBtn);
      }).catch(function (e) {
        if (my !== seq) return;
        quote = null;
        $('#mf-price').textContent = '—';
        yourPrice($('#mf-price'), null);
        $('#mf-breakdown').textContent = '';
        $('#mf-msg').innerHTML = '<div class="dz-alert">' + esc(e.message) + '</div>';
      });
    }, 150);

    slider.min = opt.widthMin; slider.max = opt.widthMax; setWidth(state.width);
    bindProof(proofBtn);
    sizeText();
    draw();
    if (state.upload) {
      slider.disabled = false;
      drop.classList.add('done');
      $('#mf-drop-title').textContent = 'Saved logo · click to change';
      $('#mf-drop-text').textContent = savedName;
      update();
    }
  }

  // ============================================================ custom text

  var inches = function (n) { return Math.round(n * 10) / 10 + '"'; };

  /** The stencil sheet with the lettering cut out, and dimension lines: the shop's plateSvg. */
  function plateSvg(layout) {
    var w = layout.plate.width, h = layout.plate.height, t = layout.text, sheets = layout.sheets;
    var unit = Math.max(w, h) / 100, pad = unit * 9, fs = unit * 4.2, sw = unit * 0.35;
    var vb = [-pad * 0.4, -pad * 0.4, w + pad * 0.9 + fs * 3.4, h + pad * 0.9 + fs * 1.8];
    var r = Math.min(w, h) * 0.03;
    var x1 = t.x, x2 = t.x + t.width, y1 = t.y, y2 = t.y + t.height;
    var dy = h + pad * 0.5, dx = w + pad * 0.5, tick = pad * 0.2;
    var plates = sheets
      ? sheets.map(function (s) { return '<rect x="' + s.x + '" y="' + s.y + '" width="' + s.width + '" height="' + s.height + '" rx="' + (Math.min(s.width, s.height) * 0.03) + '" fill="#eef1ef" stroke="#c9cfcb" stroke-width="' + sw + '"/>'; }).join('')
      : '<rect x="0" y="0" width="' + w + '" height="' + h + '" rx="' + r + '" fill="#eef1ef" stroke="#c9cfcb" stroke-width="' + sw + '"/>';
    return '<svg viewBox="' + vb.join(' ') + '" role="img" aria-label="Preview of ' + esc(layout.lines.join(' ')) + '" style="color:#646b66">' + plates +
      '<path d="' + layout.path + '" fill="#2b2f2c"/>' +
      '<g stroke="currentColor" fill="none" stroke-width="' + sw + '">' +
      '<path stroke-dasharray="' + unit + ' ' + unit + '" opacity=".6" d="M' + x1 + ' ' + y2 + ' V' + (dy + tick) + ' M' + x2 + ' ' + y2 + ' V' + (dy + tick) + ' M' + x2 + ' ' + y1 + ' H' + (dx + tick) + ' M' + x2 + ' ' + y2 + ' H' + (dx + tick) + '"/>' +
      '<path d="M' + x1 + ' ' + dy + ' H' + x2 + ' M' + x1 + ' ' + (dy - tick) + ' V' + (dy + tick) + ' M' + x2 + ' ' + (dy - tick) + ' V' + (dy + tick) + '"/>' +
      '<path d="M' + dx + ' ' + y1 + ' V' + y2 + ' M' + (dx - tick) + ' ' + y1 + ' H' + (dx + tick) + ' M' + (dx - tick) + ' ' + y2 + ' H' + (dx + tick) + '"/></g>' +
      '<text x="' + ((x1 + x2) / 2) + '" y="' + (dy + fs * 1.3) + '" font-size="' + fs + '" text-anchor="middle" fill="currentColor">' + inches(t.width) + '</text>' +
      '<text x="' + (dx + fs * 0.4) + '" y="' + ((y1 + y2) / 2 + fs * 0.35) + '" font-size="' + fs + '" fill="currentColor">' + inches(t.height) + '</text></svg>' +
      '<p class="dz-cap">' + (sheets
        ? 'Lettering ' + inches(t.width) + ' × ' + inches(t.height) + ' · ' + sheets.length + ' letter stencil' + (sheets.length === 1 ? '' : 's') + ', each ' + inches(sheets[0].width) + ' × ' + inches(sheets[0].height)
        : 'Lettering ' + inches(t.width) + ' × ' + inches(t.height) + ' · Stencil sheet ' + inches(w) + ' × ' + inches(h)) + '</p>';
  }

  function custom(root) {
    var sizes = JSON.parse(root.dataset.sizes || '[]'), materials = JSON.parse(root.dataset.materials || '{}');
    var text = $('#c-text'), size = $('#c-size'), mat = $('#c-mat');
    var seq = 0;

    size.innerHTML = sizes.map(function (s) { return '<option value="' + esc(s.key) + '">' + esc(s.label) + ' letters</option>'; }).join('');
    size.value = root.dataset.default || (sizes[0] && sizes[0].key);
    var was = saved(root);
    if (was && was.data) {
      savedId = was.id; savedName = was.name;
      if (was.data.text) text.value = was.data.text;
      if (sizes.some(function (s) { return s.key === was.data.size; })) size.value = was.data.size;
    }

    function fillMaterials() {
      var s = sizes.filter(function (x) { return x.key === size.value; })[0], keep = mat.value;
      var keys = Object.keys(materials).filter(function (m) { return s && s.prices[m]; });
      mat.innerHTML = keys.map(function (m) { return '<option value="' + m + '">' + esc(materials[m]) + '</option>'; }).join('');
      if (keys.indexOf(keep) >= 0) mat.value = keep;
    }

    var update = debounce(function () {
      var my = ++seq;
      clearDesign(null);
      $('#c-msg').innerHTML = '';
      post('/custom/text/quote', { text: text.value, size: size.value, thickness: mat.value }).then(function (res) {
        if (my !== seq) return;
        $('#c-preview').innerHTML = plateSvg(res);
        $('#c-price').textContent = money(res.price);
        yourPrice($('#c-price'), res);
        $('#c-breakdown').textContent = money(res.stencilPrice) + ' for ' + inches(res.text.width) + ' × ' + inches(res.text.height) + ' lettering' + (res.setupFee ? ' + ' + money(res.setupFee) + ' setup' : '');
        var typed = text.value.trim().toUpperCase().replace(/\s*\n\s*/g, ' / ');
        $('#c-chars').textContent = res.lines.join(' / ') !== typed ? 'Will be cut as: ' + res.lines.join(' / ') : '';
        specs($('#c-specs'), [
          ['Lettering size', inches(res.text.width) + ' × ' + inches(res.text.height)],
          ['Letter size', /x/i.test(res.size) ? res.size.replace(/\s*x\s*/i, ' × ') : res.size + ' tall, ~' + inches(res.letterWidth) + ' wide'],
          res.sheets ? ['Stencil sheets', res.sheets.length + ' letters, each ' + inches(res.sheets[0].width) + ' × ' + inches(res.sheets[0].height)]
            : ['Stencil sheet', inches(res.plate.width) + ' × ' + inches(res.plate.height) + (res.pieces > 1 ? ' · ' + res.pieces + ' pieces' : '')]
        ]);
        setDesign({ kind: 'text', text: res.lines.join('\n'), size: res.sizeKey, thickness: res.thickness }, null);
      }).catch(function (e) {
        if (my !== seq) return;
        $('#c-price').textContent = '—';
        $('#c-breakdown').textContent = '';
        $('#c-msg').innerHTML = '<div class="dz-alert">' + esc(e.message) + '</div>';
      });
    }, 200);

    fillMaterials();
    if (was && was.data && was.data.thickness && mat.querySelector('option[value="' + was.data.thickness + '"]')) mat.value = was.data.thickness;
    text.addEventListener('input', update);
    size.addEventListener('change', function () { fillMaterials(); update(); });
    mat.addEventListener('change', update);
    update();
  }

  var ct = document.getElementById('custom-designer');
  if (ct) custom(ct);
  var ez = document.getElementById('endzone-designer');
  if (ez) endzone(ez);
  var mf = document.getElementById('midfield-designer');
  if (mf) midfield(mf);
}());

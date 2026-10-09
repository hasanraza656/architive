/* ==========================================================================
   Invoice builder (admin): customer picker with inline "new customer", line items, live totals + live invoice preview.
   Markup: resources/views/portal/admin/orders/form.blade.php
   ========================================================================== */
(function () {
  'use strict';

  var form = document.getElementById('orderForm');
  if (!form) { return; }

  var $ = function (s, r) { return (r || form).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || form).querySelectorAll(s)); };
  var currency = form.getAttribute('data-currency') || 'usd';
  var money = new Intl.NumberFormat('en-US', { style: 'currency', currency: currency.toUpperCase() });
  var customers = JSON.parse(document.getElementById('customerData').textContent);
  var csrf = document.querySelector('meta[name=csrf-token]').content;

  function num(v) { var n = parseFloat(String(v).replace(/,/g, '')); return isNaN(n) ? 0 : n; }
  function cents(v) { return Math.round(num(v) * 100); }
  function setText(key, text) { $$('[data-pv="' + key + '"]', document).forEach(function (el) { el.textContent = text; }); }
  function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

  /* ---------------------------------------------------------------- customer picker */
  var combo = $('[data-combo]'), hid = $('#customer_id'), box = $('.combo__box', combo), panel = $('.combo__panel', combo),
      search = $('.combo__search', combo), listEl = $('.combo__list', combo), sel = $('.combo__sel', combo), newBox = $('#newCustomer');

  function renderList() {
    listEl.innerHTML = '';
    customers.forEach(function (c) {
      var b = document.createElement('button'); b.type = 'button'; b.className = 'combo__opt' + (String(c.id) === hid.value ? ' is-sel' : '');
      b.setAttribute('data-q', (c.name + ' ' + c.email).toLowerCase());
      b.innerHTML = '<span class="pavatar pavatar--sm pavatar--soft">' + esc(initials(c.name)) + '</span><span><b>' + esc(c.name) + '</b><small>' + esc(c.email) + '</small></span>';
      b.addEventListener('click', function () { choose(c); });
      listEl.appendChild(b);
    });
    var none = document.createElement('div'); none.className = 'combo__none'; none.textContent = 'No customer matches. Create a new one below.'; none.hidden = true; none.setAttribute('data-none', ''); listEl.appendChild(none);
  }
  function initials(n) { var p = n.trim().split(/\s+/); return ((p[0] || '')[0] || '').toUpperCase() + ((p[1] || '')[0] || '').toUpperCase(); }
  function openCombo(state) { combo.classList.toggle('is-open', state); box.setAttribute('aria-expanded', state); if (state) { search.value = ''; filterList(''); search.focus(); } }
  function filterList(q) {
    q = q.toLowerCase(); var shown = 0;
    $$('.combo__opt', listEl).forEach(function (o) { var hit = !q || o.getAttribute('data-q').indexOf(q) > -1; o.classList.toggle('is-hidden', !hit); if (hit) { shown++; } });
    $('[data-none]', listEl).hidden = shown > 0;
  }
  function choose(c) {
    hid.value = c.id;
    sel.innerHTML = '<b>' + esc(c.name) + '</b><small>' + esc(c.email) + '</small>';
    setText('customer', c.name); setText('email', c.email);
    $$('.combo__opt', listEl).forEach(function (o, i) { o.classList.toggle('is-sel', customers[i] && customers[i].id === c.id); });
    openCombo(false); clearErr('customer_id');
  }
  box.addEventListener('click', function () { openCombo(!combo.classList.contains('is-open')); });
  search.addEventListener('input', function () { filterList(search.value); });
  document.addEventListener('click', function (e) { if (!combo.contains(e.target)) { openCombo(false); } });
  combo.addEventListener('keydown', function (e) { if (e.key === 'Escape') { openCombo(false); box.focus(); } });
  $('.combo__new', combo).addEventListener('click', function () { openCombo(false); newBox.classList.add('is-open'); $('input[name=nc_first_name]', newBox).focus(); newBox.scrollIntoView({ behavior: 'smooth', block: 'center' }); });
  $('[data-nc-cancel]', newBox).addEventListener('click', function () { newBox.classList.remove('is-open'); });
  renderList();

  /* inline "new customer": POST as JSON, then select the new person without leaving the page */
  $('[data-nc-save]', newBox).addEventListener('click', function () {
    var btn = this, fields = ['first_name', 'last_name', 'email', 'phone_country', 'phone'], data = {};
    fields.forEach(function (f) { var el = $('[name="nc_' + f + '"]', newBox); data[f] = el ? el.value : ''; });
    $$('.pfield__error', newBox).forEach(function (e) { e.remove(); });
    $$('.has-error', newBox).forEach(function (e) { e.classList.remove('has-error'); });
    btn.classList.add('is-loading'); btn.disabled = true;
    fetch(newBox.getAttribute('data-url'), { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', body: JSON.stringify(data) })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, json: j }; }); })
      .then(function (res) {
        if (!res.ok) {
          Object.keys(res.json.errors || {}).forEach(function (k) {
            var el = $('[name="nc_' + k + '"]', newBox) || (k === 'phone_country' ? $('[name="nc_phone"]', newBox) : null); if (!el) { return; }
            var wrap = el.closest('.pfield'); wrap.classList.add('has-error');
            var p = document.createElement('p'); p.className = 'pfield__error'; p.textContent = res.json.errors[k][0]; wrap.appendChild(p);
          });
          return;
        }
        var c = { id: res.json.id, name: res.json.name, email: res.json.email };
        customers.unshift(c); renderList(); choose(c);
        newBox.classList.remove('is-open'); $$('input', newBox).forEach(function (i) { if (i.type !== 'hidden') { i.value = ''; } });
        flash('Customer ' + c.name + ' created and selected.');
      })
      .catch(function () { flash('Could not create the customer. Check your connection.', true); })
      .then(function () { btn.classList.remove('is-loading'); btn.disabled = false; });
  });
  function flash(text, bad) {
    var n = document.createElement('div'); n.className = 'pflash ' + (bad ? 'pflash--error' : 'pflash--success'); n.setAttribute('role', 'status'); n.textContent = text;
    var anchor = document.querySelector('[data-flash-anchor]'); anchor.parentNode.insertBefore(n, anchor); anchor.scrollIntoView({ behavior: 'smooth', block: 'center' }); setTimeout(function () { n.remove(); }, 4500);
  }
  function clearErr(name) { var el = $('[data-err="' + name + '"]'); if (el) { el.remove(); } }

  /* ---------------------------------------------------------------- line items */
  var itemsEl = $('#items'), tpl = $('#itemTpl').innerHTML, nextIdx = $$('.item', itemsEl).length + 100;

  function addItem(values) {
    var wrap = document.createElement('div'); wrap.innerHTML = tpl.replace(/__i__/g, nextIdx++).trim();
    var row = wrap.firstElementChild;
    if (values) { $('[data-f=description]', row).value = values.description || ''; $('[data-f=quantity]', row).value = values.quantity || 1; $('[data-f=unit_price]', row).value = values.unit_price || ''; }
    itemsEl.appendChild(row); wire(row); recalc(); return row;
  }
  function wire(row) {
    var ta = $('textarea', row);
    ta.addEventListener('input', function () { window.portalAutosize(ta); recalc(); }); window.portalAutosize(ta);
    $$('input', row).forEach(function (i) { i.addEventListener('input', recalc); });
    $('.item__rm', row).addEventListener('click', function () {
      if ($$('.item', itemsEl).length === 1) { $$('input, textarea', row).forEach(function (i) { i.value = ''; }); $('[data-f=quantity]', row).value = 1; window.portalAutosize(ta); recalc(); return; }
      row.remove(); recalc();
    });
  }
  $$('.item', itemsEl).forEach(wire);
  $('[data-add-item]').addEventListener('click', function () { var r = addItem(); $('textarea', r).focus(); });

  /* ---------------------------------------------------------------- totals + preview */
  var disc = $('#discount'), tax = $('#tax_rate');
  function recalc() {
    var sub = 0, lines = [];
    $$('.item', itemsEl).forEach(function (row) {
      var d = $('[data-f=description]', row).value.trim(), q = num($('[data-f=quantity]', row).value), p = cents($('[data-f=unit_price]', row).value), t = Math.round(q * p);
      $('[data-line]', row).textContent = money.format(t / 100);
      sub += t; if (d || t) { lines.push({ d: d || 'Item', q: q, p: p, t: t }); }
    });
    var dc = Math.min(sub, cents(disc.value)), rate = Math.max(0, Math.min(100, num(tax.value))), tx = Math.round((sub - dc) * rate / 100), total = sub - dc + tx;
    setText('subtotal', money.format(sub / 100)); setText('discount', '− ' + money.format(dc / 100)); setText('tax', money.format(tx / 100)); setText('taxrate', String(rate)); setText('total', money.format(total / 100));
    $$('[data-pv-row=discount]', document).forEach(function (r) { r.hidden = dc === 0; });
    $$('[data-pv-row=tax]', document).forEach(function (r) { r.hidden = tx === 0; });
    $$('[data-savetotal]', document).forEach(function (r) { r.textContent = money.format(total / 100); });

    var body = $('[data-pv="lines"]', document); body.innerHTML = '';
    if (!lines.length) { body.innerHTML = '<tr><td colspan="4" class="muted">Your line items will appear here.</td></tr>'; }
    lines.forEach(function (l) { var tr = document.createElement('tr'); tr.innerHTML = '<td class="d">' + esc(l.d) + '</td><td class="r">' + (l.q % 1 ? l.q : l.q.toFixed(0)) + '</td><td class="r">' + money.format(l.p / 100) + '</td><td class="r">' + money.format(l.t / 100) + '</td>'; body.appendChild(tr); });
  }
  [disc, tax].forEach(function (i) { i.addEventListener('input', recalc); });
  $('#title').addEventListener('input', function () { setText('title', this.value.trim() || 'Untitled order'); });
  $('#notes').addEventListener('input', function () { var n = this.value.trim(); setText('notes', n); $$('[data-pv-row=notes]', document).forEach(function (r) { r.hidden = !n; }); });

  /* due date: the visible picker is the admin's local time; the hidden field carries the exact moment in UTC */
  var dueLocal = $('#due_local'), dueIso = $('#due_at');
  function syncDue() {
    if (!dueLocal.value) { dueIso.value = ''; setText('due', 'Not set'); return; }
    var d = new Date(dueLocal.value); if (isNaN(d)) { return; }
    dueIso.value = d.toISOString(); setText('due', new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(d));
  }
  if (dueIso.value) { var d0 = new Date(dueIso.value); if (!isNaN(d0)) { var off = d0.getTimezoneOffset() * 60000; dueLocal.value = new Date(d0 - off).toISOString().slice(0, 16); } }
  dueLocal.addEventListener('input', syncDue); $('[data-due-clear]').addEventListener('click', function () { dueLocal.value = ''; syncDue(); });
  $$('[data-due-quick]').forEach(function (b) {
    b.addEventListener('click', function () { var d = new Date(Date.now() + parseInt(b.getAttribute('data-due-quick'), 10) * 864e5); d.setHours(18, 0, 0, 0); dueLocal.value = new Date(d - d.getTimezoneOffset() * 60000).toISOString().slice(0, 16); syncDue(); });
  });

  /* ---------------------------------------------------------------- submit */
  form.addEventListener('submit', function (e) {
    syncDue();
    var problems = [];
    if (!hid.value) { problems.push('Choose a customer, or create a new one.'); }
    if (!$$('.item', itemsEl).some(function (r) { return $('[data-f=description]', r).value.trim(); })) { problems.push('Add at least one line item with a description.'); }
    if (problems.length) { e.preventDefault(); flash(problems[0], true); (hid.value ? $('#items textarea') : box).focus(); return; }
    // drop completely empty rows so they are not validated
    $$('.item', itemsEl).forEach(function (r) { if (!$('[data-f=description]', r).value.trim() && !num($('[data-f=unit_price]', r).value)) { r.remove(); } });
    var b = e.submitter; if (b) { setTimeout(function () { b.classList.add('is-loading'); }, 0); $$('button[type=submit]').forEach(function (x) { if (x !== b) { x.disabled = true; } }); }
  });

  /* initial paint */
  var cur = customers.filter(function (c) { return String(c.id) === hid.value; })[0]; if (cur) { choose(cur); }
  setText('title', $('#title').value.trim() || 'Untitled order');
  $('#notes').dispatchEvent(new Event('input')); syncDue(); recalc();
}());

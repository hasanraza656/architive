/* ==========================================================================
   Admin inbox: auto-submitting filters, quick view dialog (read on open + quick reply),
   mark read / unread without leaving the page.  Markup: resources/views/portal/admin/inbox.blade.php
   ========================================================================== */
(function () {
  'use strict';

  var root = document.querySelector('[data-inbox]');
  if (!root) { return; }

  var csrf = root.getAttribute('data-csrf'),
      dialog = document.getElementById('quickView'),
      body = dialog.querySelector('[data-qv-body]'),
      openLink = dialog.querySelector('[data-qv-open]'),
      current = null;

  function $$(s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); }

  function post(url, data) {
    var fd = new FormData(); fd.append('_token', csrf);
    if (data) { Object.keys(data).forEach(function (k) { fd.append(k, data[k]); }); }
    return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, json: j }; }, function () { return { ok: false, json: {} }; }); });
  }

  /* ---- filters: selects and dates apply straight away ---- */
  $$('[data-autosubmit]').forEach(function (el) { el.addEventListener('change', function () { el.form.submit(); }); });

  /* ---- unread numbers (pill + sidebar) ---- */
  function setCount(n) {
    $$('[data-inbox-count]').forEach(function (el) { el.textContent = n; if (el.classList.contains('pnav__count')) { el.hidden = !n; } });
  }

  function paint(row, unread) {
    row.classList.toggle('is-unread', unread);
    var b = row.querySelector('[data-toggle-read]');
    if (b) { var t = unread ? 'Mark as read' : 'Mark as unread'; b.setAttribute('aria-label', t); b.setAttribute('title', t); }
  }

  /* read/unread work as a marker per conversation: reading a message also reads the earlier ones, unread also unreads the later ones */
  function sync(row, unread) {
    var id = +row.getAttribute('data-id'), order = row.getAttribute('data-order');
    $$('[data-row][data-order="' + order + '"][data-customer="1"]').forEach(function (r) {
      var rid = +r.getAttribute('data-id');
      if (unread ? rid >= id : rid <= id) { paint(r, unread); }
    });
  }

  function markRead(row) {
    if (!row.classList.contains('is-unread')) { return Promise.resolve(); }
    sync(row, false);
    return post(row.getAttribute('data-read-url')).then(function (res) { if (res.ok) { setCount(res.json.unread); } else { paint(row, true); } });
  }

  function toggle(row, btn) {
    var wasUnread = row.classList.contains('is-unread');
    btn.disabled = true;
    var req = wasUnread ? post(row.getAttribute('data-read-url')) : post(row.getAttribute('data-unread-url'));
    req.then(function (res) { if (res.ok) { sync(row, !wasUnread); setCount(res.json.unread); } }).then(function () { btn.disabled = false; });
  }

  root.addEventListener('click', function (e) {
    var row = e.target.closest('[data-row]');
    if (!row) { return; }
    var q = e.target.closest('[data-quick]'), t = e.target.closest('[data-toggle-read]');
    if (q) { openQuick(row); } else if (t) { toggle(row, t); }
  });

  /* ---- quick view ---- */
  function openQuick(row) {
    current = row;
    body.innerHTML = row.querySelector('template[data-qv]').innerHTML;
    $$('time[datetime]', body).forEach(function (t) { var d = new Date(t.getAttribute('datetime')); if (!isNaN(d)) { t.textContent = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(d); } });
    var link = body.querySelector('.qv__open');
    openLink.href = link ? link.getAttribute('href') : '#';
    dialog.showModal();
    markRead(row);
    var area = body.querySelector('textarea');
    if (area) { setTimeout(function () { area.focus({ preventScroll: true }); }, 60); }
  }

  /* quick reply (uses the same endpoint as the conversation page) */
  body.addEventListener('submit', function (e) {
    var form = e.target.closest('[data-reply]');
    if (!form) { return; }
    e.preventDefault();
    var area = form.querySelector('textarea'), msg = form.querySelector('[data-reply-msg]'), btn = form.querySelector('button[type=submit]'), text = area.value.trim();
    if (!text) { return; }
    btn.disabled = true; msg.hidden = true; msg.classList.remove('is-err');
    post(form.action, { body: text }).then(function (res) {
      msg.hidden = false;
      if (!res.ok) {
        var errs = res.json && res.json.errors ? [].concat.apply([], Object.keys(res.json.errors).map(function (k) { return res.json.errors[k]; })) : [];
        msg.classList.add('is-err'); msg.textContent = errs[0] || res.json.message || 'Could not send. Please try again.';
        return;
      }
      area.value = ''; msg.textContent = 'Reply sent. The customer is notified if they are away.';
      if (current) { paint(current, false); post(current.getAttribute('data-read-url')).then(function (r) { if (r.ok) { setCount(r.json.unread); } }); }   // replying reads the whole conversation
    }).catch(function () { msg.hidden = false; msg.classList.add('is-err'); msg.textContent = 'Connection problem. Your reply was not sent.'; })
      .then(function () { btn.disabled = false; });
  });

  dialog.addEventListener('close', function () { body.innerHTML = ''; current = null; });
}());

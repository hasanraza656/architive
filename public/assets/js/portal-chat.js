/* ==========================================================================
   Order conversation (admin + customer). Plain HTTP polling today.
   To go real-time later: keep render()/append() and call them from a websocket handler instead of poll().
   Markup + URLs come from partials/shared/chat.blade.php (data-* attributes + #chatFeed JSON).
   ========================================================================== */
(function () {
  'use strict';

  var root = document.querySelector('[data-chat]');
  if (!root) { return; }

  var list = root.querySelector('[data-chat-list]'),
      form = root.querySelector('[data-chat-form]'),
      area = form && form.querySelector('textarea'),
      fileInput = form && form.querySelector('input[type=file]'),
      chips = form && form.querySelector('[data-chat-files]'),
      errBox = form && form.querySelector('[data-chat-err]'),
      sendBtn = form && form.querySelector('[data-chat-send]'),
      feedUrl = root.getAttribute('data-feed-url'),
      pollMs = (parseInt(root.getAttribute('data-poll'), 10) || 4) * 1000,
      orderStatus = root.getAttribute('data-status'),
      csrf = document.querySelector('meta[name=csrf-token]').content,
      maxFiles = parseInt(root.getAttribute('data-max-files'), 10) || 8,
      lastId = 0, seenUpTo = 0, offer = null, pending = new DataTransfer(), polling = false, lastDay = '', mineIds = [];

  function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
  function linkify(t) { return esc(t).replace(/(https?:\/\/[^\s<]+[^\s<.,;:!?)])/g, '<a href="$1" target="_blank" rel="noopener noreferrer nofollow">$1</a>'); }
  function dayKey(d) { return d.getFullYear() + '-' + d.getMonth() + '-' + d.getDate(); }
  function dayLabel(d) {
    var n = new Date(), y = new Date(Date.now() - 864e5);
    if (dayKey(d) === dayKey(n)) { return 'Today'; }
    if (dayKey(d) === dayKey(y)) { return 'Yesterday'; }
    return new Intl.DateTimeFormat(undefined, { weekday: 'short', month: 'short', day: 'numeric' }).format(d);
  }
  function icon(name) {
    var p = { download: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/>' }[name];
    return '<svg class="ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + p + '</svg>';
  }

  var TONE = { pending: 'amber', active: 'blue', delivered: 'violet', completed: 'green', cancelled: 'red' };

  /* custom-offer card: drawn from the live order state (feed.offer) so it updates itself (Pay now -> Paid) */
  function paintOffers() {
    var cards = list.querySelectorAll('.offer-card');
    cards.forEach(function (card, i) {
      var latest = i === cards.length - 1;
      if (!offer) { card.innerHTML = ''; return; }
      var due = offer.due ? '<span>Delivery due ' + new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(new Date(offer.due)) + '</span>' : '';
      var html = '<div class="offer__top"><span class="offer__tag">' + (latest ? 'Custom offer' : 'Earlier version') + '</span><span class="pbadge pbadge--' + (TONE[offer.status] || 'grey') + '">' + esc(offer.status_label) + '</span></div>' +
        '<h4 class="offer__title">' + esc(offer.title) + '</h4><div class="offer__price">' + esc(offer.total) + '</div>' +
        '<p class="offer__meta"><span>' + offer.items + (offer.items === 1 ? ' item' : ' items') + '</span>' + due + '</p>';
      if (latest) {
        html += '<div class="offer__act">';
        if (offer.can_pay) { html += '<form method="post" action="' + offer.pay_url + '"><input type="hidden" name="_token" value="' + csrf + '"><button class="pbtn pbtn--primary" type="submit">Pay ' + esc(offer.total) + ' &amp; start</button></form>'; }
        html += '<a class="pbtn pbtn--ghost pbtn--sm" href="' + offer.view_url + '">View details</a><a class="pbtn pbtn--ghost pbtn--sm" href="' + offer.pdf_url + '">PDF</a></div>';
        if (offer.status === 'pending' && !offer.can_pay) { html += '<p class="offer__note">Waiting for the customer to accept and pay.</p>'; }
        if (offer.can_pay) { html += '<p class="offer__note">Secure payment by Stripe. Your project starts right after.</p>'; }
        if (offer.status === 'active') { html += '<p class="offer__note is-ok">Paid. The order is in progress.</p>'; }
      }
      card.innerHTML = html;
    });
  }

  function messageEl(m) {
    var d = new Date(m.time), wrap = document.createElement('div');
    if (m.kind === 'offer') {
      wrap.className = 'msg msg--offer'; wrap.setAttribute('data-id', m.id);
      wrap.innerHTML = '<div class="offer-card" role="group" aria-label="Custom offer"></div><div class="msg__foot"><span>' + esc(m.author) + ' · ' + new Intl.DateTimeFormat(undefined, { timeStyle: 'short' }).format(d) + '</span></div>';
      return wrap;
    }
    wrap.className = 'msg' + (m.mine ? ' msg--mine' : '') + (m.role === 'admin' ? ' msg--staff' : '');
    wrap.setAttribute('data-id', m.id);
    var html = '';
    if (!m.mine) { html += '<span class="pavatar pavatar--sm ' + (m.role === 'admin' ? '' : 'pavatar--soft') + '" aria-hidden="true">' + esc(m.initials) + '</span>'; }
    html += '<div class="msg__bubble">';
    if (!m.mine) { html += '<span class="msg__who">' + esc(m.author) + '</span>'; }
    if (m.body) { html += '<div class="msg__text">' + linkify(m.body) + '</div>'; }
    m.files.forEach(function (f) {
      if (f.image) { html += '<a class="msg__img" href="' + f.url + '" target="_blank" rel="noopener"><img src="' + f.preview + '" alt="' + esc(f.name) + '" loading="lazy"></a>'; }
    });
    var docs = m.files.filter(function (f) { return !f.image; });
    if (docs.length) {
      html += '<div class="msg__files">';
      docs.forEach(function (f) { html += '<a class="msg__file" href="' + f.url + '">' + icon('download') + '<span>' + esc(f.name) + '</span><small>' + esc(f.size) + '</small></a>'; });
      html += '</div>';
    }
    html += '<div class="msg__foot"><span>' + new Intl.DateTimeFormat(undefined, { timeStyle: 'short' }).format(d) + '</span>' + (m.mine ? '<span data-seen></span>' : '') + '</div></div>';
    wrap.innerHTML = html;
    return wrap;
  }

  function atBottom() { return list.scrollHeight - list.scrollTop - list.clientHeight < 90; }
  function toBottom() { list.scrollTop = list.scrollHeight; }

  function append(messages) {
    if (!messages.length) { return; }
    var stick = atBottom(), empty = list.querySelector('[data-chat-empty]');
    if (empty) { empty.remove(); }
    messages.forEach(function (m) {
      if (list.querySelector('[data-id="' + m.id + '"]')) { return; }
      var d = new Date(m.time), k = dayKey(d);
      if (k !== lastDay) { var s = document.createElement('div'); s.className = 'chat__day'; s.textContent = dayLabel(d); list.appendChild(s); lastDay = k; }
      list.appendChild(messageEl(m));
      if (m.mine) { mineIds.push(m.id); }
      lastId = Math.max(lastId, m.id);
    });
    if (stick || messages.some(function (m) { return m.mine; })) { toBottom(); }
  }

  function paintSeen() {
    list.querySelectorAll('[data-seen]').forEach(function (el) { el.textContent = ''; });
    var last = mineIds.length ? mineIds[mineIds.length - 1] : 0;
    if (last && seenUpTo >= last) { var el = list.querySelector('[data-id="' + last + '"] [data-seen]'); if (el) { el.textContent = '· Seen'; } }
  }

  function apply(feed, first) {
    append(feed.messages);
    if (feed.offer !== undefined) { offer = feed.offer; }
    paintOffers();
    seenUpTo = Math.max(seenUpTo, feed.seen_up_to || 0);
    paintSeen();
    if (feed.status && feed.status !== orderStatus && !first) { notice(); }
    if (first && !feed.messages.length) { showEmpty(); }
  }

  function showEmpty() {
    if (list.querySelector('.msg') || list.querySelector('[data-chat-empty]')) { return; }
    var e = document.createElement('div'); e.className = 'chat__empty'; e.setAttribute('data-chat-empty', '');
    e.innerHTML = root.querySelector('template[data-empty]').innerHTML; list.appendChild(e);
  }

  function notice() {
    if (document.querySelector('[data-status-notice]')) { return; }
    var n = document.createElement('div'); n.className = 'pflash pflash--info'; n.setAttribute('data-status-notice', '');
    n.innerHTML = '<span>This order was just updated.</span> <a class="plink" href="" style="margin-left:.5rem">Refresh the page</a>';
    root.parentNode.insertBefore(n, root);
  }

  function poll() {
    if (polling || document.hidden) { return; }
    polling = true;
    fetch(feedUrl + '?after=' + lastId, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
      .then(function (r) { if (r.status === 401 || r.status === 419) { location.reload(); } return r.ok ? r.json() : null; })
      .then(function (feed) { if (feed) { apply(feed, false); } })
      .catch(function () {})
      .then(function () { polling = false; });
  }

  /* ---- composer ---- */
  function showErr(t) { if (errBox) { errBox.textContent = t || ''; errBox.hidden = !t; } }
  function renderChips() {
    chips.innerHTML = '';
    Array.prototype.forEach.call(pending.files, function (f, i) {
      var c = document.createElement('span'); c.className = 'fchip'; c.appendChild(document.createTextNode(f.name));
      var x = document.createElement('button'); x.type = 'button'; x.innerHTML = '&times;'; x.setAttribute('aria-label', 'Remove ' + f.name);
      x.addEventListener('click', function () { var n = new DataTransfer(); Array.prototype.forEach.call(pending.files, function (y, k) { if (k !== i) { n.items.add(y); } }); pending = n; renderChips(); });
      c.appendChild(x); chips.appendChild(c);
    });
  }

  if (form) {
    form.querySelector('[data-chat-attach]').addEventListener('click', function () { fileInput.click(); });
    fileInput.addEventListener('change', function () {
      Array.prototype.forEach.call(fileInput.files, function (f) { if (pending.files.length < maxFiles) { pending.items.add(f); } });
      fileInput.value = ''; renderChips(); showErr(pending.files.length >= maxFiles ? 'You can attach up to ' + maxFiles + ' files per message.' : '');
    });
    area.addEventListener('input', function () { area.style.height = 'auto'; area.style.height = Math.min(area.scrollHeight + 2, 150) + 'px'; });
    area.addEventListener('keydown', function (e) {
      var touch = window.matchMedia('(pointer: coarse)').matches;
      if (e.key === 'Enter' && !e.shiftKey && !touch) { e.preventDefault(); form.requestSubmit(); }
    });
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var text = area.value.trim();
      if (!text && !pending.files.length) { return; }
      var fd = new FormData(); fd.append('_token', csrf); fd.append('body', text);
      Array.prototype.forEach.call(pending.files, function (f) { fd.append('files[]', f); });
      sendBtn.disabled = true; showErr('');
      fetch(form.action, { method: 'POST', body: fd, headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
        .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, status: r.status, json: j }; }).catch(function () { return { ok: false, status: r.status, json: {} }; }); })
        .then(function (res) {
          if (res.status === 413) { showErr('That upload is too large for the server.'); return; }
          if (!res.ok) { var errs = res.json && res.json.errors ? [].concat.apply([], Object.keys(res.json.errors).map(function (k) { return res.json.errors[k]; })) : []; showErr(errs[0] || res.json.message || 'Could not send. Please try again.'); return; }
          append([res.json]); area.value = ''; area.style.height = ''; pending = new DataTransfer(); renderChips();
        })
        .catch(function () { showErr('Connection problem. Your message was not sent.'); })
        .then(function () { sendBtn.disabled = false; area.focus(); });
    });
  }

  /* ---- boot ---- */
  var initial = document.getElementById('chatFeed');
  if (initial) { try { apply(JSON.parse(initial.textContent), true); } catch (e) {} }
  toBottom();

  /* ?m=ID (from the admin inbox): open the conversation tab and scroll to that exact message, even in the middle of the thread */
  var focusId = parseInt(new URLSearchParams(location.search).get('m'), 10) || 0;
  function focusMessage() {
    var el = focusId && list.querySelector('[data-id="' + focusId + '"]');
    if (!el) { focusId = 0; return false; }
    var box = list.getBoundingClientRect(), r = el.getBoundingClientRect();
    list.scrollTop += (r.top - box.top) - (list.clientHeight / 2 - r.height / 2);
    root.scrollIntoView({ block: 'nearest' });
    el.classList.remove('msg--focus'); void el.offsetWidth; el.classList.add('msg--focus');
    focusId = 0;
    return true;
  }
  if (focusId) {
    var chatTab = document.querySelector('[data-tab="chat"]');
    if (chatTab && !chatTab.classList.contains('is-on')) { chatTab.click(); }
    setTimeout(focusMessage, 80);
  }

  setInterval(poll, pollMs);
  document.addEventListener('visibilitychange', function () { if (!document.hidden) { poll(); } });
  document.addEventListener('portal:tab', function (e) { if (e.detail === 'chat') { setTimeout(function () { if (!(focusId && focusMessage())) { toBottom(); } }, 30); } });
}());

/* ==========================================================================
   Architive portal: shared behaviour (vanilla JS, no dependencies)
   data-* hooks: [data-nav-toggle] [data-flash-close] [data-dt] [data-countdown] [data-tabs] [data-copy] [data-confirm]
                 [data-dialog-open] [data-dialog-close] [data-cc] [data-drop] [data-loading] [data-autosize] [data-otp]
   ========================================================================== */
(function () {
  'use strict';

  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  /* ---- mobile sidebar ---- */
  var body = document.body;
  $$('[data-nav-toggle]').forEach(function (b) {
    b.addEventListener('click', function () { body.classList.toggle('nav-open'); b.setAttribute('aria-expanded', body.classList.contains('nav-open')); });
  });
  var scrim = $('.pscrim');
  if (scrim) { scrim.addEventListener('click', function () { body.classList.remove('nav-open'); }); }
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { body.classList.remove('nav-open'); } });

  /* ---- flash messages ---- */
  $$('[data-flash-close]').forEach(function (b) { b.addEventListener('click', function () { b.closest('.pflash').remove(); }); });

  /* ---- local date/time: <time data-dt="datetime|date|time|short" datetime="ISO"> ---- */
  var fmt = {
    datetime: { dateStyle: 'medium', timeStyle: 'short' },
    date: { dateStyle: 'medium' },
    time: { timeStyle: 'short' },
    short: { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }
  };
  function localise(root) {
    $$('[data-dt]', root).forEach(function (el) {
      var iso = el.getAttribute('datetime');
      if (!iso) { return; }
      var d = new Date(iso);
      if (isNaN(d)) { return; }
      el.textContent = new Intl.DateTimeFormat(undefined, fmt[el.getAttribute('data-dt')] || fmt.datetime).format(d);
    });
  }
  window.portalLocalise = localise;
  localise(document);

  /* ---- countdown ---- */
  $$('[data-countdown]').forEach(function (box) {
    var until = new Date(box.getAttribute('data-countdown'));
    var note = box.parentNode.querySelector('[data-cd-note]');
    var u = { d: $('[data-u="d"]', box), h: $('[data-u="h"]', box), m: $('[data-u="m"]', box), s: $('[data-u="s"]', box) };
    var nowLabel = new Intl.DateTimeFormat(undefined, fmt.datetime).format(until);
    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    function tick() {
      var diff = until - new Date(), late = diff < 0, t = Math.floor(Math.abs(diff) / 1000);
      u.d.textContent = Math.floor(t / 86400); u.h.textContent = pad(Math.floor(t % 86400 / 3600));
      u.m.textContent = pad(Math.floor(t % 3600 / 60)); u.s.textContent = pad(t % 60);
      box.classList.toggle('is-late', late);
      if (note) { note.classList.toggle('is-late', late); note.textContent = late ? 'Past the due date (' + nowLabel + ')' : 'Due ' + nowLabel; }
    }
    tick(); setInterval(tick, 1000);
  });

  /* ---- tabs ---- */
  $$('[data-tabs]').forEach(function (wrap) {
    var tabs = $$('[data-tab]', wrap), panes = $$('[data-pane]', wrap.parentNode);
    function open(name, push) {
      var found = false;
      tabs.forEach(function (t) { var on = t.getAttribute('data-tab') === name; t.classList.toggle('is-on', on); t.setAttribute('aria-selected', on); found = found || on; });
      panes.forEach(function (p) { p.classList.toggle('is-on', p.getAttribute('data-pane') === name); });
      if (found && push) { history.replaceState(null, '', '#' + name); }
      document.dispatchEvent(new CustomEvent('portal:tab', { detail: name }));
    }
    tabs.forEach(function (t) { t.addEventListener('click', function () { open(t.getAttribute('data-tab'), true); }); });
    var hash = location.hash.replace('#', '');
    open(tabs.some(function (t) { return t.getAttribute('data-tab') === hash; }) ? hash : wrap.getAttribute('data-default'), false);
  });

  /* ---- copy to clipboard ---- */
  $$('[data-copy]').forEach(function (b) {
    b.addEventListener('click', function () {
      var text = b.getAttribute('data-copy'), label = b.querySelector('[data-copy-label]');
      var done = function () { if (label) { var old = label.textContent; label.textContent = 'Copied!'; setTimeout(function () { label.textContent = old; }, 1600); } };
      if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(text).then(done); return; }
      var ta = document.createElement('textarea'); ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0'; document.body.appendChild(ta); ta.select();
      try { document.execCommand('copy'); done(); } catch (e) {} ta.remove();
    });
  });

  /* ---- confirm before dangerous / irreversible submits ---- */
  $$('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!window.confirm(f.getAttribute('data-confirm'))) { e.preventDefault(); } });
  });

  /* ---- dialogs ---- */
  $$('[data-dialog-open]').forEach(function (b) {
    b.addEventListener('click', function () { var d = document.getElementById(b.getAttribute('data-dialog-open')); if (d && d.showModal) { d.showModal(); var f = d.querySelector('textarea, input:not([type=hidden])'); if (f) { f.focus(); } } });
  });
  $$('[data-dialog-close]').forEach(function (b) { b.addEventListener('click', function () { b.closest('dialog').close(); }); });
  $$('dialog.pmodal').forEach(function (d) { d.addEventListener('click', function (e) { if (e.target === d) { d.close(); } }); });

  /* ---- buttons: loading state on submit (prevents double submit) ---- */
  $$('form[data-loading]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (e.defaultPrevented) { return; }
      var b = e.submitter || f.querySelector('button[type=submit]');
      if (b) { setTimeout(function () { b.classList.add('is-loading'); b.setAttribute('disabled', ''); }, 0); }
    });
  });
  window.addEventListener('pageshow', function (e) { if (e.persisted) { $$('.is-loading').forEach(function (b) { b.classList.remove('is-loading'); b.removeAttribute('disabled'); }); } });

  /* ---- auto-growing textareas ---- */
  function autosize(t) { t.style.height = 'auto'; t.style.height = Math.min(t.scrollHeight + 2, 400) + 'px'; }
  window.portalAutosize = autosize;
  $$('textarea[data-autosize]').forEach(function (t) { autosize(t); t.addEventListener('input', function () { autosize(t); }); });

  /* ---- country / dial-code picker ---- */
  $$('[data-cc]').forEach(function (box) {
    var input = $('input[type=hidden]', box), btn = $('.cc__btn', box), label = $('[data-cc-label]', box), search = $('.cc__search', box), items = $$('.cc__item', box);
    function open(state) { box.classList.toggle('is-open', state); btn.setAttribute('aria-expanded', state); if (state) { search.value = ''; filter(''); search.focus(); var sel = $('.is-sel', box); if (sel) { sel.scrollIntoView({ block: 'center' }); } } }
    function filter(q) { q = q.toLowerCase().replace('+', ''); items.forEach(function (i) { i.classList.toggle('is-hidden', q && (i.getAttribute('data-name').toLowerCase().indexOf(q) < 0 && i.getAttribute('data-dial').indexOf(q) !== 0 && i.getAttribute('data-iso').toLowerCase() !== q)); }); }
    function pick(i) { input.value = i.getAttribute('data-iso'); label.textContent = i.getAttribute('data-iso') + ' +' + i.getAttribute('data-dial'); items.forEach(function (x) { x.classList.toggle('is-sel', x === i); }); open(false); input.dispatchEvent(new Event('change', { bubbles: true })); }
    btn.addEventListener('click', function () { open(!box.classList.contains('is-open')); });
    search.addEventListener('input', function () { filter(search.value); });
    search.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); var f = items.filter(function (i) { return !i.classList.contains('is-hidden'); })[0]; if (f) { pick(f); } } });
    items.forEach(function (i) { i.addEventListener('click', function () { pick(i); }); });
    document.addEventListener('click', function (e) { if (!box.contains(e.target)) { open(false); } });
    box.addEventListener('keydown', function (e) { if (e.key === 'Escape') { open(false); btn.focus(); } });
  });

  /* ---- drag & drop / click file chooser with a visible list: <div data-drop> ---- */
  $$('[data-drop]').forEach(function (zone) {
    var input = $('input[type=file]', zone), list = zone.parentNode.querySelector('[data-drop-list]'), dt = new DataTransfer();
    function render() {
      if (!list) { return; }
      list.innerHTML = '';
      Array.prototype.forEach.call(dt.files, function (f, i) {
        var li = document.createElement('li'), name = document.createElement('span'), size = document.createElement('small'), rm = document.createElement('button');
        name.textContent = f.name; size.textContent = f.size > 1048576 ? (f.size / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(f.size / 1024)) + ' KB';
        rm.type = 'button'; rm.setAttribute('aria-label', 'Remove ' + f.name); rm.innerHTML = '&times;';
        rm.addEventListener('click', function () { var n = new DataTransfer(); Array.prototype.forEach.call(dt.files, function (x, k) { if (k !== i) { n.items.add(x); } }); dt = n; input.files = dt.files; render(); });
        li.appendChild(name); li.appendChild(size); li.appendChild(rm); list.appendChild(li);
      });
    }
    function add(files) { Array.prototype.forEach.call(files, function (f) { dt.items.add(f); }); input.files = dt.files; render(); }
    input.addEventListener('change', function () { add(Array.prototype.slice.call(input.files)); });   // keep earlier picks, append new ones
    zone.addEventListener('dragover', function (e) { e.preventDefault(); zone.classList.add('is-over'); });
    zone.addEventListener('dragleave', function () { zone.classList.remove('is-over'); });
    zone.addEventListener('drop', function (e) { e.preventDefault(); zone.classList.remove('is-over'); add(e.dataTransfer.files); });
  });

  /* ---- one-time code boxes ---- */
  $$('[data-otp]').forEach(function (wrap) {
    var boxes = $$('input[type=text]', wrap), hidden = $('input[type=hidden]', wrap), form = wrap.closest('form');
    function sync() { var v = boxes.map(function (b) { return b.value; }).join(''); hidden.value = v; boxes.forEach(function (b) { b.classList.toggle('is-filled', !!b.value); }); if (v.length === boxes.length) { form.requestSubmit ? form.requestSubmit() : form.submit(); } }
    boxes.forEach(function (b, i) {
      b.addEventListener('input', function () { b.value = b.value.replace(/\D/g, '').slice(-1); if (b.value && boxes[i + 1]) { boxes[i + 1].focus(); } sync(); });
      b.addEventListener('keydown', function (e) { if (e.key === 'Backspace' && !b.value && boxes[i - 1]) { boxes[i - 1].focus(); } if (e.key === 'ArrowLeft' && boxes[i - 1]) { boxes[i - 1].focus(); } if (e.key === 'ArrowRight' && boxes[i + 1]) { boxes[i + 1].focus(); } });
      b.addEventListener('paste', function (e) { var t = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, ''); if (!t) { return; } e.preventDefault(); boxes.forEach(function (x, k) { x.value = t[k] || ''; }); (boxes[Math.min(t.length, boxes.length - 1)]).focus(); sync(); });
      b.addEventListener('focus', function () { b.select(); });
    });
    if (wrap.classList.contains('is-error')) { boxes[0].focus(); } else { boxes[0].focus(); }
  });

  /* ---- resend countdown ---- */
  $$('[data-resend]').forEach(function (btn) {
    var left = parseInt(btn.getAttribute('data-resend'), 10), label = btn.querySelector('[data-resend-label]');
    function paint() { if (left > 0) { btn.setAttribute('disabled', ''); label.textContent = 'Resend in ' + left + 's'; } else { btn.removeAttribute('disabled'); label.textContent = 'Send a new code'; } }
    paint(); var t = setInterval(function () { left--; paint(); if (left <= 0) { clearInterval(t); } }, 1000);
  });
}());

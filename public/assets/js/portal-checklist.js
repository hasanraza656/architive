/* ==========================================================================
   To-do checklists (Notion style) inside tasks and projects.
   - Type and press Enter: the next line opens straight away, nothing waits for the server (writes go through a queue).
   - Ctrl+Enter ticks a line. A line you tick gets a line drawn through it and shows who ticked it and when.
   - Paste a list (one item per line) to add many at once. Backspace on an empty line removes it (Undo is offered).
   - Drag the handle to re-order.
   - Live: the page asks the server for changes every few seconds while it is visible (tiny "nothing new" answers), other tabs
     of the same browser are told instantly (BroadcastChannel), and a soft note appears when a teammate ticks something.
   Markup: portal/tasks/_checklist.blade.php. Works in the task panel (drawer), the task page and the project page.
   ========================================================================== */
(function () {
  'use strict';

  var csrf = (document.querySelector('meta[name=csrf-token]') || {}).content || '';
  var POLL_MS = 4000;
  var instances = [];
  var channel = null;
  try { if ('BroadcastChannel' in window) { channel = new BroadcastChannel('architive-checklist'); } } catch (e) { channel = null; }

  var ICON = {
    check: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>',
    plus: '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>',
    trash: '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14M10 11v5M14 11v5"/></svg>',
    grip: '<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><circle cx="9" cy="6" r="1.7"/><circle cx="15" cy="6" r="1.7"/><circle cx="9" cy="12" r="1.7"/><circle cx="15" cy="12" r="1.7"/><circle cx="9" cy="18" r="1.7"/><circle cx="15" cy="18" r="1.7"/></svg>'
  };

  function $(s, r) { return (r || document).querySelector(s); }
  function $$(s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); }

  function request(url, method, data) {
    var opts = { method: method, credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf } };
    if (data) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(data); }
    return fetch(url, opts).then(function (r) {
      return r.json().catch(function () { return {}; }).then(function (j) {
        if (!r.ok) {
          var m = j.message;
          if (j.errors) { m = [].concat.apply([], Object.keys(j.errors).map(function (k) { return j.errors[k]; }))[0] || m; }
          if (r.status === 403) { m = 'You can look at this checklist, but not change it.'; }
          var err = new Error(m || 'Something went wrong. Please try again.'); err.status = r.status; throw err;
        }
        return j;
      });
    });
  }

  /* ---------------------------------------------------------------- soft notes (one at a time, they replace each other) */
  var toastEl = null, toastTimer = 0;
  function toast(msg, opts) {
    opts = opts || {};
    if (!toastEl) {
      toastEl = document.createElement('div');
      toastEl.className = 'cltoast'; toastEl.setAttribute('role', 'status'); toastEl.setAttribute('aria-live', 'polite');
      toastEl.innerHTML = '<span class="cltoast__dot"></span><span class="cltoast__msg"></span><button type="button" class="cltoast__undo" hidden>Undo</button>';
      document.body.appendChild(toastEl);
      $('.cltoast__undo', toastEl).addEventListener('click', function () {
        var fn = toastEl._undo; toastEl._undo = null; hideToast(); if (fn) { fn(); }
      });
    }
    $('.cltoast__msg', toastEl).textContent = msg;
    var u = $('.cltoast__undo', toastEl);
    u.hidden = !opts.undo; toastEl._undo = opts.undo || null;
    toastEl.classList.toggle('is-bad', !!opts.bad);
    toastEl.classList.add('is-on');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(hideToast, opts.undo ? 5200 : 3000);
  }
  function hideToast() { if (toastEl) { toastEl.classList.remove('is-on'); } }

  /* ---------------------------------------------------------------- small helpers */
  function el(tag, cls) { var e = document.createElement(tag); if (cls) { e.className = cls; } return e; }
  function clean(s) { return String(s == null ? '' : s).replace(/ /g, ' ').replace(/[\r\n\t]+/g, ' ').replace(/\s{2,}/g, ' ').trim().slice(0, 500); }
  function firstName(n) { return String(n || '').split(' ')[0] || 'Someone'; }
  function when(iso) {
    if (!iso) { return ''; }
    var d = new Date(iso); if (isNaN(d)) { return ''; }
    var now = new Date();
    if (d.toDateString() === now.toDateString()) { return d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }); }
    return d.toLocaleDateString([], { month: 'short', day: 'numeric' }) + ', ' + d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
  }
  function caretAtStart(node) {
    var s = window.getSelection(); if (!s.rangeCount || !s.isCollapsed) { return false; }
    var r = s.getRangeAt(0).cloneRange(); r.selectNodeContents(node); r.setEnd(s.anchorNode, s.anchorOffset);
    return r.toString().length === 0;
  }
  function caretAtEnd(node) {
    var s = window.getSelection(); if (!s.rangeCount || !s.isCollapsed) { return false; }
    var r = s.getRangeAt(0).cloneRange(); r.selectNodeContents(node); r.setStart(s.anchorNode, s.anchorOffset);
    return r.toString().length === 0;
  }
  function focusEnd(node) {
    node.focus();
    var r = document.createRange(); r.selectNodeContents(node); r.collapse(false);
    var s = window.getSelection(); s.removeAllRanges(); s.addRange(r);
  }
  function stripMarker(line) { return line.replace(/^\s*(?:[-*•]\s+\[[ xX]?\]\s*|\[[ xX]?\]\s*|[-*•]\s+|\d+[.)]\s+)/, '').trim(); }

  /* ---------------------------------------------------------------- one checklist */
  function Checklist(root) {
    var self = this;
    this.root = root;
    this.owner = root.getAttribute('data-owner');
    this.ownerId = root.getAttribute('data-owner-id');
    this.url = root.getAttribute('data-url');
    this.reorderUrl = root.getAttribute('data-reorder-url');
    this.itemUrl = root.getAttribute('data-item-url');
    this.can = root.getAttribute('data-can') === '1';
    this.me = parseInt(root.getAttribute('data-me'), 10) || 0;
    this.meName = root.getAttribute('data-me-name') || 'You';
    this.v = root.getAttribute('data-v') || '';
    this.list = $('[data-cl-list]', root);
    this.countEl = $('[data-cl-count]', root);
    this.barEl = $('[data-cl-bar]', root);
    this.pending = 0;
    this.queue = Promise.resolve();
    this.known = {};          // id -> {done, by}: to tell what a teammate changed
    this.tmp = 0;
    this.draft = null;
    this.dragging = null;
    this.fetching = false;

    var seed = []; try { seed = JSON.parse($('[data-cl-seed]', root).textContent || '[]'); } catch (e) { seed = []; }
    root.classList.add('is-booting');
    seed.forEach(function (it) { var li = self.build(it); self.list.appendChild(li); self.known[it.id] = { done: !!it.done, by: it.by }; });
    if (this.can) { this.draft = this.buildDraft(); this.list.appendChild(this.draft); }
    this.summary();
    requestAnimationFrame(function () { requestAnimationFrame(function () { root.classList.remove('is-booting'); }); });
    this.wire();
  }

  /* ---- building rows ---- */
  Checklist.prototype.build = function (it) {
    var li = el('li', 'cl'), can = this.can;
    li.setAttribute('data-id', it.id);
    li.innerHTML =
      '<span class="cl__grip" aria-hidden="true">' + ICON.grip + '</span>' +
      '<button type="button" class="cl__box" role="checkbox" aria-checked="false" aria-label="Done"' + (can ? '' : ' disabled') + '>' + ICON.check + '</button>' +
      '<div class="cl__main"><span class="cl__text" role="textbox" aria-label="To-do" spellcheck="true"></span><small class="cl__by"></small></div>' +
      '<button type="button" class="cl__del" aria-label="Remove this to-do" title="Remove"' + (can ? '' : ' hidden') + '>' + ICON.trash + '</button>';
    var t = $('.cl__text', li); t.textContent = it.body; t.contentEditable = can ? 'true' : 'false';
    li._saved = it.body;
    this.paint(li, it);
    return li;
  };
  Checklist.prototype.buildDraft = function () {
    var li = el('li', 'cl cl--draft');
    li.innerHTML =
      '<span class="cl__grip" aria-hidden="true"></span>' +
      '<span class="cl__box cl__box--ghost" aria-hidden="true">' + ICON.plus + '</span>' +
      '<div class="cl__main"><span class="cl__text" role="textbox" aria-label="Add a to-do" data-ph="Add a to-do…" contenteditable="true" spellcheck="true"></span></div>';
    return li;
  };
  Checklist.prototype.paint = function (li, it) {
    var done = !!it.done, box = $('.cl__box', li);
    li.classList.toggle('is-done', done);
    box.setAttribute('aria-checked', done ? 'true' : 'false');
    box.setAttribute('aria-label', done ? 'Mark as not done' : 'Mark as done');
    var by = $('.cl__by', li);
    if (done && it.by) { by.textContent = 'Done by ' + (it.by.id === this.me ? 'you' : firstName(it.by.name)) + (it.at ? ' · ' + when(it.at) : ''); }
    else if (done) { by.textContent = 'Done'; }
    else { by.textContent = ''; }
    li._by = done ? (it.by || null) : null; li._at = it.at || null;
  };
  Checklist.prototype.itemsEls = function () { return $$('.cl:not(.cl--draft)', this.list); };
  Checklist.prototype.textOf = function (li) { return $('.cl__text', li); };

  /* ---- numbers ---- */
  Checklist.prototype.summary = function () {
    var els = this.itemsEls(), total = els.length, done = els.filter(function (l) { return l.classList.contains('is-done'); }).length;
    if (this.countEl) { this.countEl.textContent = total ? (done === total ? 'All done' : done + '/' + total) : ''; }
    if (this.barEl) { this.barEl.style.width = (total ? Math.round(done / total * 100) : 0) + '%'; this.barEl.parentNode.classList.toggle('is-all', total > 0 && done === total); }
    this.root.classList.toggle('is-empty', total === 0);
    var key = this.owner + ':' + this.ownerId;
    $$('[data-cl-badge="' + key + '"]').forEach(function (b) { b.textContent = total ? done + '/' + total : ''; });
    $$('[data-cl-card="' + key + '"]').forEach(function (c) {
      c.classList.toggle('is-empty', total === 0); c.classList.toggle('is-all', total > 0 && done === total);
      var b = $('b', c); if (b) { b.textContent = done + '/' + total; }
    });
    document.dispatchEvent(new CustomEvent('checklist:change', { detail: { owner: this.owner, id: this.ownerId, done: done, total: total } }));
  };

  /* ---- writes: one at a time, in the order they were made, so a fast typist never gets lines swapped ---- */
  Checklist.prototype.enqueue = function (fn) {
    var self = this;
    this.pending++;
    this.queue = this.queue.then(fn).then(function (res) {
      self.pending--;
      if (self.pending === 0 && res && res.items) { self.v = res.v; self.reconcile(res, false); self.tell(); }
    }, function (err) {
      self.pending--;
      toast(err.message || 'Could not save. Please try again.', { bad: true });
      self.pull(true);
    });
    return this.queue;
  };
  Checklist.prototype.tell = function () { if (channel) { try { channel.postMessage({ o: this.owner, id: this.ownerId }); } catch (e) {} } };
  Checklist.prototype.realId = function (li) { var id = li && li.getAttribute('data-id'); return id && id.charAt(0) !== 't' ? parseInt(id, 10) : 0; };

  /* ---- actions ---- */
  Checklist.prototype.addAfter = function (afterLi, text, opts) {
    var self = this; opts = opts || {};
    var tmp = this.build({ id: 't' + (++this.tmp), body: text, done: !!opts.done, by: opts.done ? { id: this.me, name: this.meName } : null, at: opts.done ? new Date().toISOString() : null });
    tmp.classList.add('is-new');
    if (afterLi && afterLi.parentNode === this.list) { this.list.insertBefore(tmp, afterLi.nextSibling); }
    else if (opts.first) { this.list.insertBefore(tmp, this.list.firstChild); }
    else { this.list.insertBefore(tmp, this.draft || null); }
    this.summary();
    this.enqueue(function () {
      var after = opts.first ? 0 : self.realId(afterLi);
      var body = { body: text }; if (after || opts.first) { body.after = after; } if (opts.done) { body.done = true; }
      return request(self.url.replace(/\?.*$/, ''), 'POST', body).then(function (res) {
        if (res.created && res.created[0]) { tmp.setAttribute('data-id', res.created[0]); }
        return res;
      });
    });
    return tmp;
  };
  Checklist.prototype.addMany = function (afterLi, lines) {
    var self = this;
    var tmps = lines.map(function (l, i) {
      var tmp = self.build({ id: 't' + (++self.tmp), body: l, done: false });
      tmp.classList.add('is-new');
      return tmp;
    });
    var ref = afterLi && afterLi.parentNode === this.list ? afterLi.nextSibling : (this.draft || null);
    tmps.forEach(function (t) { self.list.insertBefore(t, ref); });
    this.summary();
    this.enqueue(function () {
      var after = self.realId(afterLi);
      var body = { bodies: lines }; if (after) { body.after = after; }
      return request(self.url.replace(/\?.*$/, ''), 'POST', body).then(function (res) {
        (res.created || []).forEach(function (id, i) { if (tmps[i]) { tmps[i].setAttribute('data-id', id); } });
        return res;
      });
    });
    toast(lines.length === 1 ? 'Added 1 to-do' : 'Added ' + lines.length + ' to-dos');
    return tmps[tmps.length - 1];
  };
  Checklist.prototype.save = function (li) {
    var self = this, t = this.textOf(li), text = clean(t.textContent);
    clearTimeout(li._timer);
    if (text === '') { return; }
    if (text === li._saved) { return; }
    li._saved = text;
    this.enqueue(function () { var id = self.realId(li); if (!id) { return Promise.resolve(null); } return request(self.itemUrl.replace('__ID__', id), 'PATCH', { body: text }); });
  };
  Checklist.prototype.toggle = function (li) {
    var self = this, now = !li.classList.contains('is-done');
    this.paint(li, { done: now, by: now ? { id: this.me, name: this.meName } : null, at: now ? new Date().toISOString() : null });
    this.known[li.getAttribute('data-id')] = { done: now, by: now ? { id: this.me } : null };
    this.summary();
    this.enqueue(function () { var id = self.realId(li); if (!id) { return Promise.resolve(null); } return request(self.itemUrl.replace('__ID__', id), 'PATCH', { done: now }); });
    if (now) { toast('Checked off', { undo: function () { self.toggle(li); } }); }
  };
  Checklist.prototype.remove = function (li, focusPrev) {
    var self = this, t = this.textOf(li), body = clean(t.textContent) || li._saved, done = li.classList.contains('is-done');
    var prev = li.previousElementSibling; while (prev && prev.classList.contains('cl--draft')) { prev = prev.previousElementSibling; }
    var prevText = prev ? this.textOf(prev) : null;
    li.style.maxHeight = li.offsetHeight + 'px';
    requestAnimationFrame(function () { li.classList.add('is-leaving'); });
    setTimeout(function () { if (li.parentNode) { li.parentNode.removeChild(li); self.summary(); } }, 230);
    this.enqueue(function () { var id = self.realId(li); if (!id) { return Promise.resolve(null); } return request(self.itemUrl.replace('__ID__', id), 'DELETE'); });
    if (focusPrev && prevText) { focusEnd(prevText); }
    toast('Removed', { undo: function () { self.addAfter(prev, body, { done: done, first: !prev }); } });
  };
  Checklist.prototype.openDraftAfter = function (li) {
    if (!this.draft) { return; }
    this.list.insertBefore(this.draft, li.nextSibling);
    focusEnd(this.textOf(this.draft));
  };
  Checklist.prototype.draftHome = function () {     // an unused draft goes back to the end of the list
    if (!this.draft || this.draft.parentNode !== this.list) { return; }
    if (this.list.lastElementChild !== this.draft) { this.list.appendChild(this.draft); }
  };
  Checklist.prototype.prevReal = function (li) { var p = li.previousElementSibling; while (p && p.classList.contains('cl--draft')) { p = p.previousElementSibling; } return p; };
  Checklist.prototype.nextReal = function (li) { var n = li.nextElementSibling; while (n && n.classList.contains('cl--draft')) { n = n.nextElementSibling; } return n; };

  /* ---- events ---- */
  Checklist.prototype.wire = function () {
    var self = this, list = this.list;

    list.addEventListener('click', function (e) {
      var li = e.target.closest('.cl'); if (!li || !list.contains(li)) { return; }
      if (e.target.closest('.cl__box') && !li.classList.contains('cl--draft')) { if (self.can) { self.toggle(li); } return; }
      if (e.target.closest('.cl__del')) { self.remove(li, false); return; }
      if (self.can && (e.target.closest('.cl__box--ghost') || (e.target.closest('.cl__main') && !e.target.closest('.cl__text')))) { focusEnd(self.textOf(li)); }
    });

    list.addEventListener('keydown', function (e) {
      var t = e.target.closest('.cl__text'); if (!t || !self.can) { return; }
      var li = t.closest('.cl'), isDraft = li.classList.contains('cl--draft');
      if (e.isComposing) { return; }

      if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
        e.preventDefault(); if (!isDraft) { self.toggle(li); } return;
      }
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        var text = clean(t.textContent);
        if (isDraft) {
          if (!text) { return; }
          var after = self.prevReal(li);
          var created = self.addAfter(after, text, { first: !after });   // goes in above the empty line, which stays right below it
          t.textContent = '';
          focusEnd(t);
          created.scrollIntoView({ block: 'nearest' });
        } else {
          self.save(li);
          self.openDraftAfter(li);
        }
        return;
      }
      if (e.key === 'Backspace' && !clean(t.textContent)) {
        e.preventDefault();
        if (isDraft) { var p = self.prevReal(li); if (p) { focusEnd(self.textOf(p)); } }
        else { self.remove(li, true); }
        return;
      }
      if (e.key === 'ArrowUp' && caretAtStart(t)) { var up = self.prevReal(li); if (up) { e.preventDefault(); focusEnd(self.textOf(up)); } return; }
      if (e.key === 'ArrowDown' && caretAtEnd(t)) {
        var dn = isDraft ? null : (li.nextElementSibling && li.nextElementSibling.classList.contains('cl--draft') ? li.nextElementSibling : self.nextReal(li));
        if (dn) { e.preventDefault(); focusEnd(self.textOf(dn)); }
        return;
      }
      if (e.key === 'Escape') { e.stopPropagation(); t.blur(); }      // first Esc leaves the line, the next one closes the side panel
    });

    list.addEventListener('input', function (e) {
      var t = e.target.closest('.cl__text'); if (!t) { return; }
      var li = t.closest('.cl');
      if (t.textContent.length > 500) { t.textContent = t.textContent.slice(0, 500); focusEnd(t); }
      if (li.classList.contains('cl--draft')) { li.classList.toggle('has-text', t.textContent.length > 0); return; }
      clearTimeout(li._timer); li._timer = setTimeout(function () { self.save(li); }, 700);
    });

    list.addEventListener('focusout', function (e) {
      var t = e.target.closest('.cl__text'); if (!t) { return; }
      var li = t.closest('.cl');
      if (li.classList.contains('cl--draft')) {
        if (!clean(t.textContent)) { t.textContent = ''; li.classList.remove('has-text'); setTimeout(function () { if (!list.contains(document.activeElement) || !document.activeElement.closest('.cl__text')) { self.draftHome(); } }, 0); }
        return;
      }
      if (!clean(t.textContent)) { t.textContent = li._saved; return; }      // never leave an empty line behind: put the old text back
      self.save(li);
    });

    list.addEventListener('paste', function (e) {
      var t = e.target.closest('.cl__text'); if (!t || !self.can) { return; }
      e.preventDefault();
      var raw = (e.clipboardData || window.clipboardData).getData('text') || '';
      var lines = raw.split(/\r?\n/).map(function (l) { return stripMarker(l); }).filter(function (l) { return l !== ''; });
      var li = t.closest('.cl'), isDraft = li.classList.contains('cl--draft');
      if (lines.length <= 1) { document.execCommand('insertText', false, (lines[0] || raw).replace(/[\r\n]+/g, ' ')); return; }
      var anchor = isDraft ? self.prevReal(li) : li;
      if (!isDraft && !clean(t.textContent)) { t.textContent = lines.shift(); li._saved = ''; self.save(li); }
      else if (!isDraft) { document.execCommand('insertText', false, lines.shift()); self.save(li); }
      if (isDraft) { t.textContent = ''; li.classList.remove('has-text'); }
      var last = self.addMany(anchor, lines.slice(0, 50));
      if (isDraft) { focusEnd(self.textOf(self.draft)); }
    });

    list.addEventListener('drop', function (e) { if (e.target.closest('.cl__text')) { e.preventDefault(); } });

    /* drag to re-order (mouse; the handle makes the row draggable) */
    list.addEventListener('mousedown', function (e) {
      var g = e.target.closest('.cl__grip'); if (!g || !self.can) { return; }
      var li = g.closest('.cl'); if (li.classList.contains('cl--draft')) { return; }
      li.draggable = true;
    });
    list.addEventListener('dragstart', function (e) {
      var li = e.target.closest && e.target.closest('.cl'); if (!li || !li.draggable) { return; }
      self.dragging = li; li.classList.add('is-drag');
      try { e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', 'checklist'); } catch (x) {}
    });
    list.addEventListener('dragover', function (e) {
      if (!self.dragging) { return; }
      e.preventDefault();
      var others = self.itemsEls().filter(function (l) { return l !== self.dragging; }), target = null;
      for (var i = 0; i < others.length; i++) { var r = others[i].getBoundingClientRect(); if (e.clientY < r.top + r.height / 2) { target = others[i]; break; } }
      var before = target || self.draft || null;
      if (self.dragging.nextSibling !== before) { self.list.insertBefore(self.dragging, before); }
    });
    list.addEventListener('dragend', function () {
      var li = self.dragging; if (!li) { return; }
      li.classList.remove('is-drag'); li.draggable = false; self.dragging = null;
      var ids = self.itemsEls().map(function (l) { return self.realId(l); }).filter(Boolean);
      self.enqueue(function () { return request(self.reorderUrl, 'POST', { ids: ids }); });
    });
  };

  /* ---- bringing the screen in line with the server ---- */
  Checklist.prototype.reconcile = function (data, remote) {
    var self = this, items = data.items || [], list = this.list;
    var byId = {}; this.itemsEls().forEach(function (l) { byId[l.getAttribute('data-id')] = l; });
    var seen = {}, tickedByOthers = [], changedByOthers = 0;

    items.forEach(function (it) {
      seen[it.id] = true;
      var li = byId[String(it.id)], prev = self.known[it.id];
      if (!li) {
        li = self.build(it); li.classList.add('is-new'); if (remote) { li.classList.add('is-flash'); changedByOthers++; }
        list.insertBefore(li, self.draft || null); byId[String(it.id)] = li;
      } else {
        var t = self.textOf(li);
        if (document.activeElement !== t && clean(t.textContent) !== it.body) { t.textContent = it.body; li._saved = it.body; if (remote) { li.classList.add('is-flash'); changedByOthers++; } }
        else if (document.activeElement !== t) { li._saved = it.body; }
        var wasDone = li.classList.contains('is-done');
        if (wasDone !== !!it.done || (it.done && it.by && (!li._by || li._by.id !== it.by.id))) { self.paint(li, it); }
        if (remote && it.done && !wasDone) { li.classList.add('is-flash'); }
      }
      if (remote && it.done && it.by && it.by.id !== self.me && (!prev || !prev.done)) { tickedByOthers.push({ by: it.by, text: it.body }); }
      self.known[it.id] = { done: !!it.done, by: it.by };
    });

    this.itemsEls().forEach(function (l) {
      var id = l.getAttribute('data-id');
      if (id.charAt(0) === 't') { return; }                       // still being saved
      if (!seen[id]) {
        if (remote) { changedByOthers++; }
        l.style.maxHeight = l.offsetHeight + 'px'; requestAnimationFrame(function () { l.classList.add('is-leaving'); });
        setTimeout(function () { if (l.parentNode) { l.parentNode.removeChild(l); self.summary(); } }, 230);
        delete self.known[id];
      }
    });

    // same order as the server (the empty "add" line is never moved)
    var node = list.firstElementChild;
    items.forEach(function (it) {
      var li = byId[String(it.id)]; if (!li) { return; }
      while (node && (node === self.draft || node.classList.contains('is-leaving'))) { node = node.nextElementSibling; }
      if (node === li) { node = node.nextElementSibling; }
      else if (!(li.contains(document.activeElement))) { list.insertBefore(li, node); }
    });

    this.summary();
    if (remote && tickedByOthers.length) {
      var n = tickedByOthers[0];
      toast(tickedByOthers.length === 1 ? firstName(n.by.name) + ' checked off “' + (n.text.length > 42 ? n.text.slice(0, 40) + '…' : n.text) + '”' : tickedByOthers.length + ' to-dos were checked off by your team');
    } else if (remote && changedByOthers) {
      toast('Updated by your team');
    }
  };

  Checklist.prototype.pull = function (force) {
    var self = this;
    if (this.fetching || (!force && this.pending > 0)) { return; }
    this.fetching = true;
    request(this.url + (this.url.indexOf('?') === -1 ? '?' : '&') + 'v=' + (force ? '' : encodeURIComponent(this.v)), 'GET')
      .then(function (res) {
        if (self.pending > 0 || !res || !res.changed) { return; }
        self.v = res.v; self.reconcile(res, !force);
      })
      .catch(function () {})
      .then(function () { self.fetching = false; });
  };

  /* ---------------------------------------------------------------- start-up and the live loop */
  function init(scope) {
    $$('[data-checklist]', scope || document).forEach(function (root) {
      if (root._checklist) { return; }
      root._checklist = new Checklist(root); instances.push(root._checklist);
    });
  }
  function tick() {
    if (document.hidden) { return; }
    instances = instances.filter(function (c) { return document.documentElement.contains(c.root); });
    instances.forEach(function (c) { c.pull(false); });
  }
  setInterval(tick, POLL_MS);
  document.addEventListener('visibilitychange', function () { if (!document.hidden) { tick(); } });
  window.addEventListener('focus', tick);
  window.addEventListener('online', tick);
  if (channel) { channel.onmessage = function (m) { var d = m.data || {}; instances.forEach(function (c) { if (c.owner === d.o && String(c.ownerId) === String(d.id)) { c.pull(false); } }); }; }
  document.addEventListener('portal:drawer', function (e) { init(e.detail && e.detail.el); });
  // the project page switches tabs without reloading: look again when a tab opens
  document.addEventListener('click', function (e) { if (e.target.closest && e.target.closest('[data-tab]')) { setTimeout(tick, 60); } });

  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', function () { init(document); }); } else { init(document); }
  window.PortalChecklist = { init: init };
}());

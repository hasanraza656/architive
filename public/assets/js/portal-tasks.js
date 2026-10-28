/* ==========================================================================
   Projects and tasks: side panel (autosave, assignees, comments), board drag and drop, quick add,
   "My tasks" tick boxes. Works on the project page, My tasks, the task page and inside chats.
   ========================================================================== */
(function () {
  'use strict';

  var csrf = (document.querySelector('meta[name=csrf-token]') || {}).content || '';
  function $(s, r) { return (r || document).querySelector(s); }
  function $$(s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); }

  var busy = 0;           // writes in flight: the page does not refresh itself from the server while this person's own change is still being saved
  var liveRefresh = function () {};
  function api(url, method, data) {
    var write = method && method !== 'GET'; if (write) { busy++; }
    var opts = { method: method, credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf } };
    if (data) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(data); }
    var p = fetch(url, opts).then(function (r) {
      return r.json().catch(function () { return {}; }).then(function (j) {
        if (!r.ok) { var m = j.message; if (j.errors) { m = [].concat.apply([], Object.keys(j.errors).map(function (k) { return j.errors[k]; }))[0] || m; } var err = new Error(m || 'Something went wrong. Please try again.'); err.status = r.status; throw err; }
        return j;
      });
    });
    return write ? p.then(function (v) { busy--; return v; }, function (e) { busy--; throw e; }) : p;
  }
  window.PortalApi = window.PortalApi || api;

  function html(str) { var t = document.createElement('template'); t.innerHTML = str.trim(); return t.content.firstElementChild; }
  function autosize(el) { el.style.height = 'auto'; el.style.height = (el.scrollHeight + 2) + 'px'; }
  $$('[data-autosize]').forEach(function (el) { el.addEventListener('input', function () { autosize(el); }); });

  /* ---- keep every copy of a task (board card, list row) in sync with the server's answer ---- */
  function applyResult(res) {
    if (!res || !res.id) { return; }
    $$('[data-task-card][data-task-id="' + res.id + '"]').forEach(function (old) {
      var fresh = html(res.card), oldCol = old.closest('[data-col]');
      var board = old.closest('[data-board]');
      if (board && oldCol && oldCol.getAttribute('data-col') !== res.status) {
        board.querySelector('[data-col="' + res.status + '"] [data-col-list]').appendChild(fresh); old.remove();
      } else { old.replaceWith(fresh); }
    });
    $$('[data-task-row][data-task-id="' + res.id + '"]').forEach(function (old) {
      var fresh = html(res.row); old.replaceWith(fresh);
    });
    recount();
    $$('[data-task-panel][data-task-id="' + res.id + '"]').forEach(function (p) {
      var b = $('[data-status-badge]', p);
      if (b) { b.textContent = res.status_label; b.className = 'pbadge ' + toneOf(res.status); }
      var sel = $('[data-field=status]', p); if (sel && sel.value !== res.status) { sel.value = res.status; }
      var act = $('[data-activity]', p); if (act && res.activity) { act.innerHTML = res.activity; }
    });
    document.dispatchEvent(new CustomEvent('portal:task-updated', { detail: res }));
    liveRefresh();
  }
  function toneOf(status) { return 'pbadge--' + ({ backlog: 'grey', todo: 'blue', in_progress: 'amber', review: 'violet', done: 'green' }[status] || 'grey'); }
  function recount() { $$('[data-board] [data-col]').forEach(function (c) { var n = $('[data-count]', c); if (n) { n.textContent = $$('[data-task-card]', c).length; } }); }

  /* ---- open a task in the side drawer ---- */
  function openTask(url) {
    if (!window.PortalDrawer) { location.href = url.replace('/panel', ''); return; }
    window.PortalDrawer.open({ title: 'Task', url: url, key: 'task:' + url });
  }
  window.PortalOpenTask = openTask;

  document.addEventListener('click', function (e) {
    var row = e.target.closest('[data-task-row]');
    if (row && e.target.closest('[data-complete]')) { e.preventDefault(); toggleDone(row); return; }
    var opener = e.target.closest('[data-open-task]') || e.target.closest('[data-task-card]');
    if (opener && !(opener.closest('[data-task-card]') && opener.closest('[data-task-card]').__dragged)) {
      var holder = opener.closest('[data-task-card],[data-task-row]');
      var url = (holder || opener).getAttribute('data-panel');
      if (url) { e.preventDefault(); openTask(url); }
    }
  });
  document.addEventListener('change', function (e) {   // change the status right from a list row
    var sel = e.target.closest && e.target.closest('[data-row-status]'); if (!sel) { return; }
    var row = sel.closest('[data-task-row]'), prev = row.getAttribute('data-status');
    sel.disabled = true;
    api(panelUpdateUrl(row.getAttribute('data-task-id')), 'PATCH', { status: sel.value })
      .then(function (res) { applyResult(res); })
      .catch(function (err) { sel.value = prev; alert(err.message); })
      .then(function () { sel.disabled = false; });
  });
  document.addEventListener('keydown', function (e) {
    var card = e.target.closest && e.target.closest('[data-task-card]');
    if (card && (e.key === 'Enter' || e.key === ' ') && e.target === card) { e.preventDefault(); openTask(card.getAttribute('data-panel')); }
  });

  function toggleDone(row) {
    var done = row.getAttribute('data-status') === 'done', id = row.getAttribute('data-task-id');
    var btn = $('[data-complete]', row); btn.disabled = true; row.classList.toggle('is-done', !done);
    api(panelUpdateUrl(id), 'PATCH', { status: done ? 'todo' : 'done' })
      .then(function (res) { applyResult(res); })
      .catch(function (err) { row.classList.toggle('is-done', done); alert(err.message); })
      .then(function () { btn.disabled = false; });
  }
  function panelUpdateUrl(id) { return '/admin/tasks/' + id; }

  /* ---- the panel itself ---- */
  function initPanel(panel) {
    if (!panel || panel.__ready) { return; }
    panel.__ready = true;
    var updateUrl = panel.getAttribute('data-update-url'), saved = $('[data-saved]', panel), timer = null, pending = {};
    $$('[data-autosize]', panel).forEach(function (el) { autosize(el); el.addEventListener('input', function () { autosize(el); }); });
    if (!updateUrl) { return; }

    function say(t, bad) { if (!saved) { return; } saved.textContent = t; saved.className = 'tpanel__saved' + (bad ? ' is-bad' : ''); if (!bad) { clearTimeout(say.t); say.t = setTimeout(function () { saved.textContent = ''; }, 2200); } }
    function flush() {
      var data = pending; pending = {}; if (!Object.keys(data).length) { return; }
      say('Saving…');
      api(updateUrl, 'PATCH', data).then(function (res) { say('Saved'); applyResult(res); }).catch(function (err) { say(err.message, true); });
    }
    function queue(field, value, delay) { pending[field] = value; clearTimeout(timer); timer = setTimeout(flush, delay); }

    panel.addEventListener('input', function (e) {
      var f = e.target.getAttribute && e.target.getAttribute('data-field');
      if (f === 'title' || f === 'description') { queue(f, e.target.value, 700); }
    });
    panel.addEventListener('blur', function (e) { var f = e.target.getAttribute && e.target.getAttribute('data-field'); if (f === 'title' || f === 'description') { clearTimeout(timer); flush(); } }, true);
    panel.addEventListener('keydown', function (e) { if (e.target.getAttribute && e.target.getAttribute('data-field') === 'title' && e.key === 'Enter') { e.preventDefault(); e.target.blur(); } });
    panel.addEventListener('change', function (e) {
      var f = e.target.getAttribute && e.target.getAttribute('data-field');
      if (f === 'status' || f === 'priority' || f === 'due_date') { queue(f, e.target.value || null, 0); flush(); }
      if (e.target.closest && e.target.closest('[data-assignees]') && e.target.type === 'checkbox') { assignees(); }
    });

    function assignees() {
      var box = $('[data-assignees]', panel), checked = $$('input:checked', box);
      var chips = $('[data-assignee-chips]', panel); chips.innerHTML = '';
      checked.forEach(function (c) {
        var s = document.createElement('span'); s.className = 'achip';
        s.innerHTML = '<span class="pavatar pavatar--xs">' + (c.getAttribute('data-avatar') ? '<img src="' + c.getAttribute('data-avatar') + '" alt="">' : c.getAttribute('data-initials')) + '</span>';
        s.appendChild(document.createTextNode(c.getAttribute('data-name'))); chips.appendChild(s);
      });
      $('[data-assign-label]', box).textContent = checked.length ? 'Change' : 'Assign people';
      queue('assignee_ids', checked.map(function (c) { return parseInt(c.value, 10); }), 400);
    }
    document.addEventListener('click', function (e) { $$('details[data-assignees][open]', panel).forEach(function (d) { if (!d.contains(e.target)) { d.open = false; } }); });

    var form = $('[data-comment-form]', panel);
    if (form) {
      var ta = $('textarea', form);
      form.addEventListener('submit', function (e) {
        e.preventDefault(); var text = ta.value.trim(); if (!text) { return; }
        var btn = $('button', form); btn.disabled = true;
        api(panel.getAttribute('data-comment-url'), 'POST', { body: text }).then(function (res) { ta.value = ''; autosize(ta); $('[data-activity]', panel).innerHTML = res.activity; })
          .catch(function (err) { say(err.message, true); }).then(function () { btn.disabled = false; });
      });
      ta.addEventListener('keydown', function (e) { if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); form.requestSubmit(); } });
    }
    var del = $('[data-delete]', panel);
    if (del) {
      del.addEventListener('click', function () {
        if (!window.confirm('Delete this task for good? Its comments go with it.')) { return; }
        api(panel.getAttribute('data-delete-url'), 'DELETE').then(function () {
          var id = panel.getAttribute('data-task-id');
          $$('[data-task-card][data-task-id="' + id + '"], [data-task-row][data-task-id="' + id + '"]').forEach(function (n) { n.remove(); });
          recount(); liveRefresh();
          if (window.PortalDrawer && window.PortalDrawer.isOpen() && panel.closest('.pdrawer')) { window.PortalDrawer.close(); } else { location.href = (document.querySelector('.crumb') || {}).href || '/admin/projects'; }
        }).catch(function (err) { say(err.message, true); });
      });
    }
  }
  $$('[data-task-panel]').forEach(initPanel);
  document.addEventListener('portal:drawer', function (e) { $$('[data-task-panel]', e.detail.el).forEach(initPanel); });

  /* ---- quick task dialog on My tasks ---- */
  var qt = $('[data-quick-task]');
  if (qt) {
    qt.addEventListener('submit', function (e) {
      e.preventDefault();
      var fd = new FormData(qt), err = $('[data-quick-error]', qt), btn = $('button[type=submit]', qt), project = fd.get('project');
      var payload = { title: fd.get('title'), priority: fd.get('priority'), due_date: fd.get('due_date') || null };
      if (fd.get('mine') && window.PortalMe) { payload.assignee_ids = [window.PortalMe]; }
      err.hidden = true; btn.disabled = true;
      api(qt.getAttribute('data-url-template').replace('__ID__', project), 'POST', payload)
        .then(function () { location.reload(); })
        .catch(function (ex) { err.textContent = ex.message; err.hidden = false; btn.disabled = false; });
    });
  }

  /* ---- board: quick add + drag and drop ---- */
  var board = $('[data-board]');
  if (!board) { return; }
  var moveTpl = board.getAttribute('data-move-url-template'), addUrl = board.getAttribute('data-add-url');

  /* ---- live: board, list, counts and progress stay in step with the server (own changes at once, other people's within seconds) ---- */
  var liveUrl = board.getAttribute('data-live-url'), ver = board.getAttribute('data-v') || '', listPane = $('[data-list-pane]'), inflight = false, soon = null;
  function applyLive(d) {
    Object.keys(d.cols).forEach(function (st) {
      var list = $('[data-col="' + st + '"] [data-col-list]', board);
      if (list && list.__h !== d.cols[st]) { list.innerHTML = d.cols[st]; list.__h = d.cols[st]; }
    });
    if (listPane && listPane.__h !== d.list) { listPane.innerHTML = d.list; listPane.__h = d.list; }
    recount();
    var lc = $('[data-list-count]'); if (lc) { lc.textContent = d.total; }
    var bar = $('[data-prog-bar]'), txt = $('[data-prog-text]');
    if (bar) { bar.setAttribute('aria-label', d.progress + '% done'); var sp = $('span', bar); if (sp) { sp.style.width = d.progress + '%'; } }
    if (txt) { txt.textContent = d.progress + '% · ' + d.done + ' of ' + d.total + ' tasks done'; }
  }
  function refresh(force) {
    if (!liveUrl || inflight || busy > 0 || drag || (document.hidden && !force)) { return Promise.resolve(); }
    inflight = true;
    return api(liveUrl + (force ? '' : '?v=' + encodeURIComponent(ver)), 'GET')
      .then(function (d) { if (d.changed) { applyLive(d); } ver = d.v; })
      .catch(function () { /* offline for a moment: the next poll tries again */ })
      .then(function () { inflight = false; });
  }
  liveRefresh = function () { clearTimeout(soon); soon = setTimeout(function () { refresh(true); }, 150); };
  setInterval(function () { refresh(false); }, 4000);
  document.addEventListener('visibilitychange', function () { if (!document.hidden) { refresh(false); } });
  window.addEventListener('focus', function () { refresh(false); });

  $$('[data-quick-add]', board).forEach(function (form) {
    var open = $('[data-add-open]', form), box = $('.bcol__addbox', form), ta = $('textarea', form), col = form.closest('[data-col]');
    function toggle(on) { box.hidden = !on; open.hidden = on; if (on) { ta.focus(); } else { ta.value = ''; } }
    open.addEventListener('click', function () { toggle(true); });
    $('[data-add-cancel]', form).addEventListener('click', function () { toggle(false); });
    ta.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); } if (e.key === 'Escape') { toggle(false); } });
    form.addEventListener('submit', function (e) {
      e.preventDefault(); var title = ta.value.trim(); if (!title) { return; }
      var btn = $('button[type=submit]', form); btn.disabled = true;
      api(addUrl, 'POST', { title: title, status: col.getAttribute('data-col') }).then(function (res) {
        $('[data-col-list]', col).appendChild(html(res.card)); recount(); ta.value = ''; ta.focus(); liveRefresh();
      }).catch(function (err) { alert(err.message); }).then(function () { btn.disabled = false; });
    });
  });

  var drag = null;
  board.addEventListener('pointerdown', function (e) {
    var card = e.target.closest('[data-task-card]');
    if (!card || e.button > 0 || e.target.closest('a, button, input, textarea, select')) { return; }
    var touch = e.pointerType === 'touch', sx = e.clientX, sy = e.clientY, started = false, holdTimer = null;
    card.__dragged = false;

    function begin() {
      started = true; card.__dragged = true;
      var r = card.getBoundingClientRect();
      var ghost = card.cloneNode(true); ghost.classList.add('tcard--ghost'); ghost.style.width = r.width + 'px'; document.body.appendChild(ghost);
      var ph = document.createElement('div'); ph.className = 'tcard-placeholder'; ph.style.height = r.height + 'px';
      card.parentNode.insertBefore(ph, card); card.hidden = true; card.classList.add('is-dragging');
      drag = { card: card, ghost: ghost, ph: ph, dx: sx - r.left, dy: sy - r.top, from: card.closest('[data-col]').getAttribute('data-col') };
      document.body.classList.add('is-dragging-task');
      place(e);
    }
    function place(ev) {
      drag.ghost.style.transform = 'translate(' + (ev.clientX - drag.dx) + 'px,' + (ev.clientY - drag.dy) + 'px) rotate(2deg)';
      var el = document.elementFromPoint(ev.clientX, ev.clientY), col = el && el.closest('[data-col]');
      $$('.bcol', board).forEach(function (c) { c.classList.toggle('is-over', c === col); });
      if (!col) { return; }
      var list = $('[data-col-list]', col), after = null;
      $$('[data-task-card]:not(.is-dragging)', list).some(function (c) { var b = c.getBoundingClientRect(); if (ev.clientY < b.top + b.height / 2) { after = c; return true; } return false; });
      if (after) { list.insertBefore(drag.ph, after); } else { list.appendChild(drag.ph); }
      // auto-scroll the board sideways near its edges
      var br = board.getBoundingClientRect();
      if (ev.clientX > br.right - 60) { board.scrollLeft += 14; } else if (ev.clientX < br.left + 60) { board.scrollLeft -= 14; }
    }
    function move(ev) {
      if (!started) {
        if (!touch && Math.abs(ev.clientX - sx) + Math.abs(ev.clientY - sy) > 6) { begin(); }
        return;
      }
      ev.preventDefault(); place(ev);
    }
    function up(ev) {
      clean();
      if (!started || !drag) { return; }
      var d = drag; drag = null; started = false;
      var col = d.ph.closest('[data-col]');
      if (col) {
        var list = d.ph.parentNode, idx = $$('[data-task-card], .tcard-placeholder', list).indexOf(d.ph), status = col.getAttribute('data-col');
        d.ph.replaceWith(d.card); d.card.hidden = false; d.card.classList.remove('is-dragging'); d.card.setAttribute('data-status', status);
        recount();
        api(moveTpl.replace('__ID__', d.card.getAttribute('data-task-id')), 'POST', { status: status, index: idx }).then(applyResult).catch(function (err) { alert(err.message); location.reload(); });
      } else { d.ph.replaceWith(d.card); d.card.hidden = false; d.card.classList.remove('is-dragging'); }
      d.ghost.remove(); document.body.classList.remove('is-dragging-task'); $$('.bcol', board).forEach(function (c) { c.classList.remove('is-over'); });
      setTimeout(function () { card.__dragged = false; }, 0);
    }
    function early(ev) { if (!started && Math.abs(ev.clientX - sx) + Math.abs(ev.clientY - sy) > 12) { clean(); } }   // the finger is scrolling, not holding
    function clean() { clearTimeout(holdTimer); document.removeEventListener('pointermove', move); document.removeEventListener('pointermove', early); document.removeEventListener('pointerup', up); document.removeEventListener('pointercancel', up); }
    if (touch) { document.addEventListener('pointermove', early); holdTimer = setTimeout(function () { document.addEventListener('pointermove', move, { passive: false }); begin(); }, 280); document.addEventListener('pointerup', up); document.addEventListener('pointercancel', up); }
    else { document.addEventListener('pointermove', move); document.addEventListener('pointerup', up); }
  });
  board.addEventListener('contextmenu', function (e) { if (document.body.classList.contains('is-dragging-task')) { e.preventDefault(); } });
}());

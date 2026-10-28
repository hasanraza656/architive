/* ==========================================================================
   Right-hand side drawer shared by the whole portal (task details, project cards, thread view, chat members...).
   window.PortalDrawer.open({ title, url | html, key, onClose })  ·  .close()  ·  .isOpen()  ·  .body
   Content loaded by URL is plain HTML. After it is inserted a "portal:drawer" event fires on document
   with detail { el, key } so the page that opened it can wire up its own behaviour.
   ========================================================================== */
(function () {
  'use strict';

  var scrim = null, aside = null, bodyEl = null, titleEl = null, closeBtn = null, state = { key: null, onClose: null, last: null, token: 0 };

  function build() {
    if (aside) { return; }
    scrim = document.createElement('div'); scrim.className = 'pdrawer-scrim'; scrim.hidden = true;
    aside = document.createElement('aside'); aside.className = 'pdrawer'; aside.hidden = true; aside.setAttribute('role', 'dialog'); aside.setAttribute('aria-labelledby', 'pdrawerTitle'); aside.tabIndex = -1;
    aside.innerHTML = '<header class="pdrawer__head"><h2 class="pdrawer__title" id="pdrawerTitle"></h2><button class="pdrawer__x" type="button" aria-label="Close panel"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg></button></header><div class="pdrawer__body"></div>';
    document.body.appendChild(scrim); document.body.appendChild(aside);
    bodyEl = aside.querySelector('.pdrawer__body'); titleEl = aside.querySelector('.pdrawer__title'); closeBtn = aside.querySelector('.pdrawer__x');
    closeBtn.addEventListener('click', close);
    scrim.addEventListener('click', close);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && isOpen() && !document.querySelector('dialog[open], .cv[data-open]')) { close(); } });
    API.body = bodyEl;
  }

  function isOpen() { return !!aside && !aside.hidden; }

  function skeleton() { return '<div class="pdrawer__skel" aria-busy="true"><i></i><i></i><i></i><i></i></div>'; }

  function show(opts) {
    build();
    if (isOpen() && state.onClose && state.key !== opts.key) { try { state.onClose(); } catch (e) {} }
    state.key = opts.key || null; state.onClose = opts.onClose || null;
    if (!isOpen()) { state.last = document.activeElement; }
    titleEl.textContent = opts.title || '';
    aside.hidden = false; scrim.hidden = false;
    document.body.classList.add('drawer-open');
    requestAnimationFrame(function () { aside.classList.add('is-open'); scrim.classList.add('is-open'); });
  }

  function inject(html, key) {
    bodyEl.innerHTML = html; bodyEl.scrollTop = 0;
    document.dispatchEvent(new CustomEvent('portal:drawer', { detail: { el: bodyEl, key: key } }));
  }

  function open(opts) {
    show(opts);
    var token = ++state.token;
    if (opts.html != null) { inject(opts.html, opts.key); return Promise.resolve(); }
    bodyEl.innerHTML = skeleton();
    return fetch(opts.url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' } })
      .then(function (r) { if (!r.ok) { throw new Error(r.status === 403 ? 'You do not have access to this.' : (r.status === 404 ? 'This item no longer exists.' : 'Could not load.')); } return r.text(); })
      .then(function (html) { if (token === state.token) { inject(html, opts.key); } })
      .catch(function (e) { if (token === state.token) { bodyEl.innerHTML = '<div class="pdrawer__err"><b>' + (e.message || 'Could not load.') + '</b><button class="pbtn pbtn--ghost pbtn--sm" type="button" data-retry>Try again</button></div>'; bodyEl.querySelector('[data-retry]').addEventListener('click', function () { open(opts); }); } });
  }

  function close() {
    if (!isOpen()) { return; }
    aside.classList.remove('is-open'); scrim.classList.remove('is-open');
    document.body.classList.remove('drawer-open');
    var done = function () { aside.hidden = true; scrim.hidden = true; bodyEl.innerHTML = ''; };
    setTimeout(done, 240);
    if (state.onClose) { try { state.onClose(); } catch (e) {} }
    state.onClose = null; state.key = null; state.token++;
    if (state.last && state.last.focus) { try { state.last.focus({ preventScroll: true }); } catch (e) {} }
    document.dispatchEvent(new CustomEvent('portal:drawer-close'));
  }

  var API = { open: open, close: close, isOpen: isOpen, body: null, setTitle: function (t) { if (titleEl) { titleEl.textContent = t; } } };
  window.PortalDrawer = API;
}());

/* ==========================================================================
   Order conversation (customer + team): messages, files, voice, screen recordings, reactions, threads,
   @people and #task/#project tags, group members. Plain HTTP polling today; to go real-time later keep apply()
   and call it from a websocket handler instead of poll().

   Loaded as a classic script. Heavy parts load on demand (chat-media.js: recorders, chat-viewer.js: picture and PDF
   viewers with annotations) so the chat itself stays light. Markup + config come from portal/shared/chat.blade.php.
   ========================================================================== */
(function () {
  'use strict';

  var root = document.querySelector('[data-chat]');
  if (!root) { return; }

  var C = {}; try { C = JSON.parse(root.getAttribute('data-config')); } catch (e) { return; }
  var csrf = (document.querySelector('meta[name=csrf-token]') || {}).content || '';
  var list = root.querySelector('[data-chat-list]'), earlierBtn = root.querySelector('[data-chat-earlier]'), jumpBtn = root.querySelector('[data-chat-jump]');
  var touchDevice = window.matchMedia('(pointer: coarse)').matches;

  /* ------------------------------------------------------------------ helpers */
  function $(s, r) { return (r || document).querySelector(s); }
  function $$(s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); }
  function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
  function url(tpl, id) { return tpl.replace('__ID__', id); }
  function fmtTime(d) { return new Intl.DateTimeFormat(undefined, { timeStyle: 'short' }).format(d); }
  function dayKey(d) { return d.getFullYear() + '-' + d.getMonth() + '-' + d.getDate(); }
  function dayLabel(d) {
    var n = new Date(), y = new Date(Date.now() - 864e5);
    if (dayKey(d) === dayKey(n)) { return 'Today'; }
    if (dayKey(d) === dayKey(y)) { return 'Yesterday'; }
    return new Intl.DateTimeFormat(undefined, { weekday: 'short', month: 'short', day: 'numeric' }).format(d);
  }
  function ago(iso) {
    var s = Math.max(0, (Date.now() - new Date(iso).getTime()) / 1000);
    if (s < 60) { return 'just now'; } if (s < 3600) { return Math.floor(s / 60) + ' min ago'; } if (s < 86400) { return Math.floor(s / 3600) + ' h ago'; }
    return new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric' }).format(new Date(iso));
  }
  function mmss(sec) { sec = Math.max(0, Math.round(sec || 0)); return Math.floor(sec / 60) + ':' + ('0' + (sec % 60)).slice(-2); }
  function linkify(t) { return t.replace(/(https?:\/\/[^\s<]+[^\s<.,;:!?)])/g, '<a href="$1" target="_blank" rel="noopener noreferrer nofollow">$1</a>'); }

  var ICON = {
    reply: '<path d="M9 14 4 9l5-5"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/>',
    smile: '<circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><path d="M9 9h.01M15 9h.01"/>',
    copy: '<rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
    play: '<polygon points="6 3 20 12 6 21 6 3"/>', pause: '<rect x="14" y="4" width="4" height="16" rx="1"/><rect x="6" y="4" width="4" height="16" rx="1"/>',
    download: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/>',
    file: '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/>',
    x: '<path d="M18 6 6 18M6 6l12 12"/>', task: '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>',
    layers: '<path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/>',
    pdf: '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>',
    video: '<path d="m16 13 5.22 3.48a.5.5 0 0 0 .78-.42V7.94a.5.5 0 0 0-.78-.42L16 11"/><rect x="2" y="6" width="14" height="12" rx="2"/>',
    'chevron-left': '<path d="m15 18-6-6 6-6"/>', 'chevron-right': '<path d="m9 18 6-6-6-6"/>', check: '<path d="M20 6 9 17l-5-5"/>',
    send: '<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>', stop: '<rect x="5" y="5" width="14" height="14" rx="2"/>',
    maximize: '<path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>',
    pen: '<path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/>',
    highlighter: '<path d="m9 11-6 6v3h9l3-3"/><path d="m22 12-4.6 4.6a2 2 0 0 1-2.8 0l-5.2-5.2a2 2 0 0 1 0-2.8L14 4"/>',
    square: '<rect width="18" height="18" x="3" y="3" rx="2"/>', circle: '<circle cx="12" cy="12" r="10"/>',
    type: '<polyline points="4 7 4 4 20 4 20 7"/><line x1="9" x2="15" y1="20" y2="20"/><line x1="12" x2="12" y1="4" y2="20"/>',
    undo: '<path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/>', redo: '<path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3l3 2.7"/>',
    eraser: '<path d="m7 21-4.3-4.3c-1-1-1-2.5 0-3.4l9.6-9.6c1-1 2.5-1 3.4 0l5.6 5.6c1 1 1 2.5 0 3.4L13 21"/><path d="M22 21H7"/><path d="m5 11 9 9"/>',
    'zoom-in': '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="M11 8v6M8 11h6"/>', 'zoom-out': '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="M8 11h6"/>',
    'move-right': '<path d="M18 8 22 12 18 16"/><path d="M2 12h20"/>', mic: '<path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><path d="M12 19v3"/>'
  };
  function ico(n, cls) { return '<svg class="ic ' + (cls || '') + '" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + ICON[n] + '</svg>'; }
  function avatarHtml(p, cls) { return '<span class="pavatar ' + (cls || 'pavatar--sm') + '" aria-hidden="true">' + (p.avatar ? '<img src="' + esc(p.avatar) + '" alt="" loading="lazy">' : esc(p.initials || '?')) + '</span>'; }

  function request(method, u, data, opts) {
    var o = { method: method, credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf } };
    if (data) { o.headers['Content-Type'] = 'application/json'; o.body = JSON.stringify(data); }
    return fetch(u, o).then(function (r) {
      if (r.status === 401 || r.status === 419) { location.reload(); }
      return r.json().catch(function () { return {}; }).then(function (j) {
        if (!r.ok) { var e = new Error(firstError(j) || 'Something went wrong. Please try again.'); e.status = r.status; throw e; }
        return j;
      });
    });
  }
  function firstError(j) { if (j && j.errors) { var all = [].concat.apply([], Object.keys(j.errors).map(function (k) { return j.errors[k]; })); if (all.length) { return all[0]; } } return j && j.message; }

  /* ------------------------------------------------------------------ rendering */
  var TOKEN = /([@#])\[([^\]\n]{1,160})\]\((u|t|p):(\d+)\)/g;

  function bodyHtml(m) {
    var text = m.body || '', tags = {}, out = '', last = 0, mt;
    (m.tags || []).forEach(function (t) { tags[t.type + ':' + t.id] = t; });
    TOKEN.lastIndex = 0;
    while ((mt = TOKEN.exec(text))) {
      out += linkify(esc(text.slice(last, mt.index)));
      var code = mt[3], id = mt[4], label = esc(mt[2]);
      if (code === 'u') { out += '<span class="tag tag--user' + (parseInt(id, 10) === C.me.id ? ' tag--me' : '') + '">@' + label + '</span>'; }
      else {
        var type = code === 't' ? 'task' : 'project', info = tags[type + ':' + id];
        out += (!info || info.open)
          ? '<button type="button" class="tag tag--' + type + '" data-tag="' + type + '" data-id="' + id + '">' + ico(type === 'task' ? 'task' : 'layers') + label + '</button>'
          : '<span class="tag tag--locked" title="You do not have access to this">#' + label + '</span>';
      }
      last = TOKEN.lastIndex;
    }
    return (out + linkify(esc(text.slice(last)))).replace(/\n/g, '<br>');
  }

  function plainText(m) { return (m.body || '').replace(TOKEN, '$1$2'); }

  function voiceHtml(f) {
    var peaks = f.peaks && f.peaks.length ? f.peaks : null, n = 36, bars = '';
    for (var i = 0; i < n; i++) { var v = peaks ? peaks[Math.min(peaks.length - 1, Math.floor(i * peaks.length / n))] : 30 + ((i * 37) % 50); bars += '<i style="height:' + Math.max(12, Math.min(100, v)) + '%"></i>'; }
    return '<div class="vp" data-voice data-url="' + esc(f.url) + '" data-dur="' + (f.duration || 0) + '"><button class="vp__btn" type="button" data-play aria-label="Play voice message">' + ico('play') + '</button>' +
      '<div class="vp__wave" data-wave role="slider" tabindex="0" aria-label="Seek" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">' + bars + '</div>' +
      '<span class="vp__time" data-vtime>' + mmss(f.duration) + '</span><button class="vp__speed" type="button" data-speed aria-label="Playback speed">1×</button></div>';
  }
  function videoHtml(f) {
    return '<div class="vid" data-video data-url="' + esc(f.url) + '"><button class="vid__play" type="button" data-vplay aria-label="Play recording">' +
      (f.thumb ? '<img src="' + esc(f.thumb) + '" alt="Screen recording preview" loading="lazy"' + (f.width ? ' width="' + f.width + '"' : '') + '>' : '<span class="vid__ph">' + ico('video') + '</span>') +
      '<span class="vid__btn">' + ico('play') + '</span><span class="vid__dur">' + mmss(f.duration) + '</span></button>' +
      '<a class="vid__dl" href="' + esc(f.url) + '" download aria-label="Download recording">' + ico('download') + '</a></div>';
  }
  function pdfHtml(f) {
    return '<button class="pdfc" type="button" data-view="pdf" data-file="' + f.id + '"><span class="pdfc__thumb">' + (f.thumb ? '<img src="' + esc(f.thumb) + '" alt="" loading="lazy">' : ico('pdf')) + '</span>' +
      '<span class="pdfc__meta"><b>' + esc(f.name) + '</b><small>PDF' + (f.pages ? ' · ' + f.pages + (f.pages === 1 ? ' page' : ' pages') : '') + ' · ' + esc(f.size) + (f.annotated_of ? ' · annotated copy' : '') + '</small><em>Open and annotate</em></span></button>';
  }
  function filesHtml(m) {
    if (!m.files || !m.files.length) { return ''; }
    var imgs = m.files.filter(function (f) { return f.kind === 'image'; }), html = '';
    if (imgs.length) {
      html += '<div class="imgs imgs--' + Math.min(imgs.length, 4) + '">' + imgs.map(function (f) {
        return '<button class="att-img" type="button" data-view="image" data-file="' + f.id + '" aria-label="Open picture ' + esc(f.name) + '"><img src="' + esc(f.thumb || f.preview) + '" alt="' + esc(f.name) + '" loading="lazy" decoding="async"' + (f.width && f.height ? ' width="' + f.width + '" height="' + f.height + '"' : '') + '>' + (f.annotated_of ? '<span class="att-img__tag">Annotated</span>' : '') + '</button>';
      }).join('') + '</div>';
    }
    m.files.forEach(function (f) {
      if (f.kind === 'voice') { html += voiceHtml(f); }
      else if (f.kind === 'video') { html += videoHtml(f); }
      else if (f.kind === 'pdf') { html += pdfHtml(f); }
      else if (f.kind !== 'image') { html += '<a class="att-file" href="' + esc(f.url) + '" download>' + ico('file') + '<span>' + esc(f.name) + '</span><small>' + esc(f.size) + '</small></a>'; }
    });
    return '<div class="msg__media">' + html + '</div>';
  }
  function reactionsHtml(m, withAdd) {
    var rx = (m.reactions || []).map(function (r) { return '<button class="rx' + (r.mine ? ' is-mine' : '') + '" type="button" data-react="' + esc(r.emoji) + '" title="' + esc(r.users.join(', ')) + '"><span>' + esc(r.emoji) + '</span><b>' + r.count + '</b></button>'; }).join('');
    return rx || withAdd ? '<div class="rxs" data-rxs>' + rx + '</div>' : '';
  }
  function threadHtml(m) {
    if (!m.thread) { return ''; }
    return '<button class="msg__replies" type="button" data-thread-open><span class="avs">' + (m.thread.people || []).map(function (p) { return avatarHtml(p, 'pavatar--xs'); }).join('') + '</span><b>' + m.thread.count + (m.thread.count === 1 ? ' reply' : ' replies') + '</b><small>last reply ' + ago(m.thread.last) + '</small></button>';
  }
  function toolsHtml(m, inThread) {
    var quick = C.reactions.slice(0, 3).map(function (e) { return '<button type="button" data-react="' + esc(e) + '" aria-label="React ' + esc(e) + '">' + esc(e) + '</button>'; }).join('');
    return '<div class="msg__tools" role="toolbar" aria-label="Message actions">' + quick +
      '<button type="button" data-react-picker aria-label="More reactions">' + ico('smile') + '</button>' +
      (inThread ? '' : '<button type="button" data-thread-open aria-label="Reply in thread" title="Reply in thread">' + ico('reply') + '</button>') +
      (m.body ? '<button type="button" data-copy aria-label="Copy text" title="Copy text">' + ico('copy') + '</button>' : '') + '</div>';
  }

  var TONE = { pending: 'amber', active: 'blue', delivered: 'violet', completed: 'green', cancelled: 'red' };
  var offer = null;
  function paintOffers() {
    var cards = $$('.offer-card', list);
    cards.forEach(function (card, i) {
      var latest = i === cards.length - 1;
      if (!offer) { card.innerHTML = ''; return; }
      var due = offer.due ? '<span>Delivery due ' + new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(new Date(offer.due)) + '</span>' : '';
      var h = '<div class="offer__top"><span class="offer__tag">' + (latest ? 'Custom offer' : 'Earlier version') + '</span><span class="pbadge pbadge--' + (TONE[offer.status] || 'grey') + '">' + esc(offer.status_label) + '</span></div>' +
        '<h4 class="offer__title">' + esc(offer.title) + '</h4><div class="offer__price">' + esc(offer.total) + '</div>' +
        '<p class="offer__meta"><span>' + offer.items + (offer.items === 1 ? ' item' : ' items') + '</span>' + due + '</p>';
      if (latest) {
        h += '<div class="offer__act">';
        if (offer.can_pay) { h += '<form method="post" action="' + offer.pay_url + '"><input type="hidden" name="_token" value="' + csrf + '"><button class="pbtn pbtn--primary" type="submit">Pay ' + esc(offer.total) + ' &amp; start</button></form>'; }
        h += '<a class="pbtn pbtn--ghost pbtn--sm" href="' + offer.view_url + '">View details</a><a class="pbtn pbtn--ghost pbtn--sm" href="' + offer.pdf_url + '">PDF</a></div>';
        if (offer.status === 'pending' && !offer.can_pay) { h += '<p class="offer__note">Waiting for the customer to accept and pay.</p>'; }
        if (offer.can_pay) { h += '<p class="offer__note">Secure payment by Stripe. Your project starts right after.</p>'; }
        if (offer.status === 'active') { h += '<p class="offer__note is-ok">Paid. The order is in progress.</p>'; }
      }
      card.innerHTML = h;
    });
  }

  /** The DOM element of one message. ctx.thread = true inside the thread panel (no "reply in thread" button, no thread summary). */
  function messageEl(m, ctx) {
    ctx = ctx || {};
    var d = new Date(m.time), wrap = document.createElement('div');
    wrap.setAttribute('data-id', m.id); wrap.setAttribute('data-user', m.user_id || '');
    if (m.kind === 'system') { wrap.className = 'msg msg--system'; wrap.innerHTML = '<span>' + esc(m.body) + '</span>'; return wrap; }
    if (m.kind === 'offer') {
      wrap.className = 'msg msg--offer';
      wrap.innerHTML = '<div class="offer-card" role="group" aria-label="Custom offer"></div><div class="msg__foot"><span>' + esc(m.author) + ' · ' + fmtTime(d) + '</span></div>';
      return wrap;
    }
    wrap.className = 'msg' + (m.mine ? ' msg--mine' : '') + (m.role === 'admin' ? ' msg--staff' : '');
    var h = m.mine ? '' : '<span class="msg__av">' + avatarHtml({ avatar: m.avatar, initials: m.initials }, 'pavatar--sm ' + (m.role === 'admin' ? '' : 'pavatar--soft')) + '</span>';
    h += '<div class="msg__bubble">';
    if (!m.mine) { h += '<span class="msg__who">' + esc(m.author) + '</span>'; }
    if (m.body) { h += '<div class="msg__text">' + bodyHtml(m) + '</div>'; }
    h += filesHtml(m) + reactionsHtml(m) + (ctx.thread ? '' : threadHtml(m));
    h += '<div class="msg__foot"><span>' + fmtTime(d) + '</span>' + (m.mine && !ctx.thread ? '<span data-seen></span>' : '') + '</div></div>';
    h += toolsHtml(m, ctx.thread);
    wrap.innerHTML = h;
    wrap.__m = m;
    return wrap;
  }

  /* ------------------------------------------------------------------ state + list handling */
  var msgs = {}, order = [], firstId = 0, lastId = 0, since = null, hasMore = false, polling = false, seenUpTo = 0, mineIds = [], orderStatus = C.status, unseen = 0, idleSince = Date.now(), thread = null;

  function nearBottom() { return list.scrollHeight - list.scrollTop - list.clientHeight < 110; }
  function toBottom() { pinned = true; list.scrollTop = list.scrollHeight; }

  /** day separators + "same sender" grouping, rebuilt after every change to the list */
  function decorate() {
    $$('.chat__day', list).forEach(function (n) { n.remove(); });
    var prev = null, prevDay = '';
    $$('.msg', list).forEach(function (el) {
      var m = el.__m || msgs[el.getAttribute('data-id')]; if (!m) { return; }
      var d = new Date(m.time), k = dayKey(d);
      if (k !== prevDay) { var s = document.createElement('div'); s.className = 'chat__day'; s.textContent = dayLabel(d); list.insertBefore(s, el); prevDay = k; prev = null; }
      var cont = !!(prev && prev.user_id === m.user_id && m.kind === 'text' && prev.kind === 'text' && (d - new Date(prev.time)) < 5 * 60e3);
      el.classList.toggle('msg--cont', cont);
      prev = m;
    });
  }

  function register(m) { if (!msgs[m.id]) { order.push(m.id); } msgs[m.id] = m; }

  /** A message goes where its id says: newest at the bottom, even when a slow poll answers after a newer message was added. */
  function placeByTime(el, id) {
    var all = list.querySelectorAll(':scope > .msg[data-id]');
    for (var i = all.length - 1; i >= 0; i--) {
      if (parseInt(all[i].getAttribute('data-id'), 10) < id) { all[i].after(el); return; }
    }
    if (all.length) { all[0].before(el); } else { list.appendChild(el); }
  }
  function addMessages(arr, prepend) {
    if (!arr.length) { return; }
    arr = arr.slice().sort(function (a, b) { return a.id - b.id; });   // always oldest first, whatever order they arrive in
    var stick = nearBottom(), empty = $('[data-chat-empty]', list); if (empty) { empty.remove(); }
    var anchor = null, oldH = list.scrollHeight, oldTop = list.scrollTop, fresh = 0;
    if (prepend) { anchor = earlierBtn.nextSibling; }
    arr.forEach(function (m) {
      if (msgs[m.id]) { return; }
      register(m);
      var el = messageEl(m); el.__m = m; if (sizeWatch) { sizeWatch.observe(el); }
      if (prepend) { list.insertBefore(el, anchor); } else { placeByTime(el, m.id); if (!m.mine && m.kind !== 'system') { fresh++; } }
      if (m.mine) { mineIds.push(m.id); }
      lastId = Math.max(lastId, m.id); firstId = firstId ? Math.min(firstId, m.id) : m.id;
    });
    decorate(); paintOffers(); paintSeen(); updateJump(false);
    if (prepend) { list.scrollTop = oldTop + (list.scrollHeight - oldH); }
    else if (stick || arr.some(function (m) { return m.mine; })) { toBottom(); }
    else if (fresh) { unseen += fresh; updateJump(true); }
  }

  function replaceMessage(m, inThreadEl) {
    msgs[m.id] = Object.assign(msgs[m.id] || {}, m);
    var sel = '[data-id="' + m.id + '"]';
    $$(sel, inThreadEl || list).forEach(function (old) {
      var fresh = messageEl(msgs[m.id], { thread: !!old.closest('[data-thread-panel]') }); fresh.__m = msgs[m.id];
      // keep a playing voice message / video alive: only swap the parts that can change
      old.replaceWith(fresh);
    });
    if (thread && thread.root && thread.root.id === m.id) { renderThreadRoot(m); }
    paintSeen(); paintOffers();
  }

  function paintSeen() {
    $$('[data-seen]', list).forEach(function (e) { e.textContent = ''; });
    var last = mineIds.length ? mineIds[mineIds.length - 1] : 0;
    if (last && seenUpTo >= last) { var el = $('[data-id="' + last + '"] [data-seen]', list); if (el) { el.textContent = '· Seen'; } }
  }

  function updateJump(show) {
    if (!jumpBtn) { return; }
    var far = !nearBottom();
    jumpBtn.hidden = !(far && (unseen > 0 || list.scrollHeight - list.scrollTop - list.clientHeight > 600));
    $('[data-chat-jump-n]', jumpBtn).textContent = unseen ? unseen + ' new' : '';
    if (!far) { unseen = 0; }
  }
  var pinned = true;   // stays at the newest message while pictures, videos and thumbnails finish loading, until the person scrolls up
  var lastTouch = 0;   // only the person's own wheel / finger / keys / scrollbar decide whether to stay at the bottom, never layout shifts while things load
  ['wheel', 'touchstart', 'touchmove', 'pointerdown', 'keydown'].forEach(function (ev) { list.addEventListener(ev, function () { lastTouch = Date.now(); }, { passive: true }); });
  list.addEventListener('scroll', function () {
    var byPerson = Date.now() - lastTouch < 800;
    if (byPerson) { pinned = nearBottom(); }
    updateJump(false);
    if (byPerson && list.scrollTop < 160 && hasMore && !loadingEarlier) { loadEarlier(); }   // older messages load by themselves when you scroll up
  }, { passive: true });
  list.addEventListener('load', function () { if (pinned) { toBottom(); } }, true);
  list.addEventListener('loadedmetadata', function () { if (pinned) { toBottom(); } }, true);
  var sizeWatch = window.ResizeObserver ? new ResizeObserver(function () { if (pinned) { toBottom(); } }) : null;   // a message that grows (picture decoded, thumbnail loaded) keeps the view at the bottom
  if (sizeWatch) { sizeWatch.observe(list); }   // the chat box itself changing size (fonts, layout, full-screen) keeps the newest message in view
  window.addEventListener('load', function () { if (pinned) { toBottom(); } });
  [300, 1000, 2500].forEach(function (ms) { setTimeout(function () { if (pinned) { toBottom(); } }, ms); });   // settle after the first paint
  if (jumpBtn) { jumpBtn.addEventListener('click', function () { unseen = 0; toBottom(); updateJump(false); }); }

  function showEmpty() {
    if ($('.msg', list) || $('[data-chat-empty]', list)) { return; }
    var e = document.createElement('div'); e.className = 'chat__empty'; e.setAttribute('data-chat-empty', ''); e.innerHTML = root.querySelector('template[data-empty]').innerHTML; list.appendChild(e);
  }

  function noticeUpdated() {
    if ($('[data-status-notice]')) { return; }
    var n = document.createElement('div'); n.className = 'pflash pflash--info'; n.setAttribute('data-status-notice', '');
    n.innerHTML = '<span>This order was just updated.</span> <a class="plink" href="" style="margin-left:.5rem">Refresh the page</a>';
    root.parentNode.insertBefore(n, root);
  }

  function apply(feed, first) {
    if (feed.now) { since = feed.now; }
    (feed.messages || []).forEach(function () {});
    addMessages(feed.messages || [], false);
    (feed.updated || []).forEach(function (m) { if (msgs[m.id]) { replaceMessage(m); } });
    if (feed.offer !== undefined) { offer = feed.offer; }
    paintOffers();
    seenUpTo = Math.max(seenUpTo, feed.seen_up_to || 0); paintSeen();
    if (feed.status && feed.status !== orderStatus && !first) { noticeUpdated(); }
    if (typeof feed.has_more === 'boolean' && first) { hasMore = feed.has_more; earlierBtn.hidden = !hasMore; }
    if (feed.thread && thread && feed.thread.root && thread.root && feed.thread.root.id === thread.root.id) { applyThread(feed.thread); }
    if ((feed.messages || []).length || (feed.updated || []).length) { idleSince = Date.now(); }
    if (first && !(feed.messages || []).length) { showEmpty(); }
  }

  /* ---- polling (slows down when nothing happens, pauses in a hidden tab) ---- */
  var pollTimer = null;
  function pollDelay() { var idle = (Date.now() - idleSince) / 1000; return (idle > 180 ? Math.max(C.poll, 10) : C.poll) * 1000; }
  function poll() {
    if (polling || document.hidden) { schedule(); return; }
    polling = true;
    var q = 'after=' + lastId + (since ? '&since=' + encodeURIComponent(since) : '') + (thread ? '&thread=' + thread.root.id + '&thread_after=' + (thread.lastReply || 0) : '');
    request('GET', C.urls.feed + '?' + q).then(function (feed) { apply(feed, false); }).catch(function () {}).then(function () { polling = false; schedule(); });
  }
  function schedule() { clearTimeout(pollTimer); pollTimer = setTimeout(poll, pollDelay()); }
  function nudge() { idleSince = Date.now(); clearTimeout(pollTimer); pollTimer = setTimeout(poll, 400); }
  document.addEventListener('visibilitychange', function () { if (!document.hidden) { nudge(); } });
  window.addEventListener('focus', nudge);

  /* ---- earlier messages ---- */
  var loadingEarlier = false;
  function loadEarlier() {
    if (!hasMore || loadingEarlier) { return Promise.resolve(); }
    loadingEarlier = true; earlierBtn.disabled = true; earlierBtn.textContent = 'Loading…';
    return request('GET', C.urls.feed + '?before=' + firstId).then(function (feed) {
      hasMore = !!feed.has_more; earlierBtn.hidden = !hasMore; addMessages(feed.messages || [], true);
    }).catch(function () {}).then(function () { loadingEarlier = false; earlierBtn.disabled = false; earlierBtn.textContent = 'Load earlier messages'; });
  }
  earlierBtn.addEventListener('click', loadEarlier);

  /* ------------------------------------------------------------------ emoji popover */
  var emojiPop = null;
  function closeEmoji() { if (emojiPop) { emojiPop.remove(); emojiPop = null; document.removeEventListener('click', outsideEmoji, true); } }
  function outsideEmoji(e) { if (emojiPop && !emojiPop.contains(e.target)) { closeEmoji(); } }
  function openEmoji(anchor, onPick) {
    closeEmoji();
    emojiPop = document.createElement('div'); emojiPop.className = 'emojipop'; emojiPop.setAttribute('role', 'dialog'); emojiPop.setAttribute('aria-label', 'Pick an emoji');
    emojiPop.innerHTML = C.reactions.map(function (e) { return '<button type="button" data-e="' + esc(e) + '">' + esc(e) + '</button>'; }).join('');
    document.body.appendChild(emojiPop);
    var r = anchor.getBoundingClientRect(), w = emojiPop.offsetWidth, h = emojiPop.offsetHeight;
    var left = Math.min(Math.max(8, r.left + r.width / 2 - w / 2), window.innerWidth - w - 8), top = r.top - h - 8 < 8 ? r.bottom + 8 : r.top - h - 8;
    emojiPop.style.left = left + 'px'; emojiPop.style.top = top + 'px';
    emojiPop.addEventListener('click', function (e) { var b = e.target.closest('[data-e]'); if (b) { onPick(b.getAttribute('data-e')); closeEmoji(); } });
    setTimeout(function () { document.addEventListener('click', outsideEmoji, true); }, 0);
  }

  /* ------------------------------------------------------------------ reactions + message actions (main list and thread panel) */
  function react(id, emoji) {
    return request('POST', url(C.urls.react, id), { emoji: emoji }).then(function (m) {
      replaceMessage(m);
      if (thread) { thread.replies.forEach(function (r) { if (r.id === m.id) { Object.assign(r, m); } }); $$('[data-thread-panel] [data-id="' + m.id + '"]').forEach(function (old) { var f = messageEl(m, { thread: true }); old.replaceWith(f); }); }
    }).catch(function (e) { toast(e.message); });
  }

  document.addEventListener('click', function (e) {
    var scope = e.target.closest('[data-chat], [data-thread-panel]'); if (!scope) { return; }
    var msgEl = e.target.closest('.msg[data-id]'), id = msgEl && msgEl.getAttribute('data-id');
    var t;
    if ((t = e.target.closest('[data-react]')) && msgEl) { e.preventDefault(); react(id, t.getAttribute('data-react')); return; }
    if ((t = e.target.closest('[data-react-picker]')) && msgEl) { e.preventDefault(); openEmoji(t, function (em) { react(id, em); }); return; }
    if (e.target.closest('[data-thread-open]') && msgEl) { e.preventDefault(); openThread(parseInt(id, 10)); return; }
    if ((t = e.target.closest('[data-copy]')) && msgEl) { var m = msgs[id] || (msgEl.__m); copy(plainText(m || {})); toast('Copied'); return; }
    if ((t = e.target.closest('[data-tag]'))) { e.preventDefault(); openTag(t.getAttribute('data-tag'), t.getAttribute('data-id')); return; }
    if ((t = e.target.closest('[data-view]'))) { e.preventDefault(); openViewer(t); return; }
    if ((t = e.target.closest('[data-vplay]'))) { e.preventDefault(); playVideo(t.closest('[data-video]')); return; }
    if ((t = e.target.closest('[data-voice] [data-play]'))) { e.preventDefault(); voicePlay(t.closest('[data-voice]')); return; }
    if ((t = e.target.closest('[data-voice] [data-speed]'))) { e.preventDefault(); voiceSpeed(t.closest('[data-voice]')); return; }
    if ((t = e.target.closest('[data-voice] [data-wave]'))) { voiceSeek(t.closest('[data-voice]'), e); return; }
    // touch screens have no hover: a tap on a bubble shows its actions
    if (touchDevice && msgEl && !e.target.closest('a, button, .msg__tools')) { $$('.msg.is-tools', scope).forEach(function (x) { if (x !== msgEl) { x.classList.remove('is-tools'); } }); msgEl.classList.toggle('is-tools'); }
  });

  function copy(text) {
    if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(text); return; }
    var ta = document.createElement('textarea'); ta.value = text; ta.style.cssText = 'position:fixed;opacity:0'; document.body.appendChild(ta); ta.select(); try { document.execCommand('copy'); } catch (e) {} ta.remove();
  }
  var toastEl = null, toastT = null;
  function toast(t) {
    if (!toastEl) { toastEl = document.createElement('div'); toastEl.className = 'chat-toast'; toastEl.setAttribute('role', 'status'); document.body.appendChild(toastEl); }
    toastEl.textContent = t; toastEl.classList.add('is-on'); clearTimeout(toastT); toastT = setTimeout(function () { toastEl.classList.remove('is-on'); }, 2200);
  }

  /* ------------------------------------------------------------------ voice + video players */
  var audio = null, audioHost = null;
  function voicePlay(host) {
    if (audio && audioHost === host) { if (audio.paused) { audio.play(); } else { audio.pause(); } return; }
    if (audio) { audio.pause(); setPlaying(audioHost, false); }
    audio = new Audio(host.getAttribute('data-url')); audio.preload = 'auto'; audioHost = host; audio.playbackRate = parseFloat(host.getAttribute('data-rate') || '1');
    var bars = $$('.vp__wave i', host), dur = parseFloat(host.getAttribute('data-dur')) || 0;
    audio.addEventListener('timeupdate', function () {
      var d = isFinite(audio.duration) && audio.duration > 0 ? audio.duration : dur, p = d ? audio.currentTime / d : 0;
      bars.forEach(function (b, i) { b.classList.toggle('is-played', i / bars.length < p); });
      $('[data-vtime]', host).textContent = mmss(audio.currentTime);
      var w = $('[data-wave]', host); w.setAttribute('aria-valuenow', Math.round(p * 100));
    });
    audio.addEventListener('play', function () { setPlaying(host, true); });
    audio.addEventListener('pause', function () { setPlaying(host, false); });
    audio.addEventListener('ended', function () { setPlaying(host, false); bars.forEach(function (b) { b.classList.remove('is-played'); }); $('[data-vtime]', host).textContent = mmss(dur); audio = null; audioHost = null; });
    audio.addEventListener('error', function () { setPlaying(host, false); toast('This voice message could not be played.'); audio = null; audioHost = null; });
    audio.play().catch(function () {});
  }
  function setPlaying(host, on) { var b = $('[data-play]', host); b.innerHTML = ico(on ? 'pause' : 'play'); b.setAttribute('aria-label', on ? 'Pause' : 'Play voice message'); host.classList.toggle('is-playing', on); }
  function voiceSpeed(host) {
    var order = [1, 1.5, 2], cur = parseFloat(host.getAttribute('data-rate') || '1'), next = order[(order.indexOf(cur) + 1) % order.length];
    host.setAttribute('data-rate', next); $('[data-speed]', host).textContent = next + '×'; if (audio && audioHost === host) { audio.playbackRate = next; }
  }
  function voiceSeek(host, e) {
    var w = $('[data-wave]', host), r = w.getBoundingClientRect(), p = Math.min(1, Math.max(0, (e.clientX - r.left) / r.width));
    if (!(audio && audioHost === host)) { voicePlay(host); }
    var go = function () { var d = isFinite(audio.duration) && audio.duration > 0 ? audio.duration : (parseFloat(host.getAttribute('data-dur')) || 0); if (d) { audio.currentTime = p * d; } };
    if (audio.readyState >= 1) { go(); } else { audio.addEventListener('loadedmetadata', go, { once: true }); }
  }
  /** Recordings open in a pop-up player (like a lightbox) instead of stretching the chat bubble. */
  function playVideo(host) {
    var box = document.createElement('div'); box.className = 'vlb'; box.setAttribute('role', 'dialog'); box.setAttribute('aria-modal', 'true'); box.setAttribute('aria-label', 'Screen recording');
    box.innerHTML = '<div class="vlb__bar"><span>Screen recording</span><a href="' + esc(host.getAttribute('data-url')) + '" download aria-label="Download">' + ico('download') + '</a><button type="button" data-x aria-label="Close">' + ico('x') + '</button></div><div class="vlb__stage"></div>';
    var v = document.createElement('video'); v.src = host.getAttribute('data-url'); v.controls = true; v.autoplay = true; v.playsInline = true; v.preload = 'auto'; v.className = 'vlb__video';
    box.querySelector('.vlb__stage').appendChild(v); document.body.appendChild(box); document.body.classList.add('vlb-open');
    // browser recordings often report an unknown length: nudge the player so the seek bar works
    v.addEventListener('loadedmetadata', function () { if (v.duration === Infinity) { v.currentTime = 1e101; v.addEventListener('timeupdate', function fix() { v.removeEventListener('timeupdate', fix); v.currentTime = 0; }); } });
    function close() { v.pause(); v.removeAttribute('src'); v.load(); box.remove(); document.body.classList.remove('vlb-open'); document.removeEventListener('keydown', key, true); }
    function key(e) { if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); close(); } }
    document.addEventListener('keydown', key, true);
    box.addEventListener('click', function (e) { if (e.target === box || e.target.closest('[data-x]') || e.target.classList.contains('vlb__stage')) { close(); } });
    box.querySelector('[data-x]').focus();
    if (audio) { audio.pause(); }
  }

  /* ------------------------------------------------------------------ lazy modules (recorders, viewers) */
  var modules = {};
  function loadModule(name) {
    if (modules[name]) { return modules[name]; }
    modules[name] = new Promise(function (resolve, reject) {
      var s = document.createElement('script'); s.src = C.base + '/chat-' + name + '.js?v=' + C.version; s.async = true;
      s.onload = function () { resolve(window.PortalChat[name]); }; s.onerror = function () { modules[name] = null; reject(new Error('Could not load.')); };
      document.head.appendChild(s);
    });
    return modules[name];
  }
  function openViewer(btn) {
    var kind = btn.getAttribute('data-view'), fid = parseInt(btn.getAttribute('data-file'), 10), files = [];
    var scope = btn.closest('[data-thread-panel]') ? [btn.closest('[data-thread-panel]')] : [list];
    Object.keys(msgs).map(Number).sort(function (a, b) { return a - b; }).forEach(function (id) { ((msgs[id].files) || []).forEach(function (f) { if (f.kind === kind) { files.push(f); } }); });
    if (thread) { [thread.root].concat(thread.replies).forEach(function (m) { (m.files || []).forEach(function (f) { if (f.kind === kind && !files.some(function (x) { return x.id === f.id; })) { files.push(f); } }); }); }
    var idx = files.findIndex(function (f) { return f.id === fid; });
    loadModule('viewer').then(function (v) { v.open({ kind: kind, files: files, index: Math.max(0, idx), host: api }); }).catch(function (e) { toast(e.message); });
  }

  /* ------------------------------------------------------------------ composer (main chat and thread share it) */
  function Composer(form, opts) {
    var self = this; opts = opts || {};
    var input = $('[data-chat-input]', form), box = $('[data-chat-files]', form), err = $('[data-chat-err]', form), send = $('[data-chat-send]', form), fileIn = $('[data-chat-file]', form),
        taggedBox = $('[data-chat-tagged]', form), pop = $('[data-chat-tagpop]', form), row = $('[data-chat-row]', form);
    var pending = [], picks = [], sending = false, popItems = [], popIndex = 0, popKind = null, popTimer = null, popReq = 0;
    self.form = form; self.input = input;

    function showErr(t) { err.textContent = t || ''; err.hidden = !t; }
    function grow() { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight + 2, 160) + 'px'; }
    input.addEventListener('input', function () { grow(); onType(); saveDraft(); });
    input.addEventListener('click', onType); input.addEventListener('keyup', function (e) { if (['ArrowLeft', 'ArrowRight', 'Home', 'End'].indexOf(e.key) > -1) { onType(); } });
    input.addEventListener('blur', function () { setTimeout(closePop, 150); });

    /* ---- drafts survive a reload (main chat only) ---- */
    var draftKey = opts.draft ? 'pchat-draft-' + C.order : null;
    function saveDraft() { if (draftKey) { try { sessionStorage.setItem(draftKey, input.value); } catch (e) {} } }
    if (draftKey) { try { var d = sessionStorage.getItem(draftKey); if (d) { input.value = d; grow(); } } catch (e) {} }

    /* ---- attachments ---- */
    function limitOk(file) {
      if (pending.length >= C.maxFiles) { showErr('You can attach up to ' + C.maxFiles + ' files per message.'); return false; }
      if (file.size > C.maxMb * 1048576) { showErr('“' + file.name + '” is bigger than the ' + Math.round(C.maxMb) + ' MB limit. Please send a smaller file or a link.'); return false; }
      return true;
    }
    self.addFile = function (file, extra) {
      if (!limitOk(file)) { return false; }
      showErr('');
      var item = { file: file, kind: (extra && extra.kind) || 'file', duration: extra && extra.duration, meta: extra && extra.meta, preview: extra && extra.preview, annotated_of: extra && extra.annotated_of };
      if (/^image\//.test(file.type)) { item.thumb = URL.createObjectURL(file); }
      else if (extra && extra.previewUrl) { item.thumb = extra.previewUrl; }
      pending.push(item); renderChips();
      if (item.kind === 'file' && (file.type === 'application/pdf' || /\.pdf$/i.test(file.name))) { pdfPreview(item); }
      return true;
    };
    function pdfPreview(item) {   // first page as a small picture + page count, rendered in the browser while the person types
      loadModule('viewer').then(function (v) { return v.pdfPreview(item.file); }).then(function (r) { if (r) { item.preview = r.blob; item.meta = { pages: r.pages }; item.thumb = URL.createObjectURL(r.blob); renderChips(); } }).catch(function () {});
    }
    function annotatable(it) {
      if (it.kind !== 'file') { return null; }
      if (/^image\/(png|jpe?g|webp)$/i.test(it.file.type)) { return 'image'; }
      if (it.file.type === 'application/pdf' || /\.pdf$/i.test(it.file.name)) { return 'pdf'; }
      return null;
    }
    /** Open the viewer on a file that is still in the composer; the annotated version replaces it instead of being sent. */
    function annotateBefore(i, kind) {
      var it = pending[i]; if (!it) { return; }
      var url = URL.createObjectURL(it.file);
      loadModule('viewer').then(function (v) {
        v.open({
          kind: kind, index: 0, files: [{ id: 0, name: it.file.name, url: url, ext: (it.file.name.split('.').pop() || '').toLowerCase(), size: Math.max(1, Math.round(it.file.size / 1024)) + ' KB' }],
          applyLabel: 'Attach drawn-on copy',
          onApply: function (r) {
            var f = new File([r.blob], r.name, { type: r.blob.type });
            it.file = f; it.preview = r.extra && r.extra.preview || null; it.meta = r.extra && r.extra.meta || it.meta;
            if (kind === 'image') { it.thumb = URL.createObjectURL(f); } else if (it.preview) { it.thumb = URL.createObjectURL(it.preview); }
            renderChips();
          },
          onClose: function () { URL.revokeObjectURL(url); }
        });
      }).catch(function (e) { toast(e.message); });
    }
    function renderChips() {
      box.innerHTML = '';
      pending.forEach(function (it, i) {
        var c = document.createElement('span'); c.className = 'fchip fchip--' + it.kind;
        var inner = it.thumb ? '<img src="' + it.thumb + '" alt="">' : '<span class="fchip__ic">' + ico(it.kind === 'voice' ? 'play' : (it.kind === 'video' ? 'video' : 'file')) + '</span>';
        var label = it.kind === 'voice' ? 'Voice message · ' + mmss(it.duration) : (it.kind === 'video' ? 'Screen recording · ' + mmss(it.duration) : it.file.name);
        c.innerHTML = inner + '<span class="fchip__n">' + esc(label) + '</span>';
        var annot = annotatable(it);
        if (annot) {
          var a = document.createElement('button'); a.type = 'button'; a.className = 'fchip__ann'; a.setAttribute('aria-label', 'Annotate ' + label); a.title = 'Draw on it before sending'; a.innerHTML = ico('pen');
          a.addEventListener('click', function () { annotateBefore(i, annot); }); c.appendChild(a);
        }
        var x = document.createElement('button'); x.type = 'button'; x.setAttribute('aria-label', 'Remove ' + label); x.innerHTML = '&times;';
        x.addEventListener('click', function () { pending.splice(i, 1); renderChips(); });
        c.appendChild(x); box.appendChild(c);
      });
      box.hidden = !pending.length;
    }
    if (fileIn) {
      var attach = $('[data-chat-attach]', form); if (attach) { attach.addEventListener('click', function () { fileIn.click(); }); }
      fileIn.addEventListener('change', function () { Array.prototype.forEach.call(fileIn.files, function (f) { self.addFile(f); }); fileIn.value = ''; });
    }
    input.addEventListener('paste', function (e) {
      var items = (e.clipboardData && e.clipboardData.files) || []; if (!items.length) { return; }
      e.preventDefault(); Array.prototype.forEach.call(items, function (f) { self.addFile(f.name ? f : new File([f], 'pasted-' + Date.now() + '.png', { type: f.type })); });
    });

    /* ---- emoji, recorders ---- */
    var emojiBtn = $('[data-chat-emoji]', form);
    if (emojiBtn) { emojiBtn.addEventListener('click', function () { openEmoji(emojiBtn, function (em) { insertAtCursor(em); }); }); }
    var voiceBtn = $('[data-chat-voice]', form), screenBtn = $('[data-chat-screen]', form);
    if (voiceBtn) {
      if (!navigator.mediaDevices || !window.MediaRecorder) { voiceBtn.hidden = true; }
      voiceBtn.addEventListener('click', function () { loadModule('media').then(function (m) { m.voice({ host: row, max: C.media.voice_max_seconds, onDone: function (file, meta) { self.addFile(file, { kind: 'voice', duration: meta.duration, meta: { peaks: meta.peaks } }); if (opts.sendVoiceAtOnce !== false && !input.value.trim() && pending.length === 1) { self.submit(); } } }); }).catch(function (e) { toast(e.message); }); });
    }
    if (screenBtn) {
      if (!navigator.mediaDevices || !navigator.mediaDevices.getDisplayMedia || !window.MediaRecorder || touchDevice) { screenBtn.hidden = true; }
      screenBtn.addEventListener('click', function () { loadModule('media').then(function (m) { m.screen({ max: C.media.screen_max_seconds, maxBytes: C.maxMb * 1048576 * 0.92, onDone: function (file, meta) { self.addFile(file, { kind: 'video', duration: meta.duration, previewUrl: meta.previewUrl, preview: meta.preview, width: meta.width, height: meta.height }); } }); }).catch(function (e) { toast(e.message); }); });
    }

    function insertAtCursor(text) {
      var s = input.selectionStart || input.value.length, e = input.selectionEnd || s;
      input.value = input.value.slice(0, s) + text + input.value.slice(e); input.focus(); input.selectionStart = input.selectionEnd = s + text.length; grow(); saveDraft();
    }

    /* ---- @ people and # tasks/projects ---- */
    function onType() {
      var pos = input.selectionStart, before = input.value.slice(0, pos);
      var m = /(^|[\s(])([@#])([^\s@#\n][^@#\n]{0,28}|)$/.exec(before);
      if (!m) { closePop(); return; }
      popKind = m[2] === '@' ? 'people' : 'work'; popQuery = m[3]; popStart = pos - m[3].length - 1; fillPop();
    }
    var popQuery = '', popStart = 0;
    function fillPop() {
      if (popKind === 'people') {
        var q = popQuery.toLowerCase();
        popItems = C.people.filter(function (p) { return !q || p.name.toLowerCase().indexOf(q) > -1; }).slice(0, 8).map(function (p) { return { type: 'user', id: p.id, label: p.name, sub: p.sub, initials: p.initials, avatar: p.avatar }; });
        drawPop();
      } else {
        clearTimeout(popTimer); var req = ++popReq;
        popTimer = setTimeout(function () {
          request('GET', C.urls.tags + '?kind=work&q=' + encodeURIComponent(popQuery)).then(function (r) { if (req === popReq) { popItems = r.items || []; drawPop(); } }).catch(function () {});
        }, 160);
      }
    }
    function drawPop() {
      if (!popItems.length) { pop.innerHTML = '<div class="tagpop__empty">' + (popKind === 'people' ? 'No one matches' : 'No tasks or projects found') + '</div>'; pop.hidden = false; popIndex = 0; return; }
      popIndex = Math.min(popIndex, popItems.length - 1);
      pop.innerHTML = popItems.map(function (it, i) {
        var lead = it.type === 'user' ? avatarHtml(it, 'pavatar--xs') : '<span class="tagpop__ic tagpop__ic--' + it.type + '">' + ico(it.type === 'task' ? 'task' : 'layers') + '</span>';
        return '<button type="button" role="option" class="tagpop__item' + (i === popIndex ? ' is-on' : '') + '" data-i="' + i + '">' + lead + '<span><b>' + esc(it.label) + '</b><small>' + esc(it.sub || '') + '</small></span></button>';
      }).join('');
      pop.hidden = false;
    }
    function closePop() { pop.hidden = true; popItems = []; popKind = null; }
    function choose(i) {
      var it = popItems[i]; if (!it) { return; }
      var mark = it.type === 'user' ? '@' : '#', display = mark + it.label, token = mark + '[' + it.label.replace(/[\[\]()\n]/g, ' ') + '](' + (it.type === 'user' ? 'u' : (it.type === 'task' ? 't' : 'p')) + ':' + it.id + ')';
      var pos = input.selectionStart;
      input.value = input.value.slice(0, popStart) + display + ' ' + input.value.slice(pos);
      input.selectionStart = input.selectionEnd = popStart + display.length + 1;
      picks = picks.filter(function (p) { return p.token !== token; }); picks.push({ display: display, token: token, type: it.type, label: it.label });
      closePop(); input.focus(); grow(); renderTagged(); saveDraft();
    }
    pop.addEventListener('mousedown', function (e) { e.preventDefault(); var b = e.target.closest('[data-i]'); if (b) { choose(parseInt(b.getAttribute('data-i'), 10)); } });
    pop.addEventListener('touchstart', function (e) { var b = e.target.closest('[data-i]'); if (b) { e.preventDefault(); choose(parseInt(b.getAttribute('data-i'), 10)); } }, { passive: false });
    function renderTagged() {
      picks = picks.filter(function (p) { return input.value.indexOf(p.display) > -1; });
      taggedBox.hidden = !picks.length;
      taggedBox.innerHTML = picks.length ? '<span>Tagged</span>' + picks.map(function (p, i) { return '<span class="tagchip tagchip--' + p.type + '">' + esc(p.display) + '<button type="button" data-untag="' + i + '" aria-label="Remove tag">&times;</button></span>'; }).join('') : '';
    }
    taggedBox.addEventListener('click', function (e) { var b = e.target.closest('[data-untag]'); if (b) { var p = picks[parseInt(b.getAttribute('data-untag'), 10)]; if (p) { input.value = input.value.replace(p.display, p.display.slice(1)); } renderTagged(); input.focus(); } });
    input.addEventListener('input', renderTagged);
    $$('[data-chat-at], [data-chat-hash]', form).forEach(function (b) { b.addEventListener('click', function () { insertAtCursor((/\s$|^$/.test(input.value.slice(0, input.selectionStart || 0)) ? '' : ' ') + (b.hasAttribute('data-chat-at') ? '@' : '#')); onType(); }); });

    input.addEventListener('keydown', function (e) {
      if (!pop.hidden && popItems.length) {
        if (e.key === 'ArrowDown') { e.preventDefault(); popIndex = (popIndex + 1) % popItems.length; drawPop(); return; }
        if (e.key === 'ArrowUp') { e.preventDefault(); popIndex = (popIndex - 1 + popItems.length) % popItems.length; drawPop(); return; }
        if (e.key === 'Enter' || e.key === 'Tab') { e.preventDefault(); choose(popIndex); return; }
      }
      if (e.key === 'Escape' && !pop.hidden) { closePop(); return; }
      if (e.key === 'Enter' && !e.shiftKey && !touchDevice && !e.isComposing) { e.preventDefault(); self.submit(); }
    });

    /* ---- send ---- */
    function tokenized() {
      var text = input.value.trim();
      picks.slice().sort(function (a, b) { return b.display.length - a.display.length; }).forEach(function (p) { text = text.split(p.display).join(p.token); });
      return text;
    }
    self.submit = function () {
      var text = tokenized();
      if (sending || (!text && !pending.length)) { return; }
      showErr(''); sending = true; send.disabled = true; form.classList.add('is-sending');
      var fd = new FormData(); fd.append('_token', csrf); fd.append('body', text);
      var rt = opts.replyTo ? opts.replyTo() : null; if (rt) { fd.append('reply_to', rt); }
      var sent = pending.slice(), snapshot = { text: input.value, picks: picks.slice() };
      sent.forEach(function (it, i) {
        fd.append('files[' + i + ']', it.file, it.file.name);
        if (it.kind !== 'file') { fd.append('kinds[' + i + ']', it.kind); }
        if (it.duration != null) { fd.append('durations[' + i + ']', Math.round(it.duration)); }
        if (it.meta) { fd.append('metas[' + i + ']', JSON.stringify(it.meta)); }
        if (it.preview) { fd.append('previews[' + i + ']', it.preview, 'preview.jpg'); }
        if (it.annotated_of) { fd.append('annotated_of[' + i + ']', it.annotated_of); }
      });
      // clear the composer right away; restore it if sending fails
      input.value = ''; picks = []; pending = []; renderChips(); renderTagged(); grow(); saveDraft(); closePop();
      var bubble = sent.length ? opts.onPending && opts.onPending(sent, text) : null;
      upload(C.urls.store, fd, function (p) { if (bubble) { bubble.progress(p); } })
        .then(function (m) { if (bubble) { bubble.remove(); } opts.onSent && opts.onSent(m); })
        .catch(function (e) {
          if (bubble) { bubble.remove(); }
          input.value = snapshot.text; picks = snapshot.picks; pending = sent; renderChips(); renderTagged(); grow(); showErr(e.message);
        })
        .then(function () { sending = false; send.disabled = false; form.classList.remove('is-sending'); input.focus(); });
    };
    form.addEventListener('submit', function (e) { e.preventDefault(); self.submit(); });
    self.focus = function () { input.focus(); };
    renderChips(); grow();
  }

  /** XMLHttpRequest so a big file shows real upload progress */
  /* Files over ~600 KB travel in 512 KB pieces first, so a server, firewall or proxy with a small request limit cannot cut
     the connection. The message request then only carries the id of the finished upload. */
  var CHUNK = 512 * 1024, CHUNK_FROM = 600 * 1024;
  function sendPiece(url, id, index, blob, onBytes) {
    return new Promise(function (resolve, reject) {
      var tries = 0;
      (function attempt() {
        var fd = new FormData(); fd.append('_token', csrf); fd.append('upload', id); fd.append('index', index); fd.append('chunk', blob, 'piece');
        var x = new XMLHttpRequest(); x.open('POST', url); x.setRequestHeader('Accept', 'application/json'); x.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); x.setRequestHeader('X-CSRF-TOKEN', csrf);
        if (x.upload) { x.upload.onprogress = function (e) { onBytes(Math.min(e.loaded, blob.size)); }; }
        x.onload = function () {
          if (x.status === 401 || x.status === 419) { location.reload(); return; }
          if (x.status >= 200 && x.status < 300) { onBytes(blob.size); resolve(); } else if (++tries < 3 && x.status >= 500) { setTimeout(attempt, 600 * tries); } else { reject(new Error('Could not upload the file. Please try again.')); }
        };
        x.onerror = function () { if (++tries < 3) { setTimeout(attempt, 800 * tries); } else { reject(new Error('Connection problem. Your message was not sent.')); } };
        x.send(fd);
      }());
    });
  }
  function upload(u, fd, onProgress) {
    var big = [];
    if (C.urls.chunk && fd.forEach) { fd.forEach(function (v, k) { if (v instanceof Blob && /^files\[\d+\]$/.test(k) && v.size > CHUNK_FROM) { big.push([k, v]); } }); }
    if (!big.length) { return post(u, fd, onProgress); }
    var total = big.reduce(function (n, b) { return n + b[1].size; }, 0), done = 0;
    return big.reduce(function (chain, b) {
      return chain.then(function () {
        var key = b[0], file = b[1], id = Array.apply(null, Array(24)).map(function () { return 'abcdefghijklmnopqrstuvwxyz0123456789'.charAt(Math.floor(Math.random() * 36)); }).join(''), pieces = Math.ceil(file.size / CHUNK), base = done, i = 0;
        function next() {
          if (i >= pieces) { return Promise.resolve(); }
          var idx = i++, part = file.slice(idx * CHUNK, Math.min(file.size, (idx + 1) * CHUNK)), before = idx * CHUNK;
          return sendPiece(C.urls.chunk, id, idx, part, function (bytes) { onProgress(0.97 * (base + before + bytes) / total); }).then(next);
        }
        return next().then(function () {
          var n = key.match(/\d+/)[0];
          fd.delete(key); fd.append('upload_ids[' + n + ']', id); fd.append('upload_names[' + n + ']', file.name || 'upload'); fd.append('upload_totals[' + n + ']', pieces); fd.append('upload_types[' + n + ']', file.type || '');
          done += file.size;
        });
      });
    }, Promise.resolve()).then(function () { return post(u, fd, function (p) { onProgress(0.97 + 0.03 * p); }); });
  }
  function post(u, fd, onProgress) {
    return new Promise(function (resolve, reject) {
      var x = new XMLHttpRequest(); x.open('POST', u); x.setRequestHeader('Accept', 'application/json'); x.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); x.setRequestHeader('X-CSRF-TOKEN', csrf);
      if (x.upload) { x.upload.onprogress = function (e) { if (e.lengthComputable) { onProgress(e.loaded / e.total); } }; }
      x.onload = function () {
        var j = {}; try { j = JSON.parse(x.responseText); } catch (e) {}
        if (x.status === 401 || x.status === 419) { location.reload(); return; }
        if (x.status === 413) { reject(new Error('That upload is too large for the server.')); return; }
        if (x.status >= 200 && x.status < 300) { resolve(j); } else { reject(new Error(firstError(j) || 'Could not send. Please try again.')); }
      };
      x.onerror = function () { reject(new Error('Connection problem. Your message was not sent.')); };
      x.send(fd);
    });
  }

  /** A temporary bubble with a progress bar while files upload */
  function pendingBubble(container, files, text) {
    var el = document.createElement('div'); el.className = 'msg msg--mine msg--pending';
    el.innerHTML = '<div class="msg__bubble">' + (text ? '<div class="msg__text">' + esc(text.replace(TOKEN, '$1$2')) + '</div>' : '') +
      '<div class="msg__media">' + files.map(function (f) { return '<div class="att-file att-file--up">' + ico('file') + '<span>' + esc(f.kind === 'voice' ? 'Voice message' : (f.kind === 'video' ? 'Screen recording' : f.file.name)) + '</span></div>'; }).join('') + '</div>' +
      '<div class="up"><i style="width:0%"></i></div><div class="msg__foot"><span>Sending…</span></div></div>';
    container.appendChild(el); if (container === list) { toBottom(); }
    return { progress: function (p) { $('.up i', el).style.width = Math.round(p * 100) + '%'; }, remove: function () { el.remove(); } };
  }

  /* ---- main composer ---- */
  var mainForm = root.querySelector('[data-chat-form]'), main = null;
  if (mainForm) {
    main = new Composer(mainForm, {
      draft: true,
      onPending: function (files, text) { return pendingBubble(list, files, text); },
      onSent: function (m) { addMessages([m], false); toBottom(); nudge(); }
    });
    // drop files anywhere on the chat
    ['dragenter', 'dragover'].forEach(function (ev) { root.addEventListener(ev, function (e) { if (e.dataTransfer && Array.prototype.indexOf.call(e.dataTransfer.types || [], 'Files') > -1) { e.preventDefault(); root.classList.add('is-drop'); } }); });
    ['dragleave', 'drop'].forEach(function (ev) { root.addEventListener(ev, function (e) { if (ev === 'dragleave' && root.contains(e.relatedTarget)) { return; } root.classList.remove('is-drop'); }); });
    root.addEventListener('drop', function (e) { if (e.dataTransfer && e.dataTransfer.files.length) { e.preventDefault(); Array.prototype.forEach.call(e.dataTransfer.files, function (f) { main.addFile(f); }); main.focus(); } });
  }

  /* ------------------------------------------------------------------ threads (side drawer) */
  function openThread(id) {
    if (!window.PortalDrawer) { return; }
    thread = { root: null, replies: [], lastReply: 0, loading: true };
    window.PortalDrawer.open({ title: 'Thread', key: 'thread:' + id, html: '<div class="pdrawer__skel" aria-busy="true"><i></i><i></i><i></i></div>', onClose: function () { thread = null; } });
    request('GET', url(C.urls.thread, id)).then(function (data) {
      if (!thread) { return; }
      thread.root = data.root; thread.replies = data.replies || []; thread.lastReply = thread.replies.reduce(function (a, r) { return Math.max(a, r.id); }, 0); thread.loading = false;
      buildThread(data);
    }).catch(function (e) { window.PortalDrawer.body.innerHTML = '<div class="pdrawer__err"><b>' + esc(e.message) + '</b></div>'; });
  }
  function buildThread(data) {
    var body = window.PortalDrawer.body;
    body.innerHTML = '<div class="thr" data-thread-panel><div class="thr__scroll" data-thr-scroll><div class="thr__root" data-thr-root></div><div class="thr__count" data-thr-count></div><div class="thr__replies" data-thr-replies></div></div>' +
      (C.open ? '<form class="chat__composer thr__composer" data-chat-form novalidate><div class="chat__tagged" data-chat-tagged hidden></div><div class="chat__files" data-chat-files hidden></div><p class="chat__err" data-chat-err role="alert" hidden></p>' +
      '<div class="chat__row" data-chat-row><div class="chat__tools"><button class="chat__btn" type="button" data-chat-attach aria-label="Attach files">' + ico('file') + '</button><button class="chat__btn" type="button" data-chat-emoji aria-label="Emoji">' + ico('smile') + '</button></div>' +
      '<textarea rows="1" maxlength="5000" data-chat-input aria-label="Reply" placeholder="Reply in thread…"></textarea><button class="chat__btn chat__btn--send" type="submit" data-chat-send aria-label="Send reply"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg></button></div>' +
      '<input type="file" multiple hidden data-chat-file><div class="tagpop" data-chat-tagpop role="listbox" hidden></div></form>' : '') + '</div>';
    renderThreadRoot(data.root); renderThreadReplies(thread.replies, false);
    var form = $('.thr__composer', body);
    if (form) {
      thread.composer = new Composer(form, {
        replyTo: function () { return thread && thread.root ? thread.root.id : null; }, sendVoiceAtOnce: false,
        onPending: function (files, text) { return pendingBubble($('[data-thr-replies]', body), files, text); },
        onSent: function (m) { thread.replies.push(m); thread.lastReply = Math.max(thread.lastReply, m.id); renderThreadReplies([m], true); nudge(); }
      });
      thread.composer.focus();
    }
  }
  function renderThreadRoot(m) {
    var host = window.PortalDrawer.isOpen() && $('[data-thr-root]', window.PortalDrawer.body); if (!host) { return; }
    thread.root = m; host.innerHTML = ''; var el = messageEl(m, { thread: true }); host.appendChild(el);
    var n = thread.replies.length; $('[data-thr-count]', window.PortalDrawer.body).textContent = n ? n + (n === 1 ? ' reply' : ' replies') : 'No replies yet. Start the conversation below.';
  }
  function renderThreadReplies(arr, scroll) {
    var host = $('[data-thr-replies]', window.PortalDrawer.body), sc = $('[data-thr-scroll]', window.PortalDrawer.body); if (!host) { return; }
    arr.forEach(function (m) { if ($('[data-id="' + m.id + '"]', host)) { return; } var el = messageEl(m, { thread: true }); el.__m = m; host.appendChild(el); });
    var n = thread.replies.length; $('[data-thr-count]', window.PortalDrawer.body).textContent = n ? n + (n === 1 ? ' reply' : ' replies') : 'No replies yet. Start the conversation below.';
    if (scroll) { sc.scrollTop = sc.scrollHeight; }
  }
  function applyThread(t) {
    if (!thread || !thread.root) { return; }
    var fresh = (t.replies || []).filter(function (r) { return !thread.replies.some(function (x) { return x.id === r.id; }); });
    fresh.forEach(function (r) { thread.replies.push(r); thread.lastReply = Math.max(thread.lastReply, r.id); });
    var sc = $('[data-thr-scroll]', window.PortalDrawer.body), near = sc && sc.scrollHeight - sc.scrollTop - sc.clientHeight < 140;
    if (fresh.length) { renderThreadReplies(fresh, near); }
    (t.updated || []).forEach(function (m) { var i = thread.replies.findIndex(function (x) { return x.id === m.id; }); if (i > -1) { thread.replies[i] = m; } $$('[data-thread-panel] [data-id="' + m.id + '"]').forEach(function (old) { old.replaceWith(messageEl(m, { thread: true })); }); });
    if (t.root) { renderThreadRoot(t.root); }
  }

  /* ------------------------------------------------------------------ tags (task / project cards in the drawer) */
  function openTag(type, id) {
    if (!window.PortalDrawer) { return; }
    window.PortalDrawer.open({ title: type === 'task' ? 'Task' : 'Project', url: url(type === 'task' ? C.urls.taskCard : C.urls.projectCard, id), key: type + ':' + id });
  }

  /* ------------------------------------------------------------------ people in the chat */
  function paintPeople() {
    var avs = $('[data-chat-avs]', root), n = $('[data-chat-people-count]', root);
    avs.innerHTML = C.people.slice(0, 4).map(function (p) { return avatarHtml(p, 'pavatar--xs'); }).join('');
    n.textContent = C.people.length + (C.people.length === 1 ? ' person' : ' people');
  }
  function openMembers() {
    if (!window.PortalDrawer) { return; }
    window.PortalDrawer.open({ title: 'People in this chat', url: C.urls.members, key: 'members' });
  }
  root.addEventListener('click', function (e) { if (e.target.closest('[data-open-members]')) { openMembers(); } });
  document.addEventListener('portal:drawer', function (e) {
    var m = e.detail.el.querySelector('[data-members]'); if (!m) { return; }
    function done() { request('GET', C.urls.feed + '?after=' + lastId).then(function (f) { apply(f, false); }); openMembersRefresh(); }
    m.addEventListener('click', function (ev) {
      var add = ev.target.closest('[data-add]'), rem = ev.target.closest('[data-remove]');
      if (add) { add.disabled = true; request('POST', m.getAttribute('data-add-url'), { user_id: parseInt(add.getAttribute('data-add'), 10) }).then(function (r) { C.peopleCount = r.count; done(); }).catch(function (x) { add.disabled = false; toast(x.message); }); }
      if (rem) { if (!window.confirm(rem.textContent.trim() === 'Leave' ? 'Leave this conversation?' : 'Remove this person from the conversation?')) { return; } rem.disabled = true; request('DELETE', m.getAttribute('data-remove-template').replace('__ID__', rem.getAttribute('data-remove'))).then(function () { done(); }).catch(function (x) { rem.disabled = false; toast(x.message); }); }
    });
  });
  function openMembersRefresh() {
    // reload the drawer list and the avatars in the bar
    fetch(C.urls.members, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(function (r) { return r.text(); }).then(function (html) {
      if (window.PortalDrawer.isOpen() && window.PortalDrawer.body.querySelector('[data-members]')) { window.PortalDrawer.body.innerHTML = html; document.dispatchEvent(new CustomEvent('portal:drawer', { detail: { el: window.PortalDrawer.body, key: 'members' } })); }
      var tmp = document.createElement('div'); tmp.innerHTML = html;
      var names = $$('[data-member]', tmp).length; $('[data-chat-people-count]', root).textContent = names + (names === 1 ? ' person' : ' people');
    });
  }

  /* ------------------------------------------------------------------ expand (phones) */
  var expandBtn = root.querySelector('[data-chat-expand]');
  if (expandBtn) { expandBtn.addEventListener('click', function () { var on = root.classList.toggle('chat--full'); document.body.classList.toggle('chat-full', on); if (on) { toBottom(); } }); }
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && root.classList.contains('chat--full') && !(window.PortalDrawer && window.PortalDrawer.isOpen())) { root.classList.remove('chat--full'); document.body.classList.remove('chat-full'); } });

  /* ------------------------------------------------------------------ jump to a message (?m=ID from the inbox) */
  function highlight(el) { if (!el) { return false; } var box = el.getBoundingClientRect(), lb = list.getBoundingClientRect(); list.scrollTop += (box.top - lb.top) - (list.clientHeight / 2 - box.height / 2); el.classList.remove('msg--focus'); void el.offsetWidth; el.classList.add('msg--focus'); return true; }
  function focusMessage(id) {
    var chatTab = document.querySelector('[data-tab="chat"]'); if (chatTab && !chatTab.classList.contains('is-on')) { chatTab.click(); }
    root.scrollIntoView({ block: 'nearest' });
    request('GET', url(C.urls.thread, id)).then(function (t) {
      if (t.target && t.target !== t.root.id) { openThread(t.root.id); setTimeout(function () { highlight($('[data-thread-panel] [data-id="' + t.target + '"]')); }, 500); return; }
      var tries = 0;
      (function step() {
        var el = $('[data-id="' + id + '"]', list);
        if (el) { highlight(el); return; }
        if (hasMore && tries++ < 12) { loadEarlier().then(step); }
      })();
    }).catch(function () {});
  }

  /* ------------------------------------------------------------------ api for the lazy modules */
  var api = {
    config: C, toast: toast, request: request, upload: upload, ico: ico, esc: esc, mmss: mmss, csrf: csrf,
    sendFile: function (blob, name, extra) {   // used by the viewers to post an annotated copy into the chat
      var fd = new FormData(); fd.append('_token', csrf); fd.append('body', extra.body || '');
      fd.append('files[0]', blob, name); if (extra.annotated_of) { fd.append('annotated_of[0]', extra.annotated_of); }
      if (extra.preview) { fd.append('previews[0]', extra.preview, 'preview.jpg'); } if (extra.meta) { fd.append('metas[0]', JSON.stringify(extra.meta)); }
      return upload(C.urls.store, fd, extra.onProgress || function () {}).then(function (m) { addMessages([m], false); toBottom(); nudge(); return m; });
    }
  };
  window.PortalChat = window.PortalChat || {}; window.PortalChat.api = api;

  /* ------------------------------------------------------------------ start */
  paintPeople();
  var initial = $('[data-chat-feed]', root);
  if (initial) { try { var feed = JSON.parse(initial.textContent); apply(feed, true); } catch (e) {} }
  toBottom(); updateJump(false);
  schedule();
  document.addEventListener('portal:tab', function (e) { if (e.detail === 'chat') { setTimeout(toBottom, 30); } });
  var mParam = parseInt(new URLSearchParams(location.search).get('m'), 10);
  if (mParam) { setTimeout(function () { focusMessage(mParam); }, 120); }
}());

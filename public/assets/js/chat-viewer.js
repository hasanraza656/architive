/* ==========================================================================
   Picture and PDF viewer for the chat, loaded only when someone opens an attachment.
     PortalChat.viewer.open({ kind: 'image'|'pdf', files: [...], index, host })
     PortalChat.viewer.pdfPreview(File)  ->  Promise<{ blob, pages }>   (first page as a small picture)
   Features: zoom / pan / swipe between pictures, smooth continuous PDF pages (rendered only when visible),
   and annotations (pen, highlighter, arrow, box, circle, text, undo/redo) that can be sent back into the chat
   as an annotated copy. PDF.js and pdf-lib are separate files that load the first time they are needed.
   ========================================================================== */
(function () {
  'use strict';

  var PC = window.PortalChat = window.PortalChat || {}, host = PC.api, C = host.config;
  var ico = host.ico, esc = host.esc, toast = host.toast;

  (function () {   // styles load with this module so the chat page stays light
    var l = document.createElement('link'); l.rel = 'stylesheet'; l.href = C.base.replace(/\/js\/?$/, '/css') + '/portal-chat-viewer.css?v=' + C.version; document.head.appendChild(l);
  }());

  /* ------------------------------------------------------------------ libraries on demand */
  var libs = {};
  function script(src) {
    if (libs[src]) { return libs[src]; }
    libs[src] = new Promise(function (res, rej) { var s = document.createElement('script'); s.src = src; s.async = true; s.onload = res; s.onerror = function () { libs[src] = null; rej(new Error('Could not load the PDF tools.')); }; document.head.appendChild(s); });
    return libs[src];
  }
  function pdfjs() { return script(C.pdf.lib).then(function () { window.pdfjsLib.GlobalWorkerOptions.workerSrc = C.pdf.worker; return window.pdfjsLib; }); }
  function pdflib() { return script(C.pdf.pdflib).then(function () { return window.PDFLib; }); }

  /* ------------------------------------------------------------------ drawing (shared by pictures and PDF pages) */
  var COLORS = ['#FF3B30', '#FFD60A', '#1E9E5A', '#0B5FD3', '#141414', '#FFFFFF'];
  var SIZES = { s: 1, m: 2, l: 4 };

  function drawShape(ctx, s, k) {     // k = canvas pixels per drawing unit
    ctx.save(); ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = s.color; ctx.fillStyle = s.color;
    var w = s.size * k;
    if (s.type === 'pen' || s.type === 'highlight') {
      ctx.globalAlpha = s.type === 'highlight' ? 0.35 : 1; ctx.lineWidth = s.type === 'highlight' ? w * 4 : w; ctx.lineCap = s.type === 'highlight' ? 'butt' : 'round';
      ctx.beginPath(); s.pts.forEach(function (p, i) { if (i === 0) { ctx.moveTo(p[0] * k, p[1] * k); } else { ctx.lineTo(p[0] * k, p[1] * k); } });
      if (s.pts.length === 1) { ctx.lineTo(s.pts[0][0] * k + 0.01, s.pts[0][1] * k); } ctx.stroke();
    } else if (s.type === 'arrow') {
      ctx.lineWidth = w; var a = Math.atan2(s.y2 - s.y1, s.x2 - s.x1), head = Math.max(10 * k * (s.size / 2), 12 * k);
      ctx.beginPath(); ctx.moveTo(s.x1 * k, s.y1 * k); ctx.lineTo(s.x2 * k, s.y2 * k); ctx.stroke();
      ctx.beginPath(); ctx.moveTo(s.x2 * k, s.y2 * k); ctx.lineTo(s.x2 * k - head * Math.cos(a - Math.PI / 6), s.y2 * k - head * Math.sin(a - Math.PI / 6)); ctx.lineTo(s.x2 * k - head * Math.cos(a + Math.PI / 6), s.y2 * k - head * Math.sin(a + Math.PI / 6)); ctx.closePath(); ctx.fill();
    } else if (s.type === 'rect') {
      ctx.lineWidth = w; ctx.strokeRect(Math.min(s.x1, s.x2) * k, Math.min(s.y1, s.y2) * k, Math.abs(s.x2 - s.x1) * k, Math.abs(s.y2 - s.y1) * k);
    } else if (s.type === 'ellipse') {
      ctx.lineWidth = w; ctx.beginPath(); ctx.ellipse((s.x1 + s.x2) / 2 * k, (s.y1 + s.y2) / 2 * k, Math.max(1, Math.abs(s.x2 - s.x1) / 2 * k), Math.max(1, Math.abs(s.y2 - s.y1) / 2 * k), 0, 0, Math.PI * 2); ctx.stroke();
    } else if (s.type === 'text') {
      var fs = s.fontSize * k; ctx.font = '700 ' + fs + 'px "Plus Jakarta Sans", Arial, sans-serif'; ctx.textBaseline = 'top';
      s.text.split('\n').forEach(function (line, i) {
        var y = s.y * k + i * fs * 1.25;
        if (s.color !== '#141414') { ctx.lineWidth = Math.max(2, fs / 8); ctx.strokeStyle = s.color === '#FFFFFF' ? '#141414' : '#FFFFFF'; ctx.globalAlpha = 0.9; ctx.strokeText(line, s.x * k, y); ctx.globalAlpha = 1; }
        ctx.fillText(line, s.x * k, y);
      });
    }
    ctx.restore();
  }

  /** Annotation state + pointer handling for any number of drawing surfaces (one picture, or one per PDF page). */
  function Annotator(onChange) {
    var self = this;
    self.on = false; self.tool = 'pen'; self.color = COLORS[0]; self.size = 'm';
    self.shapes = {};          // surface key -> shapes
    self.undone = [];
    self.history = [];         // order of additions: [{key, shape}]
    function list(key) { return self.shapes[key] || (self.shapes[key] = []); }
    self.count = function () { return Object.keys(self.shapes).reduce(function (n, k) { return n + self.shapes[k].length; }, 0); };
    self.keys = function () { return Object.keys(self.shapes).filter(function (k) { return self.shapes[k].length; }); };
    self.add = function (key, shape) { list(key).push(shape); self.history.push({ key: key, shape: shape }); self.undone = []; onChange(key); };
    self.undo = function () { var h = self.history.pop(); if (!h) { return; } var l = list(h.key), i = l.indexOf(h.shape); if (i > -1) { l.splice(i, 1); } self.undone.push(h); onChange(h.key); };
    self.redo = function () { var h = self.undone.pop(); if (!h) { return; } list(h.key).push(h.shape); self.history.push(h); onChange(h.key); };
    self.clear = function () { var ks = self.keys(); self.shapes = {}; self.history = []; self.undone = []; ks.forEach(onChange); };
    self.unit = function (w, h) { return Math.max(1, Math.max(w, h) / 1000); };   // pictures differ a lot in size: scale pen widths to the picture
    self.makeShape = function (u, x, y) {
      var size = SIZES[self.size] * u;
      if (self.tool === 'pen' || self.tool === 'highlight') { return { type: self.tool, color: self.color, size: size, pts: [[x, y]] }; }
      if (self.tool === 'text') { return null; }
      return { type: self.tool, color: self.color, size: size, x1: x, y1: y, x2: x, y2: y };
    };
    self.fontSize = function (u) { return ({ s: 16, m: 24, l: 40 }[self.size]) * u; };
  }

  /** Wire pointer events of one drawing canvas. surface = {key, el (canvas), toUnits(clientX, clientY) -> [x, y], k() -> canvas px per unit, u (size unit), redraw()} */
  function attachDrawing(an, surface, getHost) {
    var el = surface.el, draft = null, pid = null;
    el.addEventListener('pointerdown', function (e) {
      if (!an.on || (e.pointerType === 'mouse' && e.button !== 0)) { return; }
      var p = surface.toUnits(e.clientX, e.clientY);
      if (an.tool === 'text') { e.preventDefault(); textInput(an, surface, e.clientX, e.clientY, p, getHost()); return; }
      e.preventDefault(); pid = e.pointerId; try { el.setPointerCapture(pid); } catch (x) {}
      draft = an.makeShape(surface.u, p[0], p[1]); surface.draft = draft; surface.redraw();
    });
    el.addEventListener('pointermove', function (e) {
      if (!draft || e.pointerId !== pid) { return; }
      e.preventDefault(); var p = surface.toUnits(e.clientX, e.clientY);
      if (draft.pts) { var last = draft.pts[draft.pts.length - 1]; if (Math.abs(p[0] - last[0]) + Math.abs(p[1] - last[1]) > 0.6 * surface.u) { draft.pts.push(p); } } else { draft.x2 = p[0]; draft.y2 = p[1]; }
      surface.redraw();
    });
    function end(e) {
      if (!draft || e.pointerId !== pid) { return; }
      var d = draft; draft = null; surface.draft = null; pid = null;
      var tiny = !d.pts && Math.abs(d.x2 - d.x1) + Math.abs(d.y2 - d.y1) < 4 * surface.u;
      if (!tiny) { an.add(surface.key, d); } else { surface.redraw(); }
    }
    el.addEventListener('pointerup', end); el.addEventListener('pointercancel', end);
  }
  function textInput(an, surface, cx, cy, p, hostEl) {
    var ta = document.createElement('textarea'); ta.className = 'cv__text'; ta.rows = 1; ta.placeholder = 'Type…'; ta.style.left = cx + 'px'; ta.style.top = cy + 'px'; ta.style.color = an.color === '#FFFFFF' ? '#fff' : an.color;
    ta.style.fontSize = Math.max(14, an.fontSize(1) * (surface.cssPerUnit ? surface.cssPerUnit() : 1)) + 'px';
    document.body.appendChild(ta); ta.focus();
    var done = false;
    function commit() { if (done) { return; } done = true; var t = ta.value.trim(); ta.remove(); if (t) { an.add(surface.key, { type: 'text', color: an.color, size: 1, text: t, x: p[0], y: p[1], fontSize: an.fontSize(surface.u) }); } }
    ta.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); commit(); } if (e.key === 'Escape') { done = true; ta.remove(); } e.stopPropagation(); });
    ta.addEventListener('blur', commit);
  }

  /* ------------------------------------------------------------------ the viewer shell */
  var cv = null, st = null;

  function build() {
    cv = document.createElement('div'); cv.className = 'cv'; cv.setAttribute('role', 'dialog'); cv.setAttribute('aria-modal', 'true'); cv.setAttribute('aria-label', 'Viewer'); cv.setAttribute('data-open', '1'); cv.tabIndex = -1;
    cv.innerHTML =
      '<header class="cv__bar"><div class="cv__title"><b data-title></b><small data-sub></small></div><div class="cv__tools">' +
        '<span class="cv__zoom" data-zoomtools><button type="button" data-zoom="out" aria-label="Zoom out">' + ico('zoom-out') + '</button><span data-zoomlabel>100%</span><button type="button" data-zoom="in" aria-label="Zoom in">' + ico('zoom-in') + '</button><button type="button" data-zoom="fit" aria-label="Fit to screen">' + ico('maximize') + '</button></span>' +
        '<button type="button" class="cv__annotate" data-annotate aria-pressed="false">' + ico('pen') + '<span>Annotate</span></button>' +
        '<a class="cv__btn" data-download download aria-label="Download">' + ico('download') + '</a><button type="button" class="cv__btn" data-close aria-label="Close viewer">' + ico('x') + '</button></div></header>' +
      '<div class="cv__annbar" data-annbar hidden></div>' +
      '<div class="cv__stage" data-stage tabindex="0"></div>' +
      '<button type="button" class="cv__nav cv__nav--prev" data-nav="-1" aria-label="Previous" hidden>' + ico('chevron-left') + '</button><button type="button" class="cv__nav cv__nav--next" data-nav="1" aria-label="Next" hidden>' + ico('chevron-right') + '</button>' +
      '<footer class="cv__foot" data-foot hidden></footer><div class="cv__busy" data-busy hidden><i></i><span data-busytext>Preparing…</span></div>';
    document.body.appendChild(cv); document.body.classList.add('cv-open');
    cv.querySelector('[data-close]').addEventListener('click', close);
    cv.addEventListener('click', function (e) { var n = e.target.closest('[data-nav]'); if (n) { go(parseInt(n.getAttribute('data-nav'), 10)); } });
    document.addEventListener('keydown', keys, true);
    cv.focus();
  }
  function keys(e) {
    if (!cv || e.target.closest && e.target.closest('textarea, input')) { return; }
    if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); if (st.an && st.an.on) { toggleAnnotate(false); } else { close(); } }
    else if (e.key === 'ArrowLeft' && st.kind === 'image' && !st.an.on) { go(-1); } else if (e.key === 'ArrowRight' && st.kind === 'image' && !st.an.on) { go(1); }
    else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z' && st.an.on) { e.preventDefault(); e.shiftKey ? st.an.redo() : st.an.undo(); }
  }
  function close() {
    if (!cv) { return; }
    if (st.an && st.an.count() && !window.confirm('Close without sending your drawing?')) { return; }
    document.removeEventListener('keydown', keys, true); document.body.classList.remove('cv-open');
    if (st.destroy) { st.destroy(); }
    var done = st.onClose; cv.remove(); cv = null; st = null; if (done) { done(); }
  }
  function busy(on, text) { var b = cv.querySelector('[data-busy]'); b.hidden = !on; if (text) { cv.querySelector('[data-busytext]').textContent = text; } }

  function open(opts) {
    if (cv) { return; }
    var files = opts.files || [], idx = opts.index || 0;
    build();
    st = { kind: opts.kind, files: files, index: idx, an: null, onApply: opts.onApply || null, applyLabel: opts.applyLabel, onClose: opts.onClose || null };
    st.an = new Annotator(function (key) { if (st.redraw) { st.redraw(key); } updateAnnBar(); });
    buildAnnBar();
    cv.querySelector('[data-annotate]').addEventListener('click', function () { toggleAnnotate(!st.an.on); });
    show(idx);
  }
  function go(d) {
    if (st.an.count() && !window.confirm('Switch picture and discard your drawing?')) { return; }
    var n = st.index + d; if (n < 0 || n >= st.files.length) { return; }
    st.an.clear(); toggleAnnotate(false); show(n);
  }
  function show(i) {
    st.index = i; var f = st.files[i];
    if (st.destroy) { st.destroy(); st.destroy = null; }
    cv.querySelector('[data-title]').textContent = f.name;
    cv.querySelector('[data-sub]').textContent = st.files.length > 1 ? (i + 1) + ' of ' + st.files.length : (f.size || '');
    var dl = cv.querySelector('[data-download]'); dl.href = f.url; dl.setAttribute('download', f.name);
    cv.querySelectorAll('[data-nav]').forEach(function (b) { b.hidden = st.files.length < 2 || (b.getAttribute('data-nav') === '-1' ? i === 0 : i === st.files.length - 1); });
    var stage = cv.querySelector('[data-stage]'); stage.innerHTML = ''; stage.className = 'cv__stage cv__stage--' + st.kind;
    if (st.kind === 'pdf') { pdfView(f, stage); } else { imageView(f, stage); }
  }

  /* ---- annotation toolbar ---- */
  function buildAnnBar() {
    var bar = cv.querySelector('[data-annbar]');
    var tools = [['pen', 'pen', 'Pen'], ['highlight', 'highlighter', 'Highlighter'], ['arrow', 'move-right', 'Arrow'], ['rect', 'square', 'Box'], ['ellipse', 'circle', 'Circle'], ['text', 'type', 'Text']];
    bar.innerHTML = '<div class="cv__group" role="group" aria-label="Tools">' + tools.map(function (t) { return '<button type="button" data-tool="' + t[0] + '" aria-label="' + t[2] + '" title="' + t[2] + '">' + ico(t[1]) + '</button>'; }).join('') + '</div>' +
      '<div class="cv__group" role="group" aria-label="Colour">' + COLORS.map(function (c) { return '<button type="button" class="cv__col" data-color="' + c + '" style="--c:' + c + '" aria-label="Colour ' + c + '"></button>'; }).join('') + '</div>' +
      '<div class="cv__group" role="group" aria-label="Thickness">' + [['s', 'Thin'], ['m', 'Medium'], ['l', 'Thick']].map(function (s) { return '<button type="button" class="cv__size" data-size="' + s[0] + '" aria-label="' + s[1] + '"><i style="height:' + ({ s: 2, m: 4, l: 8 }[s[0]]) + 'px"></i></button>'; }).join('') + '</div>' +
      '<div class="cv__group"><button type="button" data-undo aria-label="Undo" title="Undo">' + ico('undo') + '</button><button type="button" data-redo aria-label="Redo" title="Redo">' + ico('redo') + '</button><button type="button" data-clear aria-label="Clear all" title="Clear all">' + ico('eraser') + '</button></div>';
    bar.addEventListener('click', function (e) {
      var b = e.target.closest('button'); if (!b) { return; }
      if (b.hasAttribute('data-tool')) { st.an.tool = b.getAttribute('data-tool'); }
      else if (b.hasAttribute('data-color')) { st.an.color = b.getAttribute('data-color'); }
      else if (b.hasAttribute('data-size')) { st.an.size = b.getAttribute('data-size'); }
      else if (b.hasAttribute('data-undo')) { st.an.undo(); } else if (b.hasAttribute('data-redo')) { st.an.redo(); }
      else if (b.hasAttribute('data-clear')) { if (st.an.count() && window.confirm('Remove everything you drew?')) { st.an.clear(); } }
      updateAnnBar();
    });
  }
  function updateAnnBar() {
    var bar = cv && cv.querySelector('[data-annbar]'); if (!bar) { return; }
    cv.setAttribute('data-tool', st.an.tool);
    bar.querySelectorAll('[data-tool]').forEach(function (b) { b.classList.toggle('is-on', b.getAttribute('data-tool') === st.an.tool); });
    bar.querySelectorAll('[data-color]').forEach(function (b) { b.classList.toggle('is-on', b.getAttribute('data-color') === st.an.color); });
    bar.querySelectorAll('[data-size]').forEach(function (b) { b.classList.toggle('is-on', b.getAttribute('data-size') === st.an.size); });
    bar.querySelector('[data-undo]').disabled = !st.an.history.length; bar.querySelector('[data-redo]').disabled = !st.an.undone.length;
    var send = cv.querySelector('[data-send]'); if (send) { send.disabled = !st.an.count(); }
    var cnt = cv.querySelector('[data-markcount]'); if (cnt) { cnt.textContent = st.an.count() ? st.an.count() + ' mark' + (st.an.count() === 1 ? '' : 's') : 'Draw on the ' + (st.kind === 'pdf' ? 'page' : 'picture') + ' to start'; }
  }
  function toggleAnnotate(on) {
    st.an.on = on; cv.classList.toggle('is-annotating', on);
    var b = cv.querySelector('[data-annotate]'); b.setAttribute('aria-pressed', on ? 'true' : 'false'); b.classList.toggle('is-on', on);
    cv.querySelector('[data-annbar]').hidden = !on;
    var foot = cv.querySelector('[data-foot]'); foot.hidden = !on;
    if (on) {
      foot.innerHTML = '<span class="cv__mc" data-markcount></span>' + (st.kind === 'pdf' ? '<label class="cv__scope"><input type="radio" name="scope" value="all" checked> All pages</label><label class="cv__scope"><input type="radio" name="scope" value="marked"> Only marked pages</label>' : '') +
        (st.onApply ? '' : '<input class="cv__note" data-note maxlength="500" placeholder="Add a message (optional)">') + '<button type="button" class="pbtn pbtn--ghost pbtn--sm" data-cancel>Cancel</button><button type="button" class="pbtn pbtn--primary pbtn--sm" data-send disabled>' + ico(st.onApply ? 'check' : 'send') + ' ' + (st.onApply ? (st.applyLabel || 'Use this version') : 'Send annotated copy') + '</button>';
      foot.querySelector('[data-cancel]').addEventListener('click', function () { if (!st.an.count() || window.confirm('Discard your drawing?')) { st.an.clear(); toggleAnnotate(false); } });
      foot.querySelector('[data-send]').addEventListener('click', sendAnnotated);
      if (foot.querySelector('[data-note]')) { foot.querySelector('[data-note]').addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); sendAnnotated(); } e.stopPropagation(); }); }
      updateAnnBar();
    }
    cv.querySelector('[data-stage]').classList.toggle('is-drawing', on);
  }

  /* ------------------------------------------------------------------ pictures */
  function imageView(f, stage) {
    var wrap = document.createElement('div'); wrap.className = 'cv__imgwrap'; var img = new Image(); img.alt = f.name; img.draggable = false; img.className = 'cv__img';
    var canvas = document.createElement('canvas'); canvas.className = 'cv__ann'; wrap.appendChild(img); wrap.appendChild(canvas); stage.appendChild(wrap);
    var w = f.width || 0, h = f.height || 0, scale = 1, tx = 0, ty = 0, fit = 1, loaded = false, pointers = {}, pinch = null, drag = null;
    stage.insertAdjacentHTML('beforeend', '<div class="cv__spin" data-spin><i></i></div>');
    function apply() { wrap.style.transform = 'translate(' + tx + 'px,' + ty + 'px) scale(' + scale + ')'; cv.querySelector('[data-zoomlabel]').textContent = Math.round(scale / fit * 100) + '%'; }
    function center() { var r = stage.getBoundingClientRect(); tx = (r.width - w * scale) / 2; ty = (r.height - h * scale) / 2; }
    function setFit() { var r = stage.getBoundingClientRect(); fit = Math.min(r.width / w, r.height / h, 1) || 1; scale = fit; center(); apply(); }
    function zoomAt(factor, cx, cy) {
      var r = stage.getBoundingClientRect(), x = (cx == null ? r.width / 2 : cx - r.left), y = (cy == null ? r.height / 2 : cy - r.top), ns = Math.min(fit * 12, Math.max(fit * 0.5, scale * factor));
      tx = x - (x - tx) * (ns / scale); ty = y - (y - ty) * (ns / scale); scale = ns; apply();
    }
    img.onload = function () {
      w = img.naturalWidth; h = img.naturalHeight; loaded = true; wrap.style.width = w + 'px'; wrap.style.height = h + 'px';
      var max = 3000, k = Math.min(1, max / Math.max(w, h)); canvas.width = Math.round(w * k); canvas.height = Math.round(h * k); canvas.style.width = w + 'px'; canvas.style.height = h + 'px';
      var sp = stage.querySelector('[data-spin]'); if (sp) { sp.remove(); } setFit();
    };
    img.onerror = function () { stage.innerHTML = '<div class="cv__err">This picture could not be loaded.</div>'; };
    img.src = f.preview || f.url;

    var surface = {
      key: 'img', el: canvas, draft: null, get u() { return st.an.unit(w, h); },
      toUnits: function (cx, cy) { var r = canvas.getBoundingClientRect(); return [(cx - r.left) / (r.width / w), (cy - r.top) / (r.height / h)]; },
      cssPerUnit: function () { return scale; },
      redraw: function () { var ctx = canvas.getContext('2d'), k = canvas.width / w; ctx.clearRect(0, 0, canvas.width, canvas.height); (st.an.shapes.img || []).forEach(function (s) { drawShape(ctx, s, k); }); if (surface.draft) { drawShape(ctx, surface.draft, k); } }
    };
    st.redraw = function () { surface.redraw(); };
    attachDrawing(st.an, surface, function () { return stage; });
    st.exportCanvas = function () {
      var k = Math.min(1, 4096 / Math.max(w, h)), c = document.createElement('canvas'); c.width = Math.round(w * k); c.height = Math.round(h * k);
      var ctx = c.getContext('2d'); ctx.drawImage(img, 0, 0, c.width, c.height); (st.an.shapes.img || []).forEach(function (s) { drawShape(ctx, s, k); }); return c;
    };

    // zoom / pan with wheel, buttons, double click and touch pinch
    stage.addEventListener('wheel', function (e) { if (!loaded) { return; } e.preventDefault(); zoomAt(Math.exp(-e.deltaY * 0.0015), e.clientX, e.clientY); }, { passive: false });
    cv.querySelector('[data-zoomtools]').onclick = function (e) { var b = e.target.closest('[data-zoom]'); if (!b || !loaded) { return; } var z = b.getAttribute('data-zoom'); if (z === 'fit') { setFit(); } else { zoomAt(z === 'in' ? 1.35 : 1 / 1.35); } };
    stage.addEventListener('dblclick', function (e) { if (st.an.on || !loaded) { return; } if (scale > fit * 1.05) { setFit(); } else { zoomAt(2.5, e.clientX, e.clientY); } });
    stage.addEventListener('pointerdown', function (e) {
      if (st.an.on && e.target === canvas) { return; }
      pointers[e.pointerId] = { x: e.clientX, y: e.clientY };
      if (Object.keys(pointers).length === 2) { var p = Object.keys(pointers).map(function (k) { return pointers[k]; }); pinch = { d: Math.hypot(p[0].x - p[1].x, p[0].y - p[1].y), s: scale }; drag = null; }
      else { drag = { x: e.clientX, y: e.clientY, tx: tx, ty: ty, moved: false }; }
    });
    stage.addEventListener('pointermove', function (e) {
      if (!pointers[e.pointerId]) { return; } pointers[e.pointerId] = { x: e.clientX, y: e.clientY };
      var ids = Object.keys(pointers);
      if (ids.length === 2 && pinch) { var a = pointers[ids[0]], b = pointers[ids[1]]; zoomAt((pinch.s * Math.hypot(a.x - b.x, a.y - b.y) / pinch.d) / scale, (a.x + b.x) / 2, (a.y + b.y) / 2); }
      else if (drag) { var dx = e.clientX - drag.x, dy = e.clientY - drag.y; if (Math.abs(dx) + Math.abs(dy) > 4) { drag.moved = true; stage.classList.add('is-panning'); } if (scale > fit * 1.02) { tx = drag.tx + dx; ty = drag.ty + dy; apply(); } }
    });
    function up(e) {
      delete pointers[e.pointerId]; pinch = null;
      if (drag && !drag.moved === false && scale <= fit * 1.02 && Math.abs(e.clientX - drag.x) > 70 && Math.abs(e.clientY - drag.y) < 60 && !st.an.on) { go(e.clientX < drag.x ? 1 : -1); }   // swipe to the next / previous picture
      drag = null; stage.classList.remove('is-panning');
    }
    stage.addEventListener('pointerup', up); stage.addEventListener('pointercancel', up);
    var onResize = function () { if (loaded && scale <= fit * 1.02) { setFit(); } }; window.addEventListener('resize', onResize);
    st.destroy = function () { window.removeEventListener('resize', onResize); st.redraw = null; st.exportCanvas = null; };
  }

  /* ------------------------------------------------------------------ PDF */
  function pdfView(f, stage) {
    stage.innerHTML = '<div class="cv__spin" data-spin><i></i></div>';
    var pages = [], zoom = 1, doc = null, io = null, baseW = 900, current = 1, renders = {}, dpr = Math.min(2, window.devicePixelRatio || 1);
    var holder = document.createElement('div'); holder.className = 'cv__pdf';
    var dead = false;

    pdfjs().then(function (lib) { return lib.getDocument({ url: f.url, rangeChunkSize: 262144, disableAutoFetch: true, withCredentials: true }).promise; }).then(function (pdf) {
      if (dead) { return; }
      doc = pdf; stage.innerHTML = ''; stage.appendChild(holder);
      cv.querySelector('[data-sub]').textContent = pdf.numPages + (pdf.numPages === 1 ? ' page' : ' pages');
      return Promise.all(Array.apply(null, Array(pdf.numPages)).map(function (_, i) { return pdf.getPage(i + 1).then(function (p) { var v = p.getViewport({ scale: 1 }); return { n: i + 1, page: p, w: v.width, h: v.height }; }); })).then(function (list) {
        if (dead) { return; }
        pages = list; layout(true);
        io = new IntersectionObserver(function (entries) { entries.forEach(function (en) { var n = parseInt(en.target.getAttribute('data-n'), 10); if (en.isIntersecting) { renderPage(n); } else { unload(n); } }); }, { root: holder, rootMargin: '800px 0px' });
        pages.forEach(function (p) { io.observe(p.el); });
        holder.addEventListener('scroll', onScroll, { passive: true }); onScroll();
      });
    }).catch(function (e) { stage.innerHTML = '<div class="cv__err">' + esc(e && e.message ? e.message : 'This PDF could not be opened.') + '</div>'; });

    function stageWidth() { return Math.max(240, Math.min(baseW, stage.clientWidth - 24)); }
    function cssScale(p) { return (stageWidth() * zoom) / p.w; }
    function layout(first) {
      pages.forEach(function (p) {
        var s = cssScale(p), w = Math.round(p.w * s), h = Math.round(p.h * s);
        if (!p.el) {
          p.el = document.createElement('div'); p.el.className = 'cv__pg'; p.el.setAttribute('data-n', p.n);
          p.canvas = document.createElement('canvas'); p.canvas.className = 'cv__pgc'; p.ann = document.createElement('canvas'); p.ann.className = 'cv__ann';
          p.el.appendChild(p.canvas); p.el.appendChild(p.ann); holder.appendChild(p.el);
          p.surface = {
            key: 'p' + p.n, el: p.ann, draft: null, u: 1,
            toUnits: function (cx, cy) { var r = p.ann.getBoundingClientRect(); return [(cx - r.left) / (r.width / p.w), (cy - r.top) / (r.height / p.h)]; },
            cssPerUnit: function () { return cssScale(p); },
            redraw: function () { redrawAnn(p); }
          };
          attachDrawing(st.an, p.surface, function () { return stage; });
        }
        p.el.style.width = w + 'px'; p.el.style.height = h + 'px'; p.cssW = w; p.cssH = h;
        if (!first && p.rendered) { p.rendered = false; renderPage(p.n); }
      });
      cv.querySelector('[data-zoomlabel]').textContent = Math.round(zoom * 100) + '%';
    }
    function redrawAnn(p) {
      if (!p.annSized) { return; }
      var ctx = p.ann.getContext('2d'), k = p.ann.width / p.w; ctx.clearRect(0, 0, p.ann.width, p.ann.height);
      (st.an.shapes['p' + p.n] || []).forEach(function (s) { drawShape(ctx, s, k); }); if (p.surface.draft) { drawShape(ctx, p.surface.draft, k); }
    }
    st.redraw = function (key) { pages.forEach(function (p) { if (!key || p.surface.key === key) { redrawAnn(p); } }); };
    function renderPage(n) {
      var p = pages[n - 1]; if (!p || p.rendered || dead) { return; } p.rendered = true;
      var s = cssScale(p) * dpr, vp = p.page.getViewport({ scale: s });
      p.canvas.width = Math.round(vp.width); p.canvas.height = Math.round(vp.height); p.canvas.style.width = p.cssW + 'px'; p.canvas.style.height = p.cssH + 'px';
      p.ann.width = Math.round(vp.width); p.ann.height = Math.round(vp.height); p.ann.style.width = p.cssW + 'px'; p.ann.style.height = p.cssH + 'px'; p.annSized = true;
      if (renders[n]) { try { renders[n].cancel(); } catch (e) {} }
      var task = p.page.render({ canvasContext: p.canvas.getContext('2d'), viewport: vp, background: '#ffffff' }); renders[n] = task;
      task.promise.then(function () { p.el.classList.add('is-ready'); redrawAnn(p); }).catch(function () { p.rendered = false; });
    }
    function unload(n) {   // far away pages give their memory back
      var p = pages[n - 1]; if (!p || !p.rendered || st.an.shapes['p' + n] && st.an.shapes['p' + n].length && false) { return; }
      if (renders[n]) { try { renders[n].cancel(); } catch (e) {} } p.rendered = false; p.annSized = false; p.el.classList.remove('is-ready'); p.canvas.width = p.canvas.height = 1; p.ann.width = p.ann.height = 1;
    }
    function onScroll() {
      var mid = holder.scrollTop + holder.clientHeight / 3, n = 1; for (var i = 0; i < pages.length; i++) { if (pages[i].el.offsetTop <= mid) { n = i + 1; } }
      if (n !== current || !holder.__shown) { current = n; holder.__shown = true; cv.querySelector('[data-sub]').textContent = 'Page ' + n + ' of ' + pages.length; }
    }
    cv.querySelector('[data-zoomtools]').onclick = function (e) {
      var b = e.target.closest('[data-zoom]'); if (!b || !pages.length) { return; } var z = b.getAttribute('data-zoom'), keep = (holder.scrollTop + holder.clientHeight / 2) / holder.scrollHeight;
      zoom = z === 'fit' ? 1 : Math.min(3, Math.max(0.5, zoom * (z === 'in' ? 1.25 : 0.8))); layout(false); holder.scrollTop = keep * holder.scrollHeight - holder.clientHeight / 2;
    };
    var onResize = function () { if (pages.length) { layout(false); } }; window.addEventListener('resize', onResize);
    st.pdfPages = function () { return pages; }; st.pdfDoc = function () { return doc; };
    st.destroy = function () { dead = true; window.removeEventListener('resize', onResize); if (io) { io.disconnect(); } try { doc && doc.destroy(); } catch (e) {} st.redraw = null; };
  }

  /* ------------------------------------------------------------------ sending the annotated copy */
  function sendAnnotated() {
    if (!st.an.count()) { return; }
    var f = st.files[st.index], note = (cv.querySelector('[data-note]') || {}).value || '';
    busy(true, st.kind === 'pdf' ? 'Preparing your annotated PDF…' : 'Preparing your picture…');
    var job;
    if (st.kind === 'image') {
      job = new Promise(function (resolve) {
        var c = st.exportCanvas(), png = /png$/i.test(f.ext); var name = 'annotated-' + f.name.replace(/\.[^.]+$/, '') + (png ? '.png' : '.jpg');
        c.toBlob(function (b) { resolve({ blob: b, name: name, extra: {} }); }, png ? 'image/png' : 'image/jpeg', 0.9);
      });
    } else { job = exportPdf(f, cv.querySelector('[name=scope]:checked') ? cv.querySelector('[name=scope]:checked').value : 'all'); }
    if (st.onApply) {
      job.then(function (r) { var apply = st.onApply; st.an.clear(); busy(false); close(); apply(r); }).catch(function (e) { busy(false); toast(e && e.message ? e.message : 'Could not prepare the drawn-on copy.'); });
      return;
    }
    job.then(function (r) {
      busy(true, 'Sending…');
      return host.sendFile(r.blob, r.name, Object.assign({ annotated_of: f.id, body: note }, r.extra, { onProgress: function (p) { busy(true, 'Sending… ' + Math.round(p * 100) + '%'); } }));
    }).then(function () { st.an.clear(); busy(false); toast('Annotated copy sent to the chat'); close(); })
      .catch(function (e) { busy(false); toast(e && e.message ? e.message : 'Could not send the annotated copy.'); });
  }

  function exportPdf(f, scope) {
    var pagesInfo = st.pdfPages(), marked = pagesInfo.filter(function (p) { return (st.an.shapes['p' + p.n] || []).length; });
    return Promise.all([pdflib(), fetch(f.url, { credentials: 'same-origin' }).then(function (r) { if (!r.ok) { throw new Error('The PDF could not be downloaded for editing.'); } return r.arrayBuffer(); })]).then(function (res) {
      var L = res[0], bytes = res[1];
      return L.PDFDocument.load(bytes, { ignoreEncryption: true, updateMetadata: false }).then(function (src) {
        var target = src, pageOf = function (i) { return src.getPage(i); }, setup = Promise.resolve();
        if (scope === 'marked') { setup = L.PDFDocument.create().then(function (doc) { target = doc; return doc.copyPages(src, marked.map(function (p) { return p.n - 1; })); }).then(function (copied) { copied.forEach(function (p) { target.addPage(p); }); pageOf = function (i) { return target.getPage(marked.findIndex(function (p) { return p.n - 1 === i; })); }; }); }
        return setup.then(function () {
          return marked.reduce(function (chain, p) {
            return chain.then(function () {
              var k = Math.min(3, 4000 / Math.max(p.w, p.h)), c = document.createElement('canvas'); c.width = Math.max(1, Math.round(p.w * k)); c.height = Math.max(1, Math.round(p.h * k));
              var ctx = c.getContext('2d'); st.an.shapes['p' + p.n].forEach(function (s) { drawShape(ctx, s, k); });
              return new Promise(function (r) { c.toBlob(r, 'image/png'); }).then(function (b) { return b.arrayBuffer(); }).then(function (png) { return target.embedPng(png); }).then(function (img) {
                var page = pageOf(p.n - 1), rot = ((page.getRotation().angle % 360) + 360) % 360, cb = page.getCropBox ? page.getCropBox() : page.getMediaBox(), bx = cb.x, by = cb.y, w = cb.width, h = cb.height;
                if (rot === 90) { page.drawImage(img, { x: bx + w, y: by, width: h, height: w, rotate: L.degrees(90) }); }
                else if (rot === 180) { page.drawImage(img, { x: bx + w, y: by + h, width: w, height: h, rotate: L.degrees(180) }); }
                else if (rot === 270) { page.drawImage(img, { x: bx, y: by + h, width: h, height: w, rotate: L.degrees(-90) }); }
                else { page.drawImage(img, { x: bx, y: by, width: w, height: h }); }
              });
            });
          }, Promise.resolve());
        }).then(function () { return target.save({ useObjectStreams: true }); }).then(function (out) {
          var first = marked[0], preview = null;
          var done = function () { return { blob: new Blob([out], { type: 'application/pdf' }), name: 'annotated-' + f.name.replace(/\.pdf$/i, '') + '.pdf', extra: { preview: preview, meta: { pages: target.getPageCount() } } }; };
          // small preview picture: the first marked page as the viewer shows it
          try {
            var c = document.createElement('canvas'), s = 320 / first.w; c.width = 320; c.height = Math.round(first.h * s); var cx = c.getContext('2d'); cx.fillStyle = '#fff'; cx.fillRect(0, 0, c.width, c.height);
            if (first.rendered) { cx.drawImage(first.canvas, 0, 0, c.width, c.height); } st.an.shapes['p' + first.n].forEach(function (sh) { drawShape(cx, sh, s); });
            return new Promise(function (r) { c.toBlob(function (b) { preview = b; r(done()); }, 'image/jpeg', 0.8); });
          } catch (e) { return done(); }
        });
      });
    });
  }

  /* ------------------------------------------------------------------ first page of a PDF the person is about to attach */
  function pdfPreview(file) {
    if (file.size > 40 * 1048576) { return Promise.resolve(null); }
    return pdfjs().then(function (lib) { return file.arrayBuffer().then(function (buf) { return lib.getDocument({ data: buf }).promise; }); }).then(function (pdf) {
      return pdf.getPage(1).then(function (page) {
        var v = page.getViewport({ scale: 1 }), s = 360 / v.width, vp = page.getViewport({ scale: s }), c = document.createElement('canvas'); c.width = Math.round(vp.width); c.height = Math.round(vp.height);
        return page.render({ canvasContext: c.getContext('2d'), viewport: vp, background: '#ffffff' }).promise.then(function () {
          return new Promise(function (r) { c.toBlob(function (b) { r(b ? { blob: b, pages: pdf.numPages } : null); try { pdf.destroy(); } catch (e) {} }, 'image/jpeg', 0.8); });
        });
      });
    });
  }

  PC.viewer = { open: open, pdfPreview: pdfPreview };
}());

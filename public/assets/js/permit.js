/* Permit Revisions wizard. The estimate maths mirror app/Support/PermitEstimator.php (numbers come from config/permit.php via #pr-config).
   The server works the estimate out again; this only shows it straight away. */
(function () {
  'use strict';
  var cfgEl = document.getElementById('pr-config');
  var form = document.getElementById('pr-form');
  if (!cfgEl || !form) return;
  var C = JSON.parse(cfgEl.textContent);
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  var steps = $$('[data-step]', form), total = steps.length, cur = 1;
  var files = [];                 // {file, ok, msg, pages, scanned}
  var estimate = null, busy = false;
  var NAMES = ['What do you need', 'About the project', 'Your files', 'Details', 'Estimate and contact'];

  function val(name) { var e = form.querySelector('[name="' + name + '"]:checked'); return e ? e.value : ''; }
  function fv(name) { var e = form.elements[name]; return e ? String(e.value || '').trim() : ''; }
  function areas() { return $$('input[name="areas[]"]:checked', form).map(function (e) { return e.value; }); }
  function err(n, msg) { var e = $('#pr-err' + n); if (e) e.textContent = msg || ''; }
  function money(n) { return C.currency + Number(n).toLocaleString('en-US'); }
  function rateText() { return C.currency + String(+C.rate); }

  /* ---------- the estimate (same steps as PermitEstimator.php) ---------- */
  function calc() {
    var need = val('need'), sheets = Math.max(0, parseInt(fv('sheets'), 10) || 0);
    var comments = need === 'new_set' ? 0 : Math.max(0, parseInt(fv('comments'), 10) || 0);
    var fmt = val('format') || 'native', sel = areas(), country = fv('country'), r = [];
    if (need === 'new_set') r.push('New permit sets are scoped individually.');
    if (need === 'unsure') r.push('We want to look at your set before suggesting a scope.');
    if (C.auto_countries.indexOf(country) < 0) r.push('We confirm local requirements outside the United States before pricing.');
    if (comments > C.manual_if_comments_over) r.push('A letter with more than ' + C.manual_if_comments_over + ' comments needs a manual review.');
    if (sheets > C.manual_if_sheets_over) r.push('Sets of more than ' + C.manual_if_sheets_over + ' sheets are quoted after review.');
    if (val('scope') === 'change_use' || val('ptype') === 'other') r.push('This project type needs a closer look.');
    if (r.length) return { manual: true, reasons: r };
    var affected = need === 'design_change' ? Math.max(1, Math.round(sheets * 0.5)) : Math.min(sheets, Math.max(1, Math.round(comments * 0.7)));
    var units = need === 'design_change' ? 0 : comments;
    var sub = C.base_hours + units * C.hours_per_comment + affected * C.hours_per_affected_sheet;
    var up = Math.min(C.area_cap, sel.reduce(function (s, k) { return s + (C.area_add[k] || 0); }, 0));
    var hours = Math.max(C.min_hours, sub * (C.format_mult[fmt] || 1) * (1 + up));
    var price = hours * C.rate, r5 = function (n) { return Math.round(n / 5) * 5; };
    return { manual: false, hours: hours, hours_low: Math.round(hours * C.range_low), hours_high: Math.round(hours * C.range_high),
      price_low: r5(price * C.range_low), price_high: r5(price * C.range_high), affected: affected, units: units, uplift: up, fmt: fmt };
  }
  window.__prCalc = calc;   // used by the automated parity test

  /* ---------- step display ---------- */
  function show(n) {
    cur = n;
    steps.forEach(function (s, i) { s.hidden = (i + 1) !== n; });
    $$('#pr-progress i').forEach(function (b, i) { b.classList.toggle('on', i < n); });
    $('#pr-stepname').textContent = 'Step ' + n + ' of ' + total + ' · ' + NAMES[n - 1];
    $('#pr-back').style.visibility = n === 1 ? 'hidden' : 'visible';
    $('#pr-next').textContent = n === total ? 'Send my enquiry' : 'Continue';
    $('#pr-commentsbox').hidden = val('need') === 'new_set';
    if (n === 3) fileTip();
    if (n === total) { renderEstimate(); if (window.architiveCaptchaLoad) window.architiveCaptchaLoad(); }
    var top = $('#pr-stepname'); if (top && top.scrollIntoView && n > 1) top.scrollIntoView({ block: 'start', behavior: 'smooth' });
  }

  /* ---------- validation ---------- */
  function validate(n) {
    err(n, '');
    var m = '';
    if (n === 1 && !val('need')) m = 'Please choose what you need.';
    if (n === 2) {
      if (!val('ptype')) m = 'Please choose a project type.';
      else if (!val('scope')) m = 'Please choose the scope of work.';
      else if (!fv('country')) m = 'Please select a country.';
      else if (!fv('authority')) m = 'Please tell us which city or county issues the permit.';
    }
    if (n === 3) {
      var noLetter = $('#pr-nocomment').checked;
      if (!files.length && !noLetter) m = 'Add at least one file, or tick the box if you only have the drawings later.';
      else if (files.some(function (f) { return !f.ok; })) m = 'Please remove the files marked with a problem, or replace them.';
      else if (totalMb() > C.max_post_mb) m = 'Your files add up to ' + totalMb().toFixed(1) + ' MB. This form accepts about ' + C.max_post_mb + ' MB in total. Please remove some or zip them.';
    }
    if (n === 4) {
      var s = parseInt(fv('sheets'), 10), c = fv('comments');
      if (!(s >= 1 && s <= 500)) m = 'Please enter how many sheets are in the set (1 to 500).';
      else if (val('need') !== 'new_set' && !(c !== '' && parseInt(c, 10) >= 0 && parseInt(c, 10) <= 500)) m = 'Please enter the number of city comments (0 to 500).';
      else if (!val('format')) m = 'Please choose the format of the original files.';
      else if (!fv('seal')) m = 'Please tell us whether the drawings need to be stamped or sealed.';
      else if (fv('deadline') && fv('deadline') < dayStr(-2)) m = 'The deadline has already passed. Leave it empty or choose a later date.';
    }
    if (n === 5) {
      if (!fv('name')) m = 'Please enter your name.';
      else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(fv('email'))) m = 'Please enter a valid email address.';
      else if (!$('#pr-consent').checked) m = 'Please tick the consent box so we can contact you.';
      else if (captchaOn() && !captchaToken()) m = 'Please tick “I’m not a robot” to continue.';
    }
    if (m) { err(n, m); return false; }
    return true;
  }
  function dayStr(offset) { var d = new Date(); d.setDate(d.getDate() + offset); return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2); }

  /* ---------- files ---------- */
  function ext(n) { var i = n.lastIndexOf('.'); return i < 0 ? '' : n.slice(i + 1).toLowerCase(); }
  function totalMb() { return files.reduce(function (s, f) { return s + f.file.size; }, 0) / 1048576; }
  function readHead(file, n) { return new Promise(function (res) { var r = new FileReader(); r.onload = function () { res(new Uint8Array(r.result)); }; r.onerror = function () { res(null); }; r.readAsArrayBuffer(file.slice(0, n)); }); }
  function bytesText(u8) { var s = ''; for (var i = 0; i < u8.length; i++) s += String.fromCharCode(u8[i]); return s; }

  function checkFile(file) {
    var rec = { file: file, ok: true, msg: '' };
    var e = ext(file.name);
    if (C.allowed_extensions.indexOf(e) < 0) { rec.ok = false; rec.msg = 'This file type is not supported.'; return Promise.resolve(rec); }
    if (file.size === 0) { rec.ok = false; rec.msg = 'This file is empty.'; return Promise.resolve(rec); }
    if (file.size > C.max_file_mb * 1048576) { rec.ok = false; rec.msg = 'Over ' + C.max_file_mb + ' MB.'; return Promise.resolve(rec); }
    if (e !== 'pdf') return Promise.resolve(rec);
    // PDF: confirm it is a PDF, then count pages and look for text (a PDF without fonts is probably a scan)
    return new Promise(function (res) {
      var r = new FileReader();
      r.onload = function () {
        var t = bytesText(new Uint8Array(r.result));
        if (t.slice(0, 1024).indexOf('%PDF-') < 0) { rec.ok = false; rec.msg = 'This does not look like a valid PDF.'; }
        else { var m = t.match(/\/Type\s*\/Page[^s]/g); rec.pages = m ? m.length : 0; rec.scanned = t.indexOf('/Font') < 0 && rec.pages > 0; }
        res(rec);
      };
      r.onerror = function () { res(rec); };
      r.readAsArrayBuffer(file.size > 6 * 1048576 ? file.slice(0, 6 * 1048576) : file);   // very large PDFs: look at the start only
    });
  }
  function addFiles(list) {
    var incoming = Array.prototype.slice.call(list), room = C.max_files - files.length, extra = '';
    if (incoming.length > room) { extra = 'You can attach up to ' + C.max_files + ' files. Zip extra files together.'; incoming = incoming.slice(0, Math.max(0, room)); }
    Promise.all(incoming.map(function (f) { return checkFile(f); })).then(function (recs) {
      recs.forEach(function (r) {
        var dup = files.some(function (x) { return x.file.name === r.file.name && x.file.size === r.file.size; });
        if (!dup) files.push(r);
      });
      renderFiles(); err(3, extra);
    });
  }
  function renderFiles() {
    var ul = $('#pr-filelist'); ul.innerHTML = '';
    files.forEach(function (f, i) {
      var li = document.createElement('li'); if (!f.ok) li.className = 'bad';
      var s = document.createElement('span');
      s.textContent = f.file.name; var em = document.createElement('em');
      em.textContent = f.ok ? (f.file.size / 1048576).toFixed(1) + ' MB' + (f.pages ? ' · ' + f.pages + ' page' + (f.pages > 1 ? 's' : '') : '') : f.msg;
      s.appendChild(em);
      var b = document.createElement('button'); b.type = 'button'; b.setAttribute('aria-label', 'Remove ' + f.file.name); b.textContent = '×';
      b.addEventListener('click', function () { files.splice(i, 1); renderFiles(); });
      li.appendChild(s); li.appendChild(b); ul.appendChild(li);
    });
    fileTip();
  }
  function fileTip() {
    var tip = $('#pr-filetip'), out = [];
    var pdfs = files.filter(function (f) { return f.ok && f.pages; });
    var pages = pdfs.reduce(function (s, f) { return s + f.pages; }, 0);
    if (pages) out.push('Your PDF files have about ' + pages + ' page' + (pages > 1 ? 's' : '') + ' in total. You can use this to fill in the sheet count later.');
    if (pdfs.some(function (f) { return f.scanned; })) out.push('One of your PDFs looks like a scan or photo. If the originals are scans, choose “Scans or photos” in the format question so the estimate fits.');
    if (totalMb() > C.max_post_mb) out.push('Your files add up to ' + totalMb().toFixed(1) + ' MB, more than this form accepts (' + C.max_post_mb + ' MB). Please remove or zip some.');
    tip.innerHTML = ''; out.forEach(function (t) { var p = document.createElement('p'); p.textContent = t; tip.appendChild(p); });
  }
  var fin = $('#pr-filein'), drop = $('#pr-drop');
  fin.addEventListener('change', function () { addFiles(fin.files); fin.value = ''; });
  ['dragenter', 'dragover'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('over'); }); });
  ['dragleave', 'drop'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('over'); }); });
  drop.addEventListener('drop', function (e) { if (e.dataTransfer && e.dataTransfer.files) addFiles(e.dataTransfer.files); });

  /* ---------- estimate screen ---------- */
  function el(tag, cls, text) { var e = document.createElement(tag); if (cls) e.className = cls; if (text) e.textContent = text; return e; }
  function renderEstimate() {
    estimate = calc();
    var box = $('#pr-estbox'), sub = $('#pr-estsub'); box.innerHTML = '';
    var wrap = el('div', 'pr-est' + (estimate.manual ? ' manual' : ''));
    if (estimate.manual) {
      sub.textContent = 'Almost done. Add your contact details and we will prepare a quotation.';
      wrap.appendChild(el('div', 'lbl', 'Quote after review'));
      wrap.appendChild(el('div', 'big', 'We will quote this for you'));
      var ul = el('ul'); estimate.reasons.forEach(function (r) { ul.appendChild(el('li', '', r)); }); wrap.appendChild(ul);
    } else {
      sub.textContent = 'An indicative range based on your answers. Add your contact details and we will confirm it in writing.';
      wrap.appendChild(el('div', 'lbl', 'Estimated cost'));
      wrap.appendChild(el('div', 'big', money(estimate.price_low) + ' to ' + money(estimate.price_high)));
      wrap.appendChild(el('div', 'hrs', 'About ' + estimate.hours_low + ' to ' + estimate.hours_high + ' hours at our introductory rate of ' + rateText() + ' per hour'));
      var ul2 = el('ul');
      ul2.appendChild(el('li', '', 'Sheets we expect to revise: about ' + estimate.affected));
      if (estimate.units) ul2.appendChild(el('li', '', 'City comments to address: ' + estimate.units));
      ul2.appendChild(el('li', '', 'Final price, turnaround and any rush charge are confirmed in your written quotation.'));
      wrap.appendChild(ul2);
    }
    var seal = fv('seal');
    if (seal === 'have' || seal === 'need') wrap.appendChild(el('p', 'pr-sealnote', seal === 'have'
      ? 'Stamped or sealed drawings: your licensed professional signs and seals the final set. We prepare the drawings for their review.'
      : 'Stamped or sealed drawings: Architive does not provide a licence or seal. We can help you find the right professional, and their fee is separate from this estimate.'));
    var dl = fv('deadline');
    if ($('#pr-rush').checked || (dl && dl <= dayStr(7))) wrap.appendChild(el('p', 'pr-sealnote', 'You need this quickly. We will confirm in our reply what turnaround is possible and whether a rush charge applies.'));
    box.appendChild(wrap);
  }

  /* ---------- captcha ---------- */
  function captchaOn() { return !!$('[data-captcha]', form); }
  function captchaToken() { var t = form.querySelector('[name="g-recaptcha-response"]'); return t && t.value ? t.value : ''; }

  /* ---------- submit ---------- */
  function summaryText() {
    var t = ['PERMIT REVISION ENQUIRY', 'Need: ' + val('need'), 'Project: ' + val('ptype') + ', ' + val('scope'),
      'Location: ' + [fv('authority'), fv('state'), fv('country')].filter(Boolean).join(', '),
      'Sheets: ' + fv('sheets') + (val('need') !== 'new_set' ? ' | City comments: ' + fv('comments') : ''),
      'Areas: ' + (areas().join(', ') || 'not specified'), 'Format: ' + val('format') + ' | Seal: ' + fv('seal')];
    if (fv('deadline')) t.push('Deadline: ' + fv('deadline'));
    if ($('#pr-rush').checked) t.push('URGENT: fast turnaround requested');
    if (fv('details')) t.push('Notes: ' + fv('details'));
    if (estimate) t.push(estimate.manual ? 'Estimate: manual quote' : 'Estimate: ' + money(estimate.price_low) + ' to ' + money(estimate.price_high) + ' (' + estimate.hours_low + ' to ' + estimate.hours_high + ' hours)');
    t.push('Name: ' + fv('name'), 'Email: ' + fv('email'));
    return t.join('\n');
  }
  function stepForField(k) {
    k = k.replace(/\..*$/, '').replace(/\[\]$/, '');
    if (k === 'need') return 1;
    if (['ptype', 'scope', 'country', 'state', 'authority'].indexOf(k) >= 0) return 2;
    if (k === 'files') return 3;
    if (['sheets', 'comments', 'areas', 'format', 'seal', 'deadline', 'details', 'rush'].indexOf(k) >= 0) return 4;
    return 5;
  }
  function fail(msg) {
    steps.forEach(function (s) { s.hidden = true; }); $('#pr-nav').hidden = true;
    var d = $('#pr-done'); d.hidden = false; d.classList.add('fail');
    $('.pr-tick', d).textContent = '!';
    $('#pr-donetitle').textContent = 'We could not send your enquiry';
    $('#pr-donemsg').textContent = msg + ' You can email us instead. Your answers are summarised below.';
    var sum = summaryText(); $('#pr-sumtxt').textContent = sum;
    var mb = $('#pr-mailbtn'); mb.hidden = false;
    mb.href = 'mailto:' + C.email + '?subject=' + encodeURIComponent('Permit revisions enquiry') + '&body=' + encodeURIComponent(sum + '\n\n(Please attach your files to this email.)');
    var cb = $('#pr-copybtn'); cb.hidden = false;
    cb.onclick = function () { if (navigator.clipboard) navigator.clipboard.writeText(sum).then(function () { cb.textContent = 'Copied'; }); };
  }
  function submit() {
    if (busy) return; busy = true;
    var btn = $('#pr-next'); btn.disabled = true; btn.textContent = 'Sending...'; err(5, '');
    var fd = new FormData(form);
    files.forEach(function (f) { fd.append('files[]', f.file, f.file.name); });
    if ($('#pr-nocomment').checked) fd.append('no_letter', '1');
    if ($('#pr-rush').checked) fd.append('rush', '1');
    if (!$('#pr-nda').checked) fd.delete('nda');
    var done = function () { busy = false; btn.disabled = false; btn.textContent = 'Send my enquiry'; };
    fetch(C.endpoint, { method: 'POST', body: fd, headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
      .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { return { status: r.status, body: j }; }); })
      .then(function (x) {
        var j = x.body || {};
        if (x.status === 422 && j.errors) {
          done();
          var keys = Object.keys(j.errors), first = j.errors[keys[0]][0], st = Math.min.apply(null, keys.map(stepForField));
          show(st); err(st, first);
          if (window.grecaptcha && captchaOn()) { try { window.grecaptcha.reset(); } catch (e) {} }
          return;
        }
        if (x.status === 419) { done(); return fail('Your session expired. Please reload the page and try again.'); }
        if (!x.status || x.status >= 400 || !j.ok) { done(); return fail(j.message || 'Something went wrong on our side.'); }
        showDone(j);
      })
      .catch(function () { done(); fail('We could not reach the server. Please check your connection.'); });
  }
  function showDone(j) {
    steps.forEach(function (s) { s.hidden = true; }); $('#pr-nav').hidden = true;
    $$('#pr-progress i').forEach(function (b) { b.classList.add('on'); });
    $('#pr-stepname').textContent = 'Done';
    var d = $('#pr-done'); d.hidden = false;
    $('#pr-donetitle').textContent = 'Enquiry sent';
    $('#pr-donemsg').textContent = j.message || 'Thank you. We have your enquiry.';
    $('#pr-sumtxt').textContent = '';
    if (j.portal_url) { var p = $('#pr-portal'); p.href = j.portal_url; p.hidden = false; }
    d.scrollIntoView({ block: 'center', behavior: 'smooth' });
  }

  /* ---------- wiring ---------- */
  $('#pr-next').addEventListener('click', function () {
    if (!validate(cur)) return;
    if (cur < total) show(cur + 1); else submit();
  });
  $('#pr-back').addEventListener('click', function () { if (cur > 1) { err(cur, ''); show(cur - 1); } });
  form.addEventListener('submit', function (e) { e.preventDefault(); $('#pr-next').click(); });
  $$('input[name="need"]', form).forEach(function (r) { r.addEventListener('change', function () { $('#pr-commentsbox').hidden = val('need') === 'new_set'; }); });
  var dl = $('#pr-deadline'); if (dl) dl.min = dayStr(0);
  $('#pr-nocomment').addEventListener('change', function () { err(3, ''); });
  window.addEventListener('beforeunload', function (e) { if (!busy) return; e.preventDefault(); e.returnValue = ''; });
  show(1);
})();

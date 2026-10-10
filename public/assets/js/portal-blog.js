/* ==========================================================================
   Blog post editor (admin): rich text, picture upload, web address, tags, publish date,
   live Google preview and a plain-language SEO checklist.  Markup: portal/admin/blog/form.blade.php
   ========================================================================== */
(function () {
  'use strict';

  var form = document.getElementById('postForm');
  if (!form) { return; }

  function $(s, r) { return (r || form).querySelector(s); }
  function $$(s, r) { return Array.prototype.slice.call((r || form).querySelectorAll(s)); }
  var csrf = $('input[name=_token]').value, site = form.getAttribute('data-site'), suffix = form.getAttribute('data-suffix') || '';
  var dirty = false, submitting = false;
  function touch() { dirty = true; }

  /* ---- rich text editor ------------------------------------------------------ */
  var content = $('[data-content]'), quill = null;
  if (window.Quill) {
    quill = new Quill('#editor', {
      theme: 'snow',
      placeholder: 'Start writing your article…',
      modules: {
        toolbar: {
          container: [[{ header: [2, 3, 4, false] }], ['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['blockquote', 'link', 'image'], [{ align: [] }], ['clean']],
          handlers: { image: pickImage }
        }
      }
    });
    if (content.value.trim()) { quill.clipboard.dangerouslyPasteHTML(0, content.value); quill.setSelection(0, 0); }
    quill.on('text-change', function () { touch(); updateCount(); runSeo(); });
  }

  function html() {
    if (!quill) { return content.value; }
    var empty = quill.getText().trim() === '' && !quill.root.querySelector('img');
    return empty ? '' : quill.root.innerHTML;
  }
  function text() { return quill ? quill.getText() : content.value.replace(/<[^>]+>/g, ' '); }
  function words() { var t = text().trim(); return t ? t.split(/\s+/).length : 0; }
  function updateCount() { var n = words(); $('[data-wordcount]').textContent = n.toLocaleString() + (n === 1 ? ' word' : ' words') + ' · about ' + Math.max(1, Math.round(n / 220)) + ' min read'; }

  function upload(file) {
    var fd = new FormData(); fd.append('_token', csrf); fd.append('image', file);
    return fetch(form.getAttribute('data-media-url'), { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json().then(function (j) { if (!r.ok) { throw new Error((j.errors && j.errors.image && j.errors.image[0]) || j.message || 'Upload failed'); } return j; }); });
  }
  function insertImage(file) {
    if (!file || !/^image\//.test(file.type)) { return; }
    var range = quill.getSelection(true), idx = range ? range.index : quill.getLength();
    upload(file).then(function (j) { quill.insertEmbed(idx, 'image', j.url, 'user'); quill.setSelection(idx + 1, 0); })
      .catch(function (e) { window.alert(e.message || 'The picture could not be uploaded.'); });
  }
  function pickImage() {
    var input = document.createElement('input'); input.type = 'file'; input.accept = 'image/png,image/jpeg,image/webp,image/gif';
    input.addEventListener('change', function () { insertImage(input.files[0]); });
    input.click();
  }
  if (quill) {
    quill.root.addEventListener('paste', function (e) {
      var f = e.clipboardData && e.clipboardData.files && e.clipboardData.files[0];
      if (f && /^image\//.test(f.type)) { e.preventDefault(); insertImage(f); }
    });
    quill.root.addEventListener('drop', function (e) {
      var f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
      if (f && /^image\//.test(f.type)) { e.preventDefault(); insertImage(f); }
    });
  }

  /* ---- title + web address --------------------------------------------------- */
  var title = $('[data-title]'), slug = $('[data-slug]');
  function slugify(s) { return s.toLowerCase().normalize('NFKD').replace(/[̀-ͯ]/g, '').replace(/&/g, ' and ').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 100).replace(/-+$/, ''); }
  title.addEventListener('input', function () { if (slug.getAttribute('data-touched') !== '1') { slug.value = slugify(title.value); } touch(); runSeo(); });
  slug.addEventListener('input', function () { slug.setAttribute('data-touched', '1'); touch(); runSeo(); });
  slug.addEventListener('blur', function () { slug.value = slugify(slug.value); runSeo(); });

  /* ---- counters + meters ------------------------------------------------------ */
  function counters() {
    $$('[data-counter]').forEach(function (el) {
      var c = $('[data-count-for="' + el.id + '"]'); if (c) { c.textContent = el.value.length; }
      var m = $('[data-meter-for="' + el.id + '"]');
      if (m) {
        var n = el.value.length, min = +m.getAttribute('data-min'), max = +m.getAttribute('data-max');
        m.style.width = Math.min(100, (n / max) * 100) + '%';
        m.className = !n ? '' : (n < min ? 'is-low' : (n <= max ? 'is-good' : 'is-high'));
      }
    });
  }
  $$('[data-counter]').forEach(function (el) { el.addEventListener('input', function () { touch(); counters(); runSeo(); }); });

  /* ---- tags -------------------------------------------------------------------- */
  var tagsHidden = $('[data-tags]'), chips = $('[data-tag-chips]'), tagInput = $('[data-tag-input]'), tags = tagsHidden.value.split(',').map(function (t) { return t.trim(); }).filter(Boolean);
  function renderTags() {
    chips.innerHTML = '';
    tags.forEach(function (t, i) {
      var c = document.createElement('span'); c.className = 'tagchip'; c.appendChild(document.createTextNode(t));
      var x = document.createElement('button'); x.type = 'button'; x.innerHTML = '&times;'; x.setAttribute('aria-label', 'Remove tag ' + t);
      x.addEventListener('click', function () { tags.splice(i, 1); renderTags(); touch(); });
      c.appendChild(x); chips.appendChild(c);
    });
    tagsHidden.value = tags.join(', ');
  }
  function addTag(v) {
    v = v.replace(/,/g, ' ').trim();
    if (!v || tags.length >= 15 || tags.some(function (t) { return t.toLowerCase() === v.toLowerCase(); })) { return; }
    tags.push(v); renderTags(); touch();
  }
  tagInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); addTag(tagInput.value); tagInput.value = ''; }
    else if (e.key === 'Backspace' && !tagInput.value && tags.length) { tags.pop(); renderTags(); touch(); }
  });
  tagInput.addEventListener('change', function () { addTag(tagInput.value); tagInput.value = ''; });
  tagInput.addEventListener('blur', function () { addTag(tagInput.value); tagInput.value = ''; });
  $('[data-tagbox]').addEventListener('click', function () { tagInput.focus(); });
  renderTags();

  /* ---- featured image ----------------------------------------------------------- */
  var imgInput = $('[data-image-input]'), imgPrev = $('[data-image-preview]'), zone = $('[data-drop-zone]');
  function showImage(file) {
    if (!file) { return; }
    imgPrev.src = URL.createObjectURL(file); imgPrev.hidden = false;
    var empty = $('.bdrop__empty', zone), change = $('.bdrop__change', zone);
    if (empty) { empty.hidden = true; } if (change) { change.hidden = false; }
    zone.classList.add('has-image'); touch(); runSeo();
  }
  imgInput.addEventListener('change', function () { showImage(imgInput.files[0]); });
  ['dragenter', 'dragover'].forEach(function (ev) { zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.add('is-over'); }); });
  ['dragleave', 'drop'].forEach(function (ev) { zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.remove('is-over'); }); });
  zone.addEventListener('drop', function (e) {
    var f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
    if (f && /^image\//.test(f.type)) { var dt = new DataTransfer(); dt.items.add(f); imgInput.files = dt.files; showImage(f); }
  });
  var alt = $('[data-alt]'); alt.addEventListener('input', function () { touch(); runSeo(); });

  /* ---- publish date (shown in your own time zone, saved as UTC) -------------------- */
  var utc = $('[data-publish-utc]'), local = $('[data-publish-local]'), hint = $('[data-publish-hint]');
  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function toLocalValue(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes()); }
  if (utc.value) { var d0 = new Date(utc.value); if (!isNaN(d0)) { local.value = toLocalValue(d0); } }
  function publishHint() {
    if (!local.value) { hint.textContent = 'Leave empty to publish right away. Pick a future date to schedule the article.'; return; }
    var d = new Date(local.value);
    hint.textContent = d > new Date() ? 'Will go live automatically on ' + d.toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' }) + '.' : 'Dated ' + d.toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' }) + '.';
  }
  local.addEventListener('input', function () { var d = local.value ? new Date(local.value) : null; utc.value = d && !isNaN(d) ? d.toISOString() : ''; publishHint(); touch(); });
  publishHint();

  /* ---- Google preview ------------------------------------------------------------- */
  var focus = $('[data-focus]'), metaTitle = $('[data-meta-title]'), metaDesc = $('[data-meta-desc]'), excerpt = $('#excerpt');
  function clip(s, n) { return s.length > n ? s.slice(0, n - 1).replace(/\s+\S*$/, '') + '…' : s; }
  function serp() {
    var t = metaTitle.value.trim() || ((title.value.trim() || 'Article title') + suffix);
    var d = metaDesc.value.trim() || excerpt.value.trim() || text().trim().replace(/\s+/g, ' ');
    $('[data-serp-url]').textContent = site + ' › blog › ' + (slug.value || '…');
    $('[data-serp-title]').textContent = clip(t, 62);
    $('[data-serp-desc]').textContent = clip(d || 'Your description will appear here.', 160);
  }

  /* ---- SEO checklist ---------------------------------------------------------------- */
  function runSeo() {
    serp();
    var kw = focus.value.trim().toLowerCase(), t = (title.value + ' ' + metaTitle.value).toLowerCase(), d = (metaDesc.value || excerpt.value).toLowerCase();
    var body = text().toLowerCase(), first = body.split(/\s+/).slice(0, 120).join(' ');
    var mt = (metaTitle.value || title.value + suffix).length, md = metaDesc.value.length;
    var h = quill ? quill.root.querySelectorAll('h2, h3').length : 0;
    var links = quill ? quill.root.querySelectorAll('a').length : 0;
    var hasImg = !!imgPrev && !imgPrev.hidden;
    var items = [
      [mt >= 30 && mt <= 62, 'Title length', mt ? 'Your search title is ' + mt + ' characters. Aim for 30 to 60.' : 'Add a title.'],
      [md >= 110 && md <= 160, 'Meta description', md ? 'Your description is ' + md + ' characters. Aim for 110 to 160.' : 'Write a meta description. It is the text shown under the title in Google.'],
      [!!kw, 'Focus keyword', kw ? 'Set: “' + focus.value.trim() + '”.' : 'Add the phrase people would type into Google to find this article.'],
      [!kw || t.indexOf(kw) > -1, 'Keyword in the title', 'The focus keyword should appear in the title.'],
      [!kw || d.indexOf(kw) > -1, 'Keyword in the description', 'Mention the focus keyword in the meta description or summary.'],
      [!kw || first.indexOf(kw) > -1, 'Keyword in the opening', 'Use the focus keyword within the first paragraph.'],
      [!kw || slug.value.indexOf(slugify(kw).split('-')[0]) > -1, 'Keyword in the web address', 'Use the main word of your keyword in the web address.'],
      [words() >= 600, 'Article length', words() + ' words. Helpful articles are usually 600+ words.'],
      [h >= 2, 'Sub-headings', h + ' sub-heading' + (h === 1 ? '' : 's') + '. Add at least 2 (Heading 2 or 3) to make it scannable.'],
      [links >= 1, 'Links', links ? links + ' link' + (links === 1 ? '' : 's') + ' in the article.' : 'Link to another page (for example one of our services) to guide readers.'],
      [hasImg, 'Featured image', hasImg ? 'Added.' : 'Add a featured picture. It is shown on the blog and when the article is shared.'],
      [!hasImg || alt.value.trim().length > 4, 'Picture description', 'Describe the featured picture (alt text) for accessibility and Google Images.']
    ];
    var ok = items.filter(function (i) { return i[0]; }).length, pct = Math.round(ok / items.length * 100);
    var score = $('[data-seo-score]');
    score.textContent = pct + '% · ' + (pct >= 80 ? 'Great' : (pct >= 55 ? 'Good, can improve' : 'Needs work'));
    score.className = 'seo-score ' + (pct >= 80 ? 'is-good' : (pct >= 55 ? 'is-mid' : 'is-low'));
    $('[data-seo-list]').innerHTML = items.map(function (i) {
      return '<div class="seo-item ' + (i[0] ? 'is-ok' : 'is-todo') + '"><span aria-hidden="true">' + (i[0] ? '✓' : '!') + '</span><div><b>' + i[1] + '</b><small>' + i[2].replace(/</g, '&lt;') + '</small></div></div>';
    }).join('');
  }
  [focus, excerpt].forEach(function (el) { el.addEventListener('input', function () { touch(); runSeo(); }); });

  /* ---- submit ------------------------------------------------------------------------ */
  $$('[data-submit]').forEach(function (b) {
    b.addEventListener('click', function () { $('[data-action]').value = b.getAttribute('data-submit'); });
  });
  form.addEventListener('submit', function (e) {
    if (!title.value.trim()) { e.preventDefault(); title.focus(); title.scrollIntoView({ block: 'center', behavior: 'smooth' }); title.classList.add('has-error'); return; }
    content.value = html();
    if (!content.value) { e.preventDefault(); if (quill) { quill.focus(); } window.alert('The article is empty. Write something in the editor first.'); return; }
    submitting = true;
    $$('button[type=submit]').forEach(function (b) { b.disabled = true; });
  });
  $$('input, textarea, select').forEach(function (el) { el.addEventListener('change', touch); });
  window.addEventListener('beforeunload', function (e) { if (dirty && !submitting) { e.preventDefault(); e.returnValue = ''; } });

  updateCount(); counters(); runSeo();
}());

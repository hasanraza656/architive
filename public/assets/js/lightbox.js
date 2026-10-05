/* ==========================================================================
   Architive – lightbox / sheet viewer, gallery filters, before/after pair switcher
   Any <a data-lightbox="group" href="full.webp" data-title="…" data-sub="…"> opens in the viewer.
   Works as a normal link (opens the image) when JavaScript is unavailable.
   ========================================================================== */
(function ($, window, document) {
  'use strict';

  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var $root = $('html'), $lb = null, items = [], idx = 0, lastFocus = null, zoomed = false, drag = null;

  var ICON = {
    close: '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>',
    prev: '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>',
    next: '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>',
    zin: '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3M11 8v6M8 11h6"/></svg>',
    zout: '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3M8 11h6"/></svg>'
  };

  function build() {
    if ($lb) { return; }
    $lb = $(
      '<div class="lb" hidden role="dialog" aria-modal="true" aria-label="Image viewer">' +
        '<div class="lb__bar"><span class="lb__count" aria-live="polite"></span>' +
          '<div class="lb__tools"><button type="button" class="lb__btn lb__zoom" aria-label="Zoom in">' + ICON.zin + '</button>' +
          '<button type="button" class="lb__btn lb__close" aria-label="Close viewer">' + ICON.close + '</button></div></div>' +
        '<button type="button" class="lb__nav lb__prev" aria-label="Previous image">' + ICON.prev + '</button>' +
        '<div class="lb__stage"><img class="lb__img" alt=""><span class="lb__spin" aria-hidden="true"></span></div>' +
        '<button type="button" class="lb__nav lb__next" aria-label="Next image">' + ICON.next + '</button>' +
        '<div class="lb__cap"><strong class="lb__title"></strong><span class="lb__sub"></span></div>' +
      '</div>'
    ).appendTo('body');

    $lb.on('click', '.lb__close', close);
    $lb.on('click', '.lb__prev', function () { go(-1); });
    $lb.on('click', '.lb__next', function () { go(1); });
    $lb.on('click', '.lb__zoom', function () { setZoom(!zoomed); });
    $lb.on('click', '.lb__stage', function (e) {
      if (drag && drag.moved) { return; }
      if ($(e.target).is('.lb__img')) { setZoom(!zoomed); }
      else if (!zoomed) { close(); }
    });

    // drag-to-pan when zoomed
    var stage = $lb.find('.lb__stage')[0];
    $(stage).on('pointerdown', function (e) {
      if (!zoomed) { return; }
      drag = { x: e.clientX, y: e.clientY, sl: stage.scrollLeft, st: stage.scrollTop, moved: false };
      stage.setPointerCapture && stage.setPointerCapture(e.originalEvent.pointerId);
      $lb.addClass('is-dragging');
    }).on('pointermove', function (e) {
      if (!drag) { return; }
      var dx = e.clientX - drag.x, dy = e.clientY - drag.y;
      if (Math.abs(dx) + Math.abs(dy) > 4) { drag.moved = true; }
      stage.scrollLeft = drag.sl - dx; stage.scrollTop = drag.st - dy;
    }).on('pointerup pointercancel', function () {
      $lb.removeClass('is-dragging');
      setTimeout(function () { drag = null; }, 0);
    });

    // swipe between images (when not zoomed)
    var sx = 0, sy = 0;
    $(stage).on('touchstart', function (e) { sx = e.originalEvent.touches[0].clientX; sy = e.originalEvent.touches[0].clientY; })
            .on('touchend', function (e) {
              if (zoomed) { return; }
              var dx = e.originalEvent.changedTouches[0].clientX - sx, dy = e.originalEvent.changedTouches[0].clientY - sy;
              if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) * 1.5) { go(dx < 0 ? 1 : -1); }
            });
  }

  function setZoom(on) {
    zoomed = on;
    $lb.toggleClass('is-zoomed', on);
    $lb.find('.lb__zoom').attr('aria-label', on ? 'Zoom out' : 'Zoom in').html(on ? ICON.zout : ICON.zin);
    var stage = $lb.find('.lb__stage')[0];
    if (on) {   // centre the zoomed image
      setTimeout(function () { stage.scrollLeft = (stage.scrollWidth - stage.clientWidth) / 2; stage.scrollTop = (stage.scrollHeight - stage.clientHeight) / 2; }, 0);
    } else { stage.scrollLeft = 0; stage.scrollTop = 0; }
  }

  function collect($a) {
    var group = $a.attr('data-lightbox');
    return $('a[data-lightbox="' + group + '"]').filter(function () { return !$(this).closest('.is-hidden, [hidden]').length; }).toArray();
  }

  function show(i) {
    idx = (i + items.length) % items.length;
    var a = items[idx], $a = $(a), $img = $lb.find('.lb__img');
    setZoom(false);
    $lb.addClass('is-loading');
    $img.off('load.lb error.lb').on('load.lb error.lb', function () { $lb.removeClass('is-loading'); });
    $img.attr({ src: a.getAttribute('href'), alt: $a.attr('data-alt') || $a.find('img').attr('alt') || '' });
    if ($img[0].complete && $img[0].naturalWidth) { $lb.removeClass('is-loading'); }
    $lb.find('.lb__title').text($a.attr('data-title') || '');
    $lb.find('.lb__sub').text($a.attr('data-sub') || '');
    $lb.find('.lb__count').text(items.length > 1 ? (idx + 1) + ' / ' + items.length : '');
    $lb.toggleClass('is-single', items.length < 2);
    [idx + 1, idx - 1].forEach(function (k) {       // preload neighbours
      var n = items[(k + items.length) % items.length]; if (n && n !== a) { new Image().src = n.getAttribute('href'); }
    });
  }

  function go(d) { if (items.length > 1) { show(idx + d); } }

  function open($a) {
    build();
    items = collect($a); idx = Math.max(0, items.indexOf($a[0]));
    lastFocus = document.activeElement;
    $lb.prop('hidden', false);
    $root.addClass('lb-open');
    show(idx);
    requestAnimationFrame(function () { $lb.addClass('is-open'); });
    $lb.find('.lb__close').trigger('focus');
  }

  function close() {
    if (!$lb || $lb.prop('hidden')) { return; }
    $lb.removeClass('is-open is-zoomed');
    var done = function () { $lb.prop('hidden', true); $lb.find('.lb__img').attr('src', ''); $root.removeClass('lb-open'); if (lastFocus && lastFocus.focus) { lastFocus.focus(); } };
    reduceMotion ? done() : setTimeout(done, 260);
  }

  $(document).on('click', 'a[data-lightbox]', function (e) {
    if (e.ctrlKey || e.metaKey || e.shiftKey || e.which > 1) { return; }
    e.preventDefault();
    open($(this));
  });

  // "View sheets" style links: open the first image of a group
  $(document).on('click', '[data-lightbox-trigger]', function (e) {
    e.preventDefault();
    var $first = $('a[data-lightbox="' + $(this).attr('data-lightbox-trigger') + '"]').first();
    if ($first.length) { open($first); }
  });

  $(document).on('keydown', function (e) {
    if (!$lb || $lb.prop('hidden')) { return; }
    if (e.key === 'Escape') { e.preventDefault(); close(); }
    else if (e.key === 'ArrowRight') { go(1); }
    else if (e.key === 'ArrowLeft') { go(-1); }
    else if (e.key === 'z' || e.key === 'Z') { setZoom(!zoomed); }
    else if (e.key === 'Tab') {                     // keep focus inside the dialog
      var f = $lb.find('button:visible').toArray(); if (!f.length) { return; }
      var first = f[0], last = f[f.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }
  });

  /* ---- Gallery filter chips ---------------------------------------------- */
  $('[data-gallery]').each(function () {
    var $g = $(this), $items = $g.find('[data-gcat]'), $count = $g.find('[data-gcount]');
    $g.on('click', '[data-gfilter]', function () {
      var k = $(this).attr('data-gfilter');
      $g.find('[data-gfilter]').removeClass('is-active').attr('aria-pressed', 'false'); $(this).addClass('is-active').attr('aria-pressed', 'true');
      var n = 0;
      $items.each(function () { var hide = k !== 'all' && $(this).attr('data-gcat') !== k; $(this).toggleClass('is-hidden', hide); if (!hide) { n++; } });
      $count.text(n);
    });
  });

  /* ---- Before/after "pair" switcher (swaps the images inside a .compare) ---- */
  $('[data-pairs]').each(function () {
    var $p = $(this), $c = $p.find('.compare');
    $p.on('click', '[data-pair]', function () {
      var $b = $(this);
      $p.find('[data-pair]').removeClass('is-active').attr('aria-pressed', 'false'); $b.addClass('is-active').attr('aria-pressed', 'true');
      $c.find('.compare__layer--a img').attr({ src: $b.attr('data-a'), alt: $b.attr('data-alt-a'), width: $b.attr('data-w'), height: $b.attr('data-h') });
      $c.find('.compare__layer--b img').attr({ src: $b.attr('data-b'), alt: $b.attr('data-alt-b'), width: $b.attr('data-w'), height: $b.attr('data-h') });
      $c[0].style.setProperty('--ratio', $b.attr('data-ratio'));
      $c[0].style.setProperty('--pos', '50%'); $c.find('.compare__range').val(50);
    });
  });

}(jQuery, window, document));

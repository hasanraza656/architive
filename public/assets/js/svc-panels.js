/* ==========================================================================
   Home services: expanding panels.
   Loaded by partials/service-panels.blade.php. Needs jQuery.
   ========================================================================== */
(function ($, window, document) {
  'use strict';

  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var hasIO = 'IntersectionObserver' in window;

  /* ---- Fader: cross-fades the .o-slide children of a container; autoplays only while visible and not held/paused ---- */
  function Fader($root, interval, hold, onChange) {
    var $s = $root.find('.o-slide'), n = $s.length, cur = Math.max(0, $s.index($s.filter('.is-active'))), timer = null, visible = !hasIO, held = false, api;
    function show(k) {
      cur = (k + n) % n;
      $s.removeClass('is-active').attr({ 'aria-hidden': 'true', tabindex: -1 }).eq(cur).addClass('is-active').removeAttr('aria-hidden').attr('tabindex', 0);
      if (onChange) { onChange(cur); }
    }
    function stop() { if (timer) { clearInterval(timer); timer = null; } }
    function play() { stop(); if (reduce || !visible || held || api.paused || n < 2) { return; } timer = setInterval(function () { show(cur + 1); }, interval); }
    api = {
      paused: false, count: n,
      go: function (k) { show(k); play(); },
      next: function () { show(cur + 1); play(); },
      prev: function () { show(cur - 1); play(); },
      index: function () { return cur; },
      refresh: play
    };
    if (hold) {
      $root.closest('article, .thx__pane').on('mouseenter focusin', function () { held = true; stop(); }).on('mouseleave focusout', function () { held = false; play(); });
    }
    if (hasIO) {
      new IntersectionObserver(function (en) { visible = en[0].isIntersecting; play(); }, { threshold: .25 }).observe($root[0]);
    }
    play();
    return api;
  }

  /* each panel fades through its own images; arrows wired here */

  var faders = new WeakMap();
  $('.opx [data-fader]').each(function () {
    var $r = $(this); faders.set(this, Fader($r, parseInt($r.attr('data-fader'), 10) || 4500, false));
    $r.closest('article').find('[data-prev]').on('click', function () { faders.get($r[0]).prev(); });
    $r.closest('article').find('[data-next]').on('click', function () { faders.get($r[0]).next(); });
  });

  /* ---- Expanding panels ---- */
  $('[data-opx]').each(function () {
    var $w = $(this), $p = $w.find('[data-p]'), n = $p.length, cur = 0, auto = null, user = false, visible = !hasIO;
    function activate(i) {
      cur = i;
      $p.each(function (k) {
        var on = k === i, f = faders.get($(this).find('[data-fader]')[0]);
        $(this).toggleClass('is-on', on).attr('aria-expanded', on ? 'true' : 'false');
        if (f) { f.paused = !on; f.refresh(); }
      });
    }
    function cycle() { clearInterval(auto); auto = null; if (reduce || user || !visible) { return; } auto = setInterval(function () { activate((cur + 1) % n); }, 8500); }
    $p.each(function (k) {
      var $el = $(this);
      var f = faders.get($el.find('[data-fader]')[0]); if (f) { f.paused = k !== 0; }
      $el.on('mouseenter', function () { if (window.matchMedia('(hover: hover)').matches && window.matchMedia('(min-width: 992px)').matches) { user = true; cycle(); if (cur !== k) { activate(k); } } });
      $el.on('click', function (e) { if (cur !== k && !$(e.target).closest('a, button').length) { user = true; cycle(); activate(k); e.preventDefault(); } else if (cur !== k) { user = true; cycle(); activate(k); e.preventDefault(); } });
      $el.on('click', '.o-slide', function (e) { if (cur !== k) { e.preventDefault(); e.stopImmediatePropagation(); } });
      $el.on('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { if ($(e.target).is($el)) { e.preventDefault(); user = true; cycle(); activate(k); } }
        else if (e.key === 'ArrowRight' || e.key === 'ArrowDown') { e.preventDefault(); user = true; cycle(); activate((k + 1) % n); $p.eq((k + 1) % n).trigger('focus'); }
        else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') { e.preventDefault(); user = true; cycle(); activate((k + n - 1) % n); $p.eq((k + n - 1) % n).trigger('focus'); }
      });
    });
    if (hasIO) { new IntersectionObserver(function (en) { visible = en[0].isIntersecting; cycle(); }, { threshold: .3 }).observe(this); }
    activate(0); cycle();
  });


}(jQuery, window, document));

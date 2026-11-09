/* Home collaborations carousel: arrows, drag with the mouse, native touch swipe, progress bar, counter. */
(function () {
    'use strict';
    document.querySelectorAll('[data-ccar]').forEach(function (root) {
        var track = root.querySelector('[data-ccar-track]');
        var prev = root.querySelector('[data-ccar-prev]');
        var next = root.querySelector('[data-ccar-next]');
        var fill = root.querySelector('[data-ccar-fill]');
        var count = root.querySelector('[data-ccar-count]');
        if (!track) return;
        var items = Array.prototype.slice.call(track.children);
        var total = items.length;

        function pad(n) { return (n < 10 ? '0' : '') + n; }
        function step() {
            return items.length > 1 ? items[1].offsetLeft - items[0].offsetLeft : track.clientWidth;
        }
        function update() {
            var max = track.scrollWidth - track.clientWidth;
            var x = track.scrollLeft;
            var atStart = x <= 2, atEnd = x >= max - 2;
            if (prev) prev.disabled = atStart;
            if (next) next.disabled = atEnd || max <= 2;
            root.classList.toggle('is-end', atEnd || max <= 2);
            if (fill) {
                var vis = Math.min(1, track.clientWidth / track.scrollWidth);
                fill.style.width = (vis * 100) + '%';
                fill.style.marginLeft = (max > 0 ? (x / max) * (1 - vis) * 100 : 0) + '%';
            }
            if (count) {
                var first = Math.min(total, Math.round(x / step()) + 1);
                count.textContent = pad(atEnd ? total : first) + ' / ' + pad(total);
            }
        }
        function go(dir) { track.scrollBy({ left: dir * step(), behavior: 'smooth' }); }

        if (prev) prev.addEventListener('click', function () { go(-1); });
        if (next) next.addEventListener('click', function () { go(1); });
        track.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowRight') { e.preventDefault(); go(1); }
            if (e.key === 'ArrowLeft') { e.preventDefault(); go(-1); }
        });
        var raf = 0;
        track.addEventListener('scroll', function () {
            if (raf) return;
            raf = requestAnimationFrame(function () { raf = 0; update(); });
        }, { passive: true });
        window.addEventListener('resize', update);

        // mouse drag (touch uses native scrolling)
        var down = false, sx = 0, sl = 0, moved = false;
        track.addEventListener('pointerdown', function (e) {
            if (e.pointerType !== 'mouse' || e.button !== 0) return;
            down = true; moved = false; sx = e.clientX; sl = track.scrollLeft;
        });
        window.addEventListener('pointermove', function (e) {
            if (!down) return;
            var dx = e.clientX - sx;
            if (!moved && Math.abs(dx) > 6) { moved = true; track.classList.add('is-drag'); }
            if (moved) track.scrollLeft = sl - dx;
        });
        function up() {
            if (!down) return;
            down = false;
            if (moved) {
                track.classList.remove('is-drag');
                var s = step();
                track.scrollTo({ left: Math.round(track.scrollLeft / s) * s, behavior: 'smooth' });
            }
        }
        window.addEventListener('pointerup', up);
        window.addEventListener('pointercancel', up);
        track.addEventListener('dragstart', function (e) { e.preventDefault(); });
        track.addEventListener('click', function (e) { if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; } }, true);

        update();
        window.addEventListener('load', update);
    });
})();

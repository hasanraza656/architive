{{-- Tawk.to live chat (replaces the old custom "Ask Architive" bubble). IDs/toggle: config/services.php -> TAWK_* in .env --}}
@php $tawk = config('services.tawk'); @endphp
@if (! empty($tawk['enabled']) && ! empty($tawk['property_id']) && ! empty($tawk['widget_id']))
    <script>
        /* Tawk.to embed. Loads once the page has finished loading (or on the visitor's first interaction),
           so the chat script can never delay rendering. */
        var Tawk_API = Tawk_API || {}, Tawk_LoadStart = new Date();
        Tawk_API.customStyle = { visibility: { desktop: { position: 'br', xOffset: 20, yOffset: 20 }, mobile: { position: 'br', xOffset: 12, yOffset: 12 } } };
        (function () {
            var started = false, evs = ['scroll', 'pointerdown', 'keydown', 'touchstart', 'mousemove'];
            function load() {
                if (started) { return; }
                started = true;
                evs.forEach(function (e) { window.removeEventListener(e, load); });
                var s1 = document.createElement('script'), s0 = document.getElementsByTagName('script')[0];
                s1.async = true;
                s1.src = 'https://embed.tawk.to/{{ $tawk['property_id'] }}/{{ $tawk['widget_id'] }}';
                s1.charset = 'UTF-8';
                s1.setAttribute('crossorigin', '*');
                s0.parentNode.insertBefore(s1, s0);
            }
            evs.forEach(function (e) { window.addEventListener(e, load, { passive: true }); });
            window.addEventListener('load', function () { setTimeout(load, 3500); });
        })();
    </script>
@endif

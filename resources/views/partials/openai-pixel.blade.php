{{-- OpenAI (ChatGPT Ads) measurement pixel. ID / switches: config/services.php -> OPENAI_PIXEL_ID, OPENAI_PIXEL_ENABLED, OPENAI_PIXEL_DEBUG in .env.
     Install it ONCE per page (here), never also through Google Tag Manager. It is initialised once (window.__oaiqInit guard).
     - page_viewed is sent on every page load (OpenAI's name for a page view).
     - lead_created is sent by window.architiveOaiLead(ref): on /thank-you/ (ad landing pages) and by the enquiry forms that stay on the page.
       It is sent once per request number (event_id + a stored list), so refreshing the thank-you page never counts twice.
       The server sends the same event (App\Services\Ads\OpenAiEvents) with the same id "lead_<request number>": OpenAI keeps the first one only
       (it matches on pixel id + event name + id).
     - whatsapp: custom event on every tap of a WhatsApp link (wa.me, api.whatsapp.com, whatsapp://). One per tap, same id in the browser event and
       in the server copy sent through /ads/whatsapp (navigator.sendBeacon, never delays the redirect), so OpenAI counts it once.
     - Consent: visitors in the EU / UK and anyone who declined the cookie banner start with consent = false (same rule as the Meta pixel);
       public/assets/js/consent.js switches it when they choose. window.architiveAdsConsent() answers the same question for other code
       (forms send it as ads_consent; the server sends nothing without it).
     - OpenAI saves the ad click reference (oppref) from the landing page URL in its own first-party cookie (__oppref); UTM parameters and the
       oppref are also kept 90 days in architive-src (attribution.js) and travel with the form. --}}
@php $oai = config('services.openai_pixel'); @endphp
@if (! empty($oai['enabled']) && ! empty($oai['id']))
<script>
!function(w,d,s,u){if(w.oaiq)return;var q=function(){q.q.push(arguments)};q.q=[];w.oaiq=q;var j=d.createElement(s);j.async=1;j.src=u;var f=d.getElementsByTagName(s)[0];f.parentNode.insertBefore(j,f);}(window,document,"script","https://bzrcdn.openai.com/sdk/oaiq.min.js");
(function () {
    if (window.__oaiqInit) { return; }
    window.__oaiqInit = true;

    var EU_TZ = /^(Europe\/(Amsterdam|Athens|Berlin|Bratislava|Brussels|Bucharest|Budapest|Busingen|Copenhagen|Dublin|Helsinki|Lisbon|Ljubljana|London|Belfast|Guernsey|Isle_of_Man|Jersey|Gibraltar|Luxembourg|Madrid|Malta|Mariehamn|Paris|Prague|Riga|Rome|Sofia|Stockholm|Tallinn|Vienna|Vilnius|Warsaw|Zagreb|Nicosia)|Asia\/(Nicosia|Famagusta)|Atlantic\/(Canary|Madeira|Azores))$/;
    var EU = 'AT BE BG HR CY CZ DK EE FI FR DE GR HU IE IT LV LT LU MT NL PL PT RO SK SI ES SE GB'.split(' ');
    // true = ad measurement allowed for this visitor right now
    window.architiveAdsConsent = function () {
        var ask = false, c = null;
        try {
            ask = EU_TZ.test(Intl.DateTimeFormat().resolvedOptions().timeZone || '');
            var langs = navigator.languages && navigator.languages.length ? navigator.languages : [navigator.language || ''];
            for (var i = 0; i < langs.length && !ask; i++) { var m = /[-_]([A-Za-z]{2})$/.exec(langs[i]); if (m && EU.indexOf(m[1].toUpperCase()) !== -1) { ask = true; } }
            c = JSON.parse(localStorage.getItem('architive-consent') || 'null');
        } catch (e) {}
        var chosen = c && typeof c.analytics === 'boolean';
        return chosen ? !!c.analytics : !ask;
    };
    function cookie(n) { var m = document.cookie.match(new RegExp('(?:^|; )' + n + '=([^;]*)')); return m ? decodeURIComponent(m[1]) : ''; }
    function savedOppref() {
        try { var s = JSON.parse(localStorage.getItem('architive-src') || 'null'); return (s && ((s.last && s.last.oppref) || (s.first && s.first.oppref))) || ''; } catch (e) { return ''; }
    }

    if (!window.architiveAdsConsent()) { oaiq('consent', false); }
    oaiq('init', { pixelId: @json($oai['id']) @if (! empty($oai['debug'])), debug: true @endif });
    oaiq('measure', 'page_viewed', { type: 'contents', contents: [{ id: location.pathname, name: document.title, content_type: 'page' }] });

    // WhatsApp taps (header, landing sticky bar, footer, thank-you page, contact page, mobile menu ...): custom event "whatsapp", separate from lead_created.
    // One event per tap (guard against double taps / bubbling). The pixel and the server copy share the id, so OpenAI keeps just one.
    var waLast = 0;
    document.addEventListener('click', function (e) {
        var l = e.target.closest && e.target.closest('a[href]');
        if (!l || !/(wa\.me\/\d|api\.whatsapp\.com\/send\?phone=|whatsapp:\/\/send\?phone=)/i.test(l.getAttribute('href') || '')) { return; }
        var now = Date.now();
        if (now - waLast < 1500) { return; }
        waLast = now;
        if (!window.architiveAdsConsent()) { return; }
        var id = 'wa_' + now.toString(36) + Math.random().toString(36).slice(2, 8);
        try { oaiq('measure', 'custom', { type: 'custom' }, { custom_event_name: 'whatsapp', event_id: id }); } catch (x) {}
        @if (! empty($oai['server_whatsapp']) && app(\App\Services\Ads\OpenAiEvents::class)->enabled())
        try {
            var body = JSON.stringify({ id: id, at: now, path: location.pathname, oppref: cookie('__oppref') || savedOppref(), obref: cookie('__obref') });
            if (!(navigator.sendBeacon && navigator.sendBeacon('{{ url('ads/whatsapp') }}', new Blob([body], { type: 'application/json' })))) {
                fetch('{{ url('ads/whatsapp') }}', { method: 'POST', body: body, headers: { 'Content-Type': 'application/json' }, keepalive: true, credentials: 'same-origin' });
            }
        } catch (x) {}
        @endif
    }, true);

    // Lead conversion, once per request number. ref = request number (falls back to one random id kept for the browser session).
    window.architiveOaiLead = function (ref) {
        try {
            var id = String(ref || '').replace(/[^A-Za-z0-9_-]/g, '').slice(0, 40), seen = [];
            if (!id) { id = sessionStorage.getItem('architive-oai-sid') || ('s' + Date.now().toString(36) + Math.random().toString(36).slice(2, 8)); sessionStorage.setItem('architive-oai-sid', id); }
            try { seen = JSON.parse(localStorage.getItem('architive-oai-leads') || '[]') || []; } catch (e) { seen = []; }
            if (seen.indexOf(id) !== -1) { return false; }
            seen.push(id); try { localStorage.setItem('architive-oai-leads', JSON.stringify(seen.slice(-30))); } catch (e) {}
            oaiq('measure', 'lead_created', { type: 'customer_action' }, { event_id: 'lead_' + id });
            return true;
        } catch (e) { return false; }
    };
})();
</script>
@endif

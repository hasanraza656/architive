{{-- OpenAI (ChatGPT Ads) measurement pixel. ID / switches: config/services.php -> OPENAI_PIXEL_ID, OPENAI_PIXEL_ENABLED, OPENAI_PIXEL_DEBUG in .env.
     Install it ONCE per page (here), never also through Google Tag Manager.
     - page_viewed is sent on every page load (OpenAI's name for a page view).
     - lead_created is sent by window.architiveOaiLead(ref): on /thank-you/ (ad landing pages) and by the enquiry forms that stay on the page.
       It is sent once per request number (event_id + a stored list), so refreshing the thank-you page never counts twice.
     - Consent: visitors in the EU / UK and anyone who declined the cookie banner start with consent = false (same rule as the Meta pixel);
       public/assets/js/consent.js switches it when they choose.
     - OpenAI saves the ad click reference (oppref) from the landing page URL in its own first-party cookie; UTM parameters stay in architive-src (attribution.js). --}}
@php $oai = config('services.openai_pixel'); @endphp
@if (! empty($oai['enabled']) && ! empty($oai['id']))
<script>
!function(w,d,s,u){if(w.oaiq)return;var q=function(){q.q.push(arguments)};q.q=[];w.oaiq=q;var j=d.createElement(s);j.async=1;j.src=u;var f=d.getElementsByTagName(s)[0];f.parentNode.insertBefore(j,f);}(window,document,"script","https://bzrcdn.openai.com/sdk/oaiq.min.js");
(function () {
    var ask = false, c = null;
    try {
        var tz = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
        ask = /^(Europe\/(Amsterdam|Athens|Berlin|Bratislava|Brussels|Bucharest|Budapest|Busingen|Copenhagen|Dublin|Helsinki|Lisbon|Ljubljana|London|Belfast|Guernsey|Isle_of_Man|Jersey|Gibraltar|Luxembourg|Madrid|Malta|Mariehamn|Paris|Prague|Riga|Rome|Sofia|Stockholm|Tallinn|Vienna|Vilnius|Warsaw|Zagreb|Nicosia)|Asia\/(Nicosia|Famagusta)|Atlantic\/(Canary|Madeira|Azores))$/.test(tz);
        var EU = 'AT BE BG HR CY CZ DK EE FI FR DE GR HU IE IT LV LT LU MT NL PL PT RO SK SI ES SE GB'.split(' ');
        var langs = navigator.languages && navigator.languages.length ? navigator.languages : [navigator.language || ''];
        for (var i = 0; i < langs.length && !ask; i++) { var m = /[-_]([A-Za-z]{2})$/.exec(langs[i]); if (m && EU.indexOf(m[1].toUpperCase()) !== -1) { ask = true; } }
        c = JSON.parse(localStorage.getItem('architive-consent') || 'null');
    } catch (e) {}
    var chosen = c && typeof c.analytics === 'boolean';
    if (chosen ? !c.analytics : ask) { oaiq('consent', false); }
    oaiq('init', { pixelId: @json($oai['id']) @if (! empty($oai['debug'])), debug: true @endif });
    oaiq('measure', 'page_viewed', { type: 'contents', contents: [{ id: location.pathname, name: document.title, content_type: 'page' }] });

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

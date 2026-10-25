{{-- Footer for ad landing pages: contact, legal links and cookie settings only. --}}
<footer class="lp-footer" role="contentinfo">
    <div class="wrap lp-footer__inner">
        <p class="mb-0">&copy; {{ date('Y') }} {{ config('site.legal_name') }} · Newark, Delaware, USA</p>
        <p class="mb-0 lp-footer__links">
            <a href="mailto:{{ config('site.email') }}">{{ config('site.email') }}</a>
            <a href="https://wa.me/{{ ltrim(config('site.phone'), '+') }}" target="_blank" rel="noopener">WhatsApp</a>
            <a href="{{ pu('privacy') }}">Privacy</a>
            <a href="{{ pu('terms') }}">Terms</a>
            <button type="button" class="footer-cookie" data-cookie-settings hidden>Cookie settings</button>
        </p>
    </div>
</footer>

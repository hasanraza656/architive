<footer class="site-footer" role="contentinfo">
    <div class="wrap">
        <div class="row g-5 footer-top">
            <div class="col-lg-4">
                <a href="{{ pu('home') }}" class="footer-logo" aria-label="Architive – home"><x-logo :light="true" /></a>
                <p class="footer-about">Architive is a dependable architectural production partner providing architectural visualization, BIM and Revit, and CAD drafting for architecture firms, interior designers, developers and homeowners worldwide.</p>
                <a class="footer-mail" href="mailto:{{ config('site.email') }}">{{ config('site.email') }}</a>
            </div>
            <div class="col-6 col-lg-2 offset-lg-1">
                <h2 class="footer-h">Quick links</h2>
                <ul class="footer-list">
                    @foreach (config('site.footer_links') as $link)
                        <li><a href="{{ pu($link['route']) }}">{{ $link['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h2 class="footer-h">Services</h2>
                <ul class="footer-list">
                    @foreach (config('site.nav')[2]['children'] as $child)
                        <li><a href="{{ pu($child['route']) }}">{{ $child['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div class="col-lg-3">
                <h2 class="footer-h">Contact us</h2>
                <div class="footer-addr">
                    @foreach (config('site.address') as $a)
                        <p><strong>{{ $a['label'] }}:</strong><br>{{ $a['value'] }}</p>
                    @endforeach
                </div>
                <h2 class="footer-h footer-h--spaced">Standards</h2>
                <ul class="footer-list footer-list--plain">
                    @foreach (config('site.standards') as $s)
                        <li>{{ $s }}</li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>© {{ date('Y') }} {{ config('site.legal_name') }}. All Rights Reserved. Founded {{ config('site.founded') }} by {{ config('site.founder') }}.</p>
            <nav class="footer-legal" aria-label="Legal">
                <a href="{{ pu('privacy') }}">Privacy</a>
                <a href="{{ pu('terms') }}">Terms</a>
                <a href="{{ pu('sitemap.html') }}">Sitemap</a>
                <a href="#top" class="footer-top-link" data-to-top>Back to top <x-icon name="arrow-up" /></a>
            </nav>
        </div>
    </div>
</footer>

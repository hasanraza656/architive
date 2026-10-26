{{-- Header for ad landing pages and the thank-you page: logo, phone and one call to action. No navigation menu. --}}
<header class="lp-header">
    <div class="wrap wrap--wide lp-header__inner">
        <a class="header-logo" href="{{ pu('home') }}" aria-label="Architive – home"><x-logo /></a>
        <div class="lp-header__actions">
            <a class="lp-header__phone" href="tel:{{ config('site.phone') }}"><x-icon name="message" /><span>{{ config('site.phone_display') }}</span></a>
            @unless (is_page('thanks'))
                <a class="btn-ay lp-header__cta" href="#trial">Start free trial</a>
            @endunless
        </div>
    </div>
</header>

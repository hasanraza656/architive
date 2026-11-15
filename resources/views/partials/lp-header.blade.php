{{-- Header for ad landing pages and the thank-you page: logo, phone and one call to action. No navigation menu. --}}
<header class="lp-header">
    <div class="wrap wrap--wide lp-header__inner">
        <a class="header-logo" href="{{ pu('home') }}" aria-label="Architive – home"><x-logo /></a>
        <div class="lp-header__actions">
            <a class="lp-header__phone" href="tel:{{ config('site.phone') }}"><x-icon name="message" /><span>{{ config('site.phone_display') }}</span></a>
            @unless (is_page('thanks'))
                <a class="btn-ay lp-header__cta" href="#trial">Start free trial</a>
            @endunless
            @php $waDigits = preg_replace('/\D+/', '', (string) config('site.phone')); @endphp
            @if ($waDigits)
                <a class="header-wa" href="https://wa.me/{{ $waDigits }}?text={{ rawurlencode('Hi Architive, I would like to talk about a project.') }}" target="_blank" rel="noopener noreferrer" data-no-modal aria-label="Chat with Architive on WhatsApp, {{ config('site.phone_display') }}" title="Chat on WhatsApp">
                    <svg viewBox="0 0 32 32" width="22" height="22" aria-hidden="true" focusable="false"><path fill="#fff" d="M16.003 3C9.376 3 4 8.373 4 15c0 2.116.553 4.18 1.6 6.003L4 29l8.17-1.57A12 12 0 0 0 16.003 27C22.63 27 28 21.627 28 15S22.63 3 16.003 3zm0 21.8a9.77 9.77 0 0 1-4.98-1.366l-.357-.212-3.7.71.74-3.6-.233-.37A9.78 9.78 0 0 1 6.2 15c0-5.413 4.39-9.8 9.803-9.8 5.41 0 9.797 4.387 9.797 9.8s-4.387 9.8-9.797 9.8zm5.37-7.34c-.294-.147-1.743-.86-2.013-.958-.27-.1-.466-.147-.663.147-.196.294-.76.958-.932 1.155-.172.196-.343.22-.637.074-.294-.147-1.243-.458-2.368-1.46-.875-.78-1.466-1.744-1.638-2.038-.172-.294-.018-.453.13-.6.132-.132.294-.343.44-.515.148-.172.197-.294.295-.49.098-.197.05-.37-.025-.516-.074-.147-.663-1.6-.908-2.19-.24-.575-.483-.497-.663-.506l-.565-.01c-.196 0-.515.074-.785.37-.27.294-1.03 1.006-1.03 2.454s1.055 2.847 1.202 3.043c.147.196 2.077 3.172 5.03 4.448.703.303 1.25.484 1.678.62.705.224 1.347.192 1.855.116.566-.084 1.743-.713 1.988-1.4.245-.688.245-1.278.172-1.4-.074-.123-.27-.197-.565-.344z"/></svg>
                </a>
            @endif
        </div>
    </div>
</header>

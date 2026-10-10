<header class="site-header" id="siteHeader">
    <div class="wrap wrap--wide header-inner">
        <a class="header-logo" href="{{ pu('home') }}" aria-label="Architive – home">
            <x-logo />
        </a>

        @php $waDigits = preg_replace('/\D+/', '', (string) config('site.phone')); @endphp
        <a class="header-phone d-none d-xl-inline-flex" href="tel:{{ config('site.phone') }}" aria-label="Call Architive on {{ config('site.phone_display') }}">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/></svg>
            <span>{{ config('site.phone_display') }}</span>
        </a>

        <nav class="main-nav d-none d-lg-block" aria-label="Primary">
            <ul class="main-nav__list">
                @foreach (config('site.nav') as $item)
                    @php
                        $isActive = $item['route'] === 'home'
                            ? is_page('home')
                            : (isset($item['children']) ? is_page('services.*') : is_page($item['route'], $item['route'] . '.*'));
                    @endphp
                    @if (! empty($item['children']))
                        <li class="main-nav__item has-mega {{ $isActive ? 'is-active' : '' }}">
                            <a class="main-nav__link" href="{{ pu($item['route']) }}" @if($isActive) aria-current="page" @endif>{{ $item['label'] }}</a>
                            <button class="mega-toggle" type="button" aria-expanded="false" aria-controls="mega-services" aria-label="Show services menu">
                                <x-icon name="chevron-down" />
                            </button>
                            <div class="mega" id="mega-services">
                                <div class="mega__grid">
                                    @foreach ($item['children'] as $child)
                                        <a class="mega__link" href="{{ pu($child['route']) }}">
                                            <span class="mega__icon"><x-icon :name="$child['icon']" /></span>
                                            <span>
                                                <strong>{{ $child['label'] }}</strong>
                                                <small>{{ $child['text'] }}</small>
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                                @if (! empty($item['more']))
                                    <div class="mega__more">
                                        @foreach ($item['more'] as $more)
                                            <a href="{{ pu($more['route']) }}">{{ $more['label'] }} <x-icon name="arrow-right" /></a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </li>
                    @else
                        <li class="main-nav__item {{ $isActive ? 'is-active' : '' }}">
                            <a class="main-nav__link" href="{{ pu($item['route']) }}" @if($isActive) aria-current="page" @endif>{{ $item['label'] }}</a>
                        </li>
                    @endif
                @endforeach
            </ul>
        </nav>

        <div class="header-actions">
            <a class="client-link d-none d-sm-grid" href="{{ route('customer.login') }}" aria-label="Client login" title="Client login"><x-icon name="user" /></a>
            <button class="theme-toggle" type="button" aria-label="Switch to dark mode" data-theme-toggle>
                <x-icon name="moon" class="ic theme-toggle__moon" />
                <x-icon name="sun" class="ic theme-toggle__sun" />
            </button>
            <a class="btn-ay btn-ay--sm d-none d-sm-inline-flex" href="{{ pu('contact') }}">Free consultation <x-icon name="arrow-right" /></a>
            @if ($waDigits)
                <a class="header-wa" href="https://wa.me/{{ $waDigits }}?text={{ rawurlencode('Hi Architive, I would like to talk about a project.') }}" target="_blank" rel="noopener noreferrer" data-no-modal
                   aria-label="Chat with Architive on WhatsApp, {{ config('site.phone_display') }}" title="Chat on WhatsApp">
                    <svg viewBox="0 0 32 32" width="22" height="22" aria-hidden="true" focusable="false"><path fill="#fff" d="M16.003 3C9.376 3 4 8.373 4 15c0 2.116.553 4.18 1.6 6.003L4 29l8.17-1.57A12 12 0 0 0 16.003 27C22.63 27 28 21.627 28 15S22.63 3 16.003 3zm0 21.8a9.77 9.77 0 0 1-4.98-1.366l-.357-.212-3.7.71.74-3.6-.233-.37A9.78 9.78 0 0 1 6.2 15c0-5.413 4.39-9.8 9.803-9.8 5.41 0 9.797 4.387 9.797 9.8s-4.387 9.8-9.797 9.8zm5.37-7.34c-.294-.147-1.743-.86-2.013-.958-.27-.1-.466-.147-.663.147-.196.294-.76.958-.932 1.155-.172.196-.343.22-.637.074-.294-.147-1.243-.458-2.368-1.46-.875-.78-1.466-1.744-1.638-2.038-.172-.294-.018-.453.13-.6.132-.132.294-.343.44-.515.148-.172.197-.294.295-.49.098-.197.05-.37-.025-.516-.074-.147-.663-1.6-.908-2.19-.24-.575-.483-.497-.663-.506l-.565-.01c-.196 0-.515.074-.785.37-.27.294-1.03 1.006-1.03 2.454s1.055 2.847 1.202 3.043c.147.196 2.077 3.172 5.03 4.448.703.303 1.25.484 1.678.62.705.224 1.347.192 1.855.116.566-.084 1.743-.713 1.988-1.4.245-.688.245-1.278.172-1.4-.074-.123-.27-.197-.565-.344z"/></svg>
                </a>
            @endif
            <button class="menu-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu" aria-label="Open menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</header>

<div class="offcanvas offcanvas-end mobile-menu" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
    <div class="mobile-menu__top">
        <span class="eyebrow" id="mobileMenuLabel">Menu</span>
        <button type="button" class="menu-close" data-bs-dismiss="offcanvas" aria-label="Close menu"><x-icon name="x" /></button>
    </div>
    <nav class="mobile-menu__nav" aria-label="Mobile">
        <ul>
            @foreach (config('site.nav') as $item)
                <li style="--i: {{ $loop->index }}">
                    <a href="{{ pu($item['route']) }}">{{ $item['label'] }}</a>
                    @if (! empty($item['children']))
                        <ul class="mobile-menu__sub">
                            @foreach ($item['children'] as $child)
                                <li><a href="{{ pu($child['route']) }}"><x-icon :name="$child['icon']" /> {{ $child['label'] }}</a></li>
                            @endforeach
                            @foreach ($item['more'] ?? [] as $more)
                                @if ($more['route'] !== 'services.index')
                                    <li><a href="{{ pu($more['route']) }}"><x-icon name="users" /> {{ $more['label'] }}</a></li>
                                @endif
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>
    <div class="mobile-menu__foot">
        <a class="btn-ay btn-ay--block" href="{{ pu('contact') }}">Start your project <x-icon name="arrow-right" /></a>
        <a class="mobile-menu__mail" href="tel:{{ config('site.phone') }}">{{ config('site.phone_display') }}</a>
        <a class="mobile-menu__mail" href="https://wa.me/{{ preg_replace('/\D+/', '', (string) config('site.phone')) }}" target="_blank" rel="noopener noreferrer">WhatsApp us</a>
        <a class="mobile-menu__mail" href="mailto:{{ config('site.email') }}">{{ config('site.email') }}</a>
        <a class="mobile-menu__mail" href="{{ route('customer.login') }}">Client login</a>
        <p class="mono-note">Free consultation and free start. No commitment until you approve the scope.</p>
    </div>
</div>

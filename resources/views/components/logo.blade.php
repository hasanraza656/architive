@props(['light' => false])
{{-- Client logo. $light = true -> always the light-lettered version (dark backgrounds such as the footer). --}}
<span {{ $attributes->merge(['class' => 'brand' . ($light ? ' brand--light' : '')]) }}>
    <img class="brand__img brand__img--dark" src="{{ asset('assets/img/logo.png') }}" width="580" height="100" alt="Architive: visualization and design studio" decoding="async">
    <img class="brand__img brand__img--light" src="{{ asset('assets/img/logo-light.png') }}" width="580" height="100" alt="" aria-hidden="true" loading="lazy" decoding="async">
</span>

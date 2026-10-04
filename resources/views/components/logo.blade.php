@props(['light' => false])
<span {{ $attributes->merge(['class' => 'brand' . ($light ? ' brand--light' : '')]) }}>
    <svg class="brand__mark" viewBox="0 0 64 64" width="34" height="34" aria-hidden="true" focusable="false">
        <rect width="64" height="64" rx="15" class="brand__tile"/>
        <path class="brand__a" d="M14 50 L32 12 L50 50" fill="none" stroke-width="5" stroke-linejoin="round" stroke-linecap="round"/>
        <path d="M22 38 H42" stroke="#FFD60A" stroke-width="5" stroke-linecap="round"/>
    </svg>
    <span class="brand__word">ARCHITIVE<small>Production Studio</small></span>
</span>

{{-- One carousel slide for the services design options. Props: $img (Portfolio::reel entry), $group (lightbox group), $first (bool), optional $sizes.
     The blurred copy of the picture fills the frame, so nothing is cropped; drawing sheets sit on white. --}}
<a class="o-slide {{ ! empty($img['sheet']) ? 'is-sheet' : '' }} {{ ! empty($first) ? 'is-active' : '' }}" href="{{ $img['src'] }}"
   data-lightbox="{{ $group }}" data-title="{{ $img['title'] ?? '' }}" data-alt="{{ $img['alt'] }}" style="--bg: url('{{ $img['thumb'] }}')"
   @if (empty($first)) tabindex="-1" aria-hidden="true" @endif>
    <img src="{{ $img['thumb'] }}" srcset="{{ $img['thumb'] }} {{ $img['tw'] }}w, {{ $img['src'] }} {{ $img['w'] }}w" sizes="{{ $sizes ?? '(min-width: 992px) 45vw, 92vw' }}"
         width="{{ $img['tw'] }}" height="{{ $img['th'] }}" alt="{{ $img['alt'] }}" loading="{{ ($eager ?? ! empty($first)) ? 'eager' : 'lazy' }}" decoding="async">
</a>

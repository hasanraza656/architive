{{-- Continuous right-to-left logo marquee. $logos from Content::brandLogos(). Duplicated once for a seamless loop. --}}
<div class="logo-marquee">
    <div class="wrap">
        <p class="logo-marquee__caption">Brands represented in projects supported through our collaborators</p>
        <div class="logo-marquee__viewport">
            <ul class="logo-marquee__track">
                @foreach (array_merge($logos, $logos) as $i => $l)
                    <li class="logo-marquee__item" @if ($i >= count($logos)) aria-hidden="true" @endif>
                        <img src="{{ asset_v('assets/logos/' . $l['file']) }}" alt="{{ $i >= count($logos) ? '' : $l['alt'] }}" loading="lazy" decoding="async">
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>

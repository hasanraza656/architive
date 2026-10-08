{{--
  Inner-page hero. Props: $eyebrow, $title (HTML ok), $lead, $cta (label), $ctaUrl, $image, $alt,
  optional: $secondary [label,url], $chips [..], $visual (view name for an animated SVG), $imgPos,
  $showcase (category key 'visualization'|'bim'|'cad' -> real-work carousel background instead of a single $image)
--}}
<section class="page-hero" data-parallax-root>
    <div class="page-hero__bg" aria-hidden="true">
        @if (! empty($showcase))
            @php $showImages = \App\Support\Content::coreServices()[$showcase]['gallery']; @endphp
            <div class="page-hero__carousel" data-slider>
                @foreach ($showImages as $img)
                    <picture class="slider__slide {{ ! empty($img['sheet']) ? 'slider__slide--sheet' : '' }} {{ $loop->first ? 'is-active' : '' }}">
                        <source type="image/webp" srcset="{{ $img['thumb'] }} {{ $img['tw'] }}w, {{ $img['src'] }} {{ $img['w'] }}w" sizes="100vw">
                        <img src="{{ $img['src'] }}" alt="" width="{{ $img['w'] }}" height="{{ $img['h'] }}" decoding="async" data-parallax="0.12" @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif>
                    </picture>
                @endforeach
            </div>
        @elseif (! empty($image))
            <picture>
                <source type="image/webp" srcset="{{ asset($image . '-800.webp') }} 800w, {{ asset($image . '.webp') }} 1600w" sizes="100vw">
                <img src="{{ asset($image . '.webp') }}" alt="" width="1600" height="1066" fetchpriority="high" decoding="async" data-parallax="0.12" style="object-position: {{ $imgPos ?? 'center' }}">
            </picture>
        @endif
        <span class="page-hero__shade"></span>
        <svg class="page-hero__grid" width="100%" height="100%" aria-hidden="true"><defs><pattern id="hg" width="48" height="48" patternUnits="userSpaceOnUse"><path d="M48 0H0V48" fill="none" stroke="currentColor" stroke-width=".6"/></pattern></defs><rect width="100%" height="100%" fill="url(#hg)"/></svg>
    </div>

    <div class="wrap wrap--wide page-hero__inner">
        @include('partials.breadcrumbs')
        <div class="row align-items-end g-4">
            <div class="col-lg-8 col-xl-7">
                <p class="eyebrow eyebrow--light" data-reveal>{{ $eyebrow }}</p>
                <h1 class="page-hero__title" data-split>{!! $title !!}</h1>
                <p class="page-hero__lead" data-reveal style="--d:.2s">{{ $lead }}</p>
                @if (! empty($cta))
                    <div class="page-hero__actions" data-reveal style="--d:.3s">
                        <a class="btn-ay" href="{{ $ctaUrl }}" data-magnetic>{{ $cta }} <x-icon name="arrow-right" /></a>
                        @if (! empty($secondary))
                            <a class="link-arrow link-arrow--light" href="{{ $secondary[1] }}">{{ $secondary[0] }} <x-icon name="arrow-right" /></a>
                        @endif
                    </div>
                    <p class="mono-note mono-note--light" data-reveal style="--d:.4s">Free consultation and free start. No commitment until you approve the scope.</p>
                @endif
            </div>
            @if (! empty($chips))
                <div class="col-lg-4 col-xl-5 d-none d-lg-block">
                    <ul class="hero-chips" data-reveal style="--d:.35s">
                        @foreach ($chips as $i => $chip)
                            <li><span>0{{ $i + 1 }}</span>{{ $chip }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</section>

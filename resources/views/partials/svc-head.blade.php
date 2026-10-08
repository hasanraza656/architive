{{-- Compact heading for the Services pages: breadcrumb, H1, a short description and the form button. No full-screen hero.
     Props: $eyebrow, $title (HTML ok), $lead, optional $items [..] (short deliverables), $cta, $ctaUrl, $topic, $note,
            $image ['key' => Portfolio image key, 'alt' => .., 'sheet' => bool] -> shows a good image on the left of the text --}}
@php $img = ! empty($image) ? \App\Support\Portfolio::img($image['key']) : null; @endphp
<section class="svc-head {{ $img ? 'svc-head--img' : '' }}">
    <div class="wrap">
        @include('partials.breadcrumbs')
        <div class="row g-4 g-lg-5 align-items-center">
            @if ($img)
                <div class="col-lg-5" data-reveal="left">
                    <figure class="svc-head__img {{ ! empty($image['sheet']) ? 'is-sheet' : '' }}">
                        <img src="{{ $img['thumb'] }}" srcset="{{ $img['thumb'] }} {{ $img['tw'] }}w, {{ $img['src'] }} {{ $img['w'] }}w" sizes="(min-width: 992px) 40vw, 92vw"
                             width="{{ $img['tw'] }}" height="{{ $img['th'] }}" alt="{{ $image['alt'] }}" fetchpriority="high" decoding="async">
                    </figure>
                </div>
            @endif
            <div class="{{ $img ? 'col-lg-7' : 'col-lg-8' }}">
                <p class="eyebrow" data-reveal>{{ $eyebrow }}</p>
                <h1 class="svc-head__title" data-split>{!! $title !!}</h1>
                <p class="svc-head__lead" data-reveal style="--d:.15s">{{ $lead }}</p>
                @if (! empty($items))
                    <ul class="svc-head__items" data-reveal style="--d:.2s">
                        @foreach ($items as $it)<li>{{ $it }}</li>@endforeach
                    </ul>
                @endif
                @if ($img && ! empty($cta))
                    <div class="svc-head__cta" data-reveal style="--d:.25s">
                        <a class="btn-ay btn-ay--lg" href="{{ $ctaUrl }}" @if (! empty($topic)) data-topic="{{ $topic }}" @endif data-magnetic>{{ $cta }} <i class="btn-ay__dot"></i></a>
                        <p class="mono-note">Free consultation and free start. No commitment until you approve the scope.</p>
                    </div>
                @endif
            </div>
            @if (! $img && ! empty($cta))
                <div class="col-lg-4 svc-head__side" data-reveal style="--d:.25s">
                    <a class="btn-ay btn-ay--lg" href="{{ $ctaUrl }}" @if (! empty($topic)) data-topic="{{ $topic }}" @endif data-magnetic>{{ $cta }} <i class="btn-ay__dot"></i></a>
                    <p class="mono-note">Free consultation and free start. No commitment until you approve the scope.</p>
                </div>
            @endif
        </div>
        @if (! empty($note))
            <p class="svc-head__note" data-reveal>{{ $note }}</p>
        @endif
    </div>
</section>

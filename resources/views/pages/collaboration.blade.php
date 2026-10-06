@extends('layouts.app', ['overlay' => true])

@section('content')
@php
    $keys = array_keys(\App\Support\Content::collaborations());
    $i = array_search($slug, $keys);
    $all = \App\Support\Content::collaborations();
    $prev = $all[$keys[($i + count($keys) - 1) % count($keys)]];
    $next = $all[$keys[($i + 1) % count($keys)]];
    $prevSlug = $keys[($i + count($keys) - 1) % count($keys)];
    $nextSlug = $keys[($i + 1) % count($keys)];
    $media = \App\Support\Portfolio::caseMedia($slug);
@endphp

<section class="page-hero page-hero--plain">
    <div class="page-hero__bg" aria-hidden="true">
        <span class="page-hero__shade"></span>
        <svg class="page-hero__grid" width="100%" height="100%" aria-hidden="true"><defs><pattern id="hg" width="48" height="48" patternUnits="userSpaceOnUse"><path d="M48 0H0V48" fill="none" stroke="currentColor" stroke-width=".6"/></pattern></defs><rect width="100%" height="100%" fill="url(#hg)"/></svg>
    </div>
    <div class="wrap wrap--wide page-hero__inner">
        @include('partials.breadcrumbs')
        <div class="row g-5 align-items-center">
            <div class="col-lg-7">
                <p class="eyebrow eyebrow--light" data-reveal>{{ $c['tag'] }} · {{ $c['place'] }}</p>
                <h1 class="page-hero__title" data-split>{{ $c['title'] }}</h1>
                <p class="page-hero__lead" data-reveal style="--d:.2s">{{ $c['situation'] }}</p>
            </div>
            <div class="col-lg-5" data-reveal="zoom">
                @if ($media)
                    <a class="case-cover case-cover--hero" href="{{ $media['cover']['src'] }}" data-lightbox="case-cover" data-title="{{ $media['caption'] }}" data-sub="{{ $c['title'] }}" data-alt="{{ $media['alt'] }}">
                        <img src="{{ $media['cover']['thumb'] }}" srcset="{{ $media['cover']['thumb'] }} {{ $media['cover']['tw'] }}w, {{ $media['cover']['src'] }} {{ $media['cover']['w'] }}w" sizes="(min-width: 992px) 40vw, 100vw"
                             width="{{ $media['cover']['w'] }}" height="{{ $media['cover']['h'] }}" alt="{{ $media['alt'] }}" fetchpriority="high" decoding="async">
                        <span class="case-cover__tag">{{ $media['caption'] }}</span>
                    </a>
                @else
                    <div class="collab-card collab-card--hero">
                        <div class="collab-card__art">
                            <div class="collab-card__row"><span>Project {{ $c['num'] }}</span><b class="tag-{{ $c['key'] }}">{{ $c['tag'] }}</b></div>
                            @include('partials.blueprint-card', ['kind' => $c['key']])
                            <div class="collab-card__row"><span>Illustrative line drawing</span><span>Scale 1:100</span></div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

<section class="section" aria-labelledby="collab-detail">
    <div class="wrap">
        <h2 class="visually-hidden" id="collab-detail">Collaboration details</h2>
        <div class="row g-4">
            <div class="col-md-6" data-reveal><div class="info-card"><span class="info-card__k"><x-icon name="building" /> Client</span><p>{{ $c['client'] }}</p></div></div>
            <div class="col-md-6" data-reveal style="--d:.08s"><div class="info-card"><span class="info-card__k"><x-icon name="target" /> Situation</span><p>{{ $c['situation'] }}</p></div></div>
            <div class="col-md-6" data-reveal style="--d:.16s"><div class="info-card"><span class="info-card__k"><x-icon name="users" /> Our role</span><p>{{ $c['role'] }}</p></div></div>
            <div class="col-md-6" data-reveal style="--d:.24s">
                <div class="info-card"><span class="info-card__k"><x-icon name="file-text" /> Deliverables</span>
                    <ul class="check-list">@foreach ($c['deliverables'] as $d)<li><x-icon name="check" /> {{ $d }}</li>@endforeach</ul>
                </div>
            </div>
        </div>

        @if ($c['note'])
            <div class="callout callout--wide mt-4" data-reveal><x-icon name="info" /><span><b>Scope of our role:</b> {{ $c['note'] }}</span></div>
        @endif

        <div class="related mt-5" data-reveal>
            <p class="mono-note mb-2">Related service</p>
            <a class="link-arrow" href="{{ pu($c['service_route']) }}">{{ $c['service_label'] }} <x-icon name="arrow-right" /></a>
        </div>
    </div>
</section>

@if ($media)
    {{-- PROJECT VISUALS --}}
    <section class="section section--alt" aria-labelledby="case-visuals">
        <div class="wrap">
            <p class="eyebrow" data-reveal>Project visuals</p>
            <h2 class="display-h" id="case-visuals" data-split>The Work, <em>in Detail</em></h2>

            @if (! empty($media['video']))
                <div class="row g-4 align-items-center mt-1">
                    <div class="col-lg-7" data-reveal="zoom">
                        <figure class="video-card">
                            <video controls preload="none" playsinline poster="{{ $media['video']['poster'] }}" width="1280" height="720" aria-label="{{ $media['video']['title'] }}">
                                <source src="{{ $media['video']['src'] }}" type="video/mp4">
                                Your browser can't play this video. <a href="{{ $media['video']['src'] }}">Download the MP4</a>.
                            </video>
                            <figcaption><span>{{ $media['video']['title'] }}</span><span>23 sec · Revit 3D views</span></figcaption>
                        </figure>
                    </div>
                    <div class="col-lg-5">
                        <p class="lead-p" data-reveal>A short walkthrough of the architectural Revit model: exterior views, wall and roof build-ups and building sections, developed and updated in line with the agreed drawings, model requirements and review comments.</p>
                        <p class="mono-note" data-reveal>The video loads only when you press play.</p>
                    </div>
                </div>
            @else
                <div class="case-board" data-reveal="zoom">
                    <a class="case-cover" href="{{ $media['cover']['src'] }}" data-lightbox="case-board" data-title="{{ $media['caption'] }}" data-sub="{{ $c['title'] }}" data-alt="{{ $media['alt'] }}">
                        <img src="{{ $media['cover']['src'] }}" width="{{ $media['cover']['w'] }}" height="{{ $media['cover']['h'] }}" alt="{{ $media['alt'] }}" loading="lazy" decoding="async">
                        <span class="case-cover__tag">{{ $media['caption'] }} · click to enlarge</span>
                    </a>
                </div>
            @endif

            @if (! empty($media['sheets']))
                <h3 class="display-h display-h--sm mt-5" data-reveal>Documentation <em>sheets</em></h3>
                <ul class="work-grid mt-3" data-reveal>
                    @foreach ($media['sheets'] as $sh)
                        <li class="work-item">
                            <a href="{{ $sh['src'] }}" data-lightbox="case-sheets" data-title="{{ $sh['no'] }} · {{ $sh['title'] }}" data-sub="{{ $c['title'] }}" data-alt="{{ $sh['alt'] }}">
                                <img src="{{ $sh['thumb'] }}" width="{{ $sh['tw'] }}" height="{{ $sh['th'] }}" alt="{{ $sh['alt'] }}" loading="lazy" decoding="async">
                                <span class="work-item__cap"><span><small>Sheet {{ $sh['no'] }}</small><strong>{{ $sh['title'] }}</strong></span><i><x-icon name="search" /></i></span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <p class="note-line">Sheets are shown without title blocks (owner, address and client branding removed). Click any sheet to zoom.</p>
            @endif
        </div>
    </section>
@endif

<section class="section section--alt pager-sec" aria-label="More collaborations">
    <div class="wrap">
        <div class="pager">
            <a href="{{ pu('collaborations.show', ['slug' => $prevSlug]) }}" class="pager__a"><span class="pager__k"><x-icon name="arrow-left" /> Previous</span><strong>{{ $prev['name'] }}</strong></a>
            <a href="{{ pu('collaborations.index') }}" class="pager__all" aria-label="All collaborations"><x-icon name="layers" /></a>
            <a href="{{ pu('collaborations.show', ['slug' => $nextSlug]) }}" class="pager__a pager__a--r"><span class="pager__k">Next <x-icon name="arrow-right" /></span><strong>{{ $next['name'] }}</strong></a>
        </div>
    </div>
</section>

@include('partials.cta-band', ['title' => 'Discuss a <em>Similar Project.</em>', 'cta' => 'Discuss a similar project', 'ctaUrl' => pu('contact', [], ['service' => ['visualization' => 'visualization', 'cad' => 'cad', 'bim' => 'bim'][$c['key']]])])
@endsection

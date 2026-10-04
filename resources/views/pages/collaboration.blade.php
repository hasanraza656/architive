@extends('layouts.app')

@section('content')
@php
    $keys = array_keys(\App\Support\Content::collaborations());
    $i = array_search($slug, $keys);
    $all = \App\Support\Content::collaborations();
    $prev = $all[$keys[($i + count($keys) - 1) % count($keys)]];
    $next = $all[$keys[($i + 1) % count($keys)]];
    $prevSlug = $keys[($i + count($keys) - 1) % count($keys)];
    $nextSlug = $keys[($i + 1) % count($keys)];
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
                <div class="collab-card collab-card--hero">
                    <div class="collab-card__art">
                        <div class="collab-card__row"><span>Project {{ $c['num'] }}</span><b class="tag-{{ $c['key'] }}">{{ $c['tag'] }}</b></div>
                        @include('partials.blueprint-card', ['kind' => $c['key']])
                        <div class="collab-card__row"><span>Illustrative line drawing</span><span>Scale 1:100</span></div>
                    </div>
                </div>
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

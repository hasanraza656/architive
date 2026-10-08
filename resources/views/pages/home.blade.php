@extends('layouts.app', ['overlay' => true])

@push('preload')
    <link rel="preload" as="image" href="{{ asset('assets/img/hero.webp') }}" type="image/webp" fetchpriority="high" imagesrcset="{{ asset('assets/img/hero-800.webp') }} 800w, {{ asset('assets/img/hero.webp') }} 1279w" imagesizes="100vw">
@endpush

@section('content')
@php
    use App\Support\Content;
    $core = Content::coreServices();
    $track = config('site.track_record');
@endphp

{{-- ===================== HERO (the header floats on top of it) ===================== --}}
<section class="hero hero--home" aria-label="Introduction">
    <div class="hero__card" data-hero>
        <div class="hero__media" data-parallax-wrap>
            <picture>
                <source type="image/webp" srcset="{{ asset('assets/img/hero-800.webp') }} 800w, {{ asset('assets/img/hero.webp') }} 1279w" sizes="100vw">
                <img src="{{ asset('assets/img/hero.webp') }}" width="1279" height="720" fetchpriority="high" decoding="async" data-parallax="0.08"
                     alt="Curved timber and steel garden pavilion at sunset surrounded by trees, an architectural visualization by Architive">
            </picture>
        </div>
        <div class="hero__shade" aria-hidden="true"></div>

        <div class="hero__content">
            <h1 class="hero__kicker">Architectural Production Studio</h1>
            <h2 class="hero__title">
                <span class="line"><span>One brief.</span></span>
                <span class="line line--serif"><span><em>One team.</em></span></span>
            </h2>
            <p class="hero__lead">Coordinated team for Architectural Visualization, BIM and Revit, and CAD Drafting.</p>
            <div class="hero__actions">
                <a class="btn-ay btn-ay--lg" href="{{ pu('contact') }}" data-magnetic>Start your project <i class="btn-ay__dot"></i></a>
                <a class="link-arrow link-arrow--light" href="#services">See our services <x-icon name="arrow-down" /></a>
            </div>
            <p class="hero__note">No overhead, no hiring, no delays.</p>
        </div>
    </div>
</section>

{{-- ===================== SOFTWARE TICKER ===================== --}}
<div class="home-ticker">
    <div class="ticker" aria-label="Software ecosystem">
        <div class="wrap ticker__wrap">
            <span class="ticker__label">Software ecosystem:</span>
            <div class="ticker__viewport">
                <ul class="ticker__track">
                    @foreach (array_merge(config('site.software'), config('site.software')) as $s)
                        <li @if($loop->index >= count(config('site.software'))) aria-hidden="true" @endif><i></i>{{ $s }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- Production support banner sits right after the software ticker --}}
<div class="support-band">
    <div class="wrap wrap--wide">
        @include('partials.support-strip')
    </div>
</div>

{{-- ===================== TRACK RECORD (Fiverr + Upwork) ===================== --}}
@include('partials.track-band', ['track' => $track])

{{-- ===================== THE THREE CORE SERVICES: big visuals, one under another ===================== --}}
<section class="section" id="services" aria-labelledby="services-title">
    <div class="wrap wrap--wide">
        <div class="section-head">
            <div>
                <p class="eyebrow eyebrow--lg" data-reveal>Services</p>
                <h2 class="display-h display-h--md mb-0" id="services-title" data-split>One Brief. One Team. <em>Every Deliverable Connected.</em></h2>
            </div>
        </div>
    </div>
    <div class="svc-show-list">
        @foreach ($core as $key => $s)
            @include('partials.service-show', ['s' => $s, 'key' => $key, 'n' => $loop->iteration])
        @endforeach
    </div>
</section>

{{-- ===================== COLLABORATIONS ===================== --}}
<section class="section section--alt" aria-labelledby="collab-title">
    <div class="wrap">
        <div class="section-head">
            <div>
                <p class="eyebrow" data-reveal>Collaborations</p>
                <h2 class="display-h mb-2" id="collab-title" data-split>Proof in the Work<em>not in the Promises.</em></h2>
                <p class="lead-p mb-0" data-reveal style="--d:.15s">See what the client needed, the responsibility Architive handled and how the deliverables supported the wider project.</p>
            </div>
            <a class="btn-ay" href="{{ pu('collaborations.index') }}" data-reveal>View collaborations <x-icon name="arrow-right" /></a>
        </div>
        <div class="row g-4">
            @foreach (Content::collaborations() as $slug => $c)
                <div class="col-md-6 col-lg-4" data-reveal style="--d: {{ $loop->index * .1 }}s">
                    @include('partials.collab-card', ['slug' => $slug, 'c' => $c])
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ===================== WHO WE ARE (short) ===================== --}}
<section class="section" id="who-we-are" aria-labelledby="who-title">
    <div class="wrap wrap--narrow text-center">
        <p class="eyebrow eyebrow--center" data-reveal>Who we are</p>
        <h2 class="display-h" id="who-title" data-split>Who We Are <em>and What We Do.</em></h2>
        <p class="lead-p" data-reveal style="--d:.1s">I started Architive in {{ config('site.founded') }} as a freelance architectural engineer on Fiverr and Upwork. What began with helping clients turn ideas into drawings and 3D visuals grew through repeat projects and long-term working relationships into a multidisciplinary architectural production studio.</p>
        <p class="lead-p" data-reveal style="--d:.18s">Today we provide architectural visualization, BIM and Revit, and CAD drafting under one roof. You lead the design and the client relationship; we turn approved information into accurate drawings, structured models and presentation-ready visuals, in your formats and to your standards.</p>
        <p class="who__sig who__sig--center" data-reveal style="--d:.26s"><span class="signature__name">{{ config('site.founder') }}</span><span class="signature__role">{{ config('site.founder_role') }}</span><a class="link-arrow" href="{{ pu('about') }}">Read our story <x-icon name="arrow-right" /></a></p>
    </div>
</section>

{{-- ===================== WHERE WE WORK (map) ===================== --}}
@include('partials.world-map')

{{-- ===================== CLIENT REVIEWS ===================== --}}
<section class="section section--dark reviews-sec" aria-labelledby="reviews-title">
    <div class="process__glow" aria-hidden="true"></div>
    <div class="wrap wrap--wide">
        <div class="text-center mb-5">
            <p class="eyebrow eyebrow--center" data-reveal>Client reviews</p>
            <h2 class="display-h" id="reviews-title" data-split>What Clients <em>Say About Working With Us.</em></h2>
        </div>
        @include('partials.testimonials', ['items' => Content::testimonials()])
    </div>
</section>

{{-- ===================== BRAND / PARTNER LOGO MARQUEE ===================== --}}
@include('partials.logo-marquee', ['logos' => Content::brandLogos()])

{{-- ===================== FAQ ===================== --}}
<section class="section section--alt" aria-labelledby="faq-title">
    <div class="wrap wrap--narrow">
        <div class="text-center mb-5">
            <p class="eyebrow eyebrow--center" data-reveal>Frequently asked questions</p>
            <h2 class="display-h" id="faq-title" data-split>Questions Buyers Ask <em>Before Starting.</em></h2>
            <p class="lead-p mx-auto" data-reveal>Clear answers regarding office standards, free starts, architectural design boundaries, permit packages, pricing and revisions.</p>
        </div>
        @include('partials.faq-toolbar')
        @include('partials.faq-list', ['items' => Content::faqs(), 'uid' => 'homefaq'])
        <p class="text-center mt-4" data-reveal><a class="link-arrow" href="{{ pu('faqs') }}">See all FAQs <x-icon name="arrow-right" /></a></p>
    </div>
</section>

@endsection

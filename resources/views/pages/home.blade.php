@extends('layouts.app')

@push('preload')
    <link rel="preload" as="image" href="{{ asset('assets/img/hero.webp') }}" type="image/webp" fetchpriority="high" imagesrcset="{{ asset('assets/img/hero-800.webp') }} 800w, {{ asset('assets/img/hero.webp') }} 1279w" imagesizes="(max-width: 900px) 100vw, 1330px">
@endpush

@section('content')
@php
    use App\Support\Content;
    $services = Content::services();
@endphp

{{-- ===================== HERO ===================== --}}
<section class="hero" aria-label="Introduction">
    <div class="hero__card" data-hero>
        <div class="hero__media" data-parallax-wrap>
            <picture>
                <source type="image/webp" srcset="{{ asset('assets/img/hero-800.webp') }} 800w, {{ asset('assets/img/hero.webp') }} 1279w" sizes="(max-width: 900px) 100vw, 1330px">
                <img src="{{ asset('assets/img/hero.webp') }}" width="1279" height="720" fetchpriority="high" decoding="async" data-parallax="0.08"
                     alt="Curved timber and steel garden pavilion at sunset surrounded by trees, an architectural visualization by Architive">
            </picture>
        </div>
        <div class="hero__shade" aria-hidden="true"></div>
        <div class="hero__frame" aria-hidden="true"></div>

        <svg class="hero__arc" viewBox="0 0 1000 560" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false" data-tilt-layer>
            <g fill="none" stroke="#fff" stroke-linecap="round">
                <path class="draw" d="M360 330 A 240 240 0 0 1 840 330" stroke-opacity=".55" stroke-width="1"/>
                <path class="draw" d="M390 330 A 210 210 0 0 1 810 330" stroke-opacity=".35" stroke-width="1" stroke-dasharray="4 6"/>
                <path class="draw" d="M600 70 V470" stroke-opacity=".35" stroke-dasharray="3 6"/>
                <path class="draw" d="M440 110 V470M760 110 V470" stroke-opacity=".22" stroke-dasharray="2 7"/>
                <path class="draw" d="M592 96h16M592 130h16" stroke-opacity=".6"/>
            </g>
            <g class="hero__arc-tags" font-family="JetBrains Mono, monospace" font-size="9" fill="#fff" fill-opacity=".7" letter-spacing="1.5">
                <text x="612" y="94">R 4.20 M</text><text x="446" y="124">AXIS A</text><text x="766" y="124">AXIS C</text>
            </g>
        </svg>

        <p class="hero__top">Architectural visualization <i>•</i> BIM and Revit <i>•</i> CAD drafting</p>

        <div class="hero__content">
            <p class="hero__meta"><span>Est. 2017</span><i>·</i><span>Architive LLC Delaware</span><i>·</i><span>Global delivery</span></p>
            <h1 class="hero__title">
                <span class="line"><span>More project capacity.</span></span>
                <span class="line line--sm"><span>Without another full-time hire.</span></span>
            </h1>
            <p class="hero__lead">One coordinated team for CAD drafting, BIM and Revit, and architectural visualization—ready when your project needs more production capacity.</p>
            <p class="hero__lead2 d-none d-lg-block">You lead the design and client relationship. We turn the approved information into accurate drawings, structured models and presentation-ready visuals, delivered in your formats and built around your standards.</p>
            <div class="hero__actions">
                <a class="btn-ay" href="{{ pu('contact') }}" data-magnetic>Start your project <i class="btn-ay__dot"></i></a>
                <a class="link-arrow link-arrow--light" href="{{ pu('process') }}">See how it works <x-icon name="arrow-right" /></a>
            </div>
            <p class="hero__note">Start with a free consultation or a small paid pilot. No commitment until you approve the scope.</p>
            <ul class="hero__chips">
                <li><b>01</b> 3D Visualization</li>
                <li><b>02</b> BIM &amp; Revit</li>
                <li><b>03</b> CAD Drafting</li>
                <li class="plain">One brief · One team</li>
            </ul>
        </div>

        <a class="proof-card" href="{{ pu('collaborations.index') }}" aria-label="See our collaborations">
            <span class="proof-card__arrow"><x-icon name="arrow-up-right" /></span>
            <strong class="proof-card__num" data-count="1500" data-suffix="+" data-delay="1300">1,500+</strong>
            <span class="proof-card__text">Projects delivered across visualization, BIM &amp; Revit, and CAD drafting</span>
            <span class="proof-card__foot"><b><i></i> Proof before promises</b><em>1,000+ reviews</em></span>
        </a>
    </div>
</section>

{{-- ===================== PROOF STATS + SOFTWARE ===================== --}}
<section class="proof" aria-labelledby="proof-title">
    <div class="wrap">
        <h2 class="visually-hidden" id="proof-title">Proof Before Promises</h2>
        <div class="row g-4 g-lg-5 stats">
            <div class="col-6 col-lg-3" data-reveal><div class="stat"><strong class="stat__num"><span data-count="2017" data-from="1990">2017</span><i>.</i></strong><span class="stat__label">Established in 2017</span><span class="stat__sub">Multidisciplinary production team</span></div></div>
            <div class="col-6 col-lg-3" data-reveal style="--d:.1s"><div class="stat"><strong class="stat__num"><span data-count="1500" data-suffix="+">1,500+</span><i>.</i></strong><span class="stat__label">Projects delivered</span><span class="stat__sub">CAD, BIM &amp; visualization worldwide</span></div></div>
            <div class="col-6 col-lg-3" data-reveal style="--d:.2s"><div class="stat"><strong class="stat__num"><span data-count="1000" data-suffix="+">1,000+</span><i>.</i></strong><span class="stat__label">Client reviews</span><span class="stat__sub">Verified global client feedback</span></div></div>
            <div class="col-6 col-lg-3" data-reveal style="--d:.3s"><div class="stat"><strong class="stat__num stat__num--word">Delaware<i>.</i></strong><span class="stat__label">Architive LLC</span><span class="stat__sub">Registered US company</span></div></div>
        </div>
    </div>
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
</section>

{{-- ===================== PROBLEM + OUTCOME (interactive diagram) ===================== --}}
<section class="section" aria-labelledby="problem-title">
    <div class="wrap">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <p class="eyebrow" data-reveal>Why Architive</p>
                <h2 class="display-h" id="problem-title" data-split>Production Support Should Remove Pressure—<em>not Add Another Person to Manage.</em></h2>
                <p class="lead-p" data-reveal style="--d:.15s">When CAD, BIM and visualization are split across unrelated freelancers, your team becomes the coordinator. The same design is explained repeatedly, standards drift and every handover creates another review cycle.</p>
                <p class="lead-p" data-reveal style="--d:.25s">Architive brings those disciplines together under one brief and one accountable point of contact. You keep control of the design; we keep the production moving.</p>
            </div>
            <div class="col-lg-6" data-reveal="zoom">
                @include('partials.unify-diagram')
            </div>
        </div>
    </div>
</section>

{{-- ===================== SERVICES ===================== --}}
<section class="section section--alt" id="services" aria-labelledby="services-title">
    <div class="wrap">
        <div class="section-head">
            <div>
                <p class="eyebrow" data-reveal>Services</p>
                <h2 class="display-h mb-0" id="services-title" data-split>Brief One Team. <em>Keep Every Deliverable Connected.</em></h2>
            </div>
            <a class="btn-ay" href="{{ pu('services.index') }}" data-reveal>View all services <x-icon name="arrow-right" /></a>
        </div>

        <div class="row g-4 svc-grid">
            @foreach ($services as $key => $s)
                <div class="col-md-6 col-xl-3" data-reveal style="--d: {{ $loop->index * .1 }}s">
                    <a class="svc-card" href="{{ pu($s['route']) }}" data-spotlight>
                        <span class="svc-card__icon"><x-icon :name="$s['icon']" /></span>
                        <h3 class="svc-card__title">{{ $s['title'] }}</h3>
                        <p class="svc-card__text">{{ $s['short'] }}</p>
                        <ul class="svc-card__list">
                            @foreach ($s['items'] as $it)<li>{{ $it }}</li>@endforeach
                        </ul>
                        <span class="svc-card__foot">View specification <x-icon name="arrow-right" /></span>
                    </a>
                </div>
            @endforeach
        </div>
        <p class="mono-note text-center mt-5" data-reveal>{{ $services['cad']['note'] }}</p>
    </div>
</section>

{{-- ===================== ENGAGEMENT MODEL ===================== --}}
<section class="section" aria-labelledby="engage-title">
    <div class="wrap">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5">
                <p class="eyebrow" data-reveal>Engagement model</p>
                <h2 class="display-h" id="engage-title" data-split>A Flexible Extension <em>of Your Studio.</em></h2>
                <p class="lead-p" data-reveal style="--d:.15s">Bring us one defined assignment, begin with a paid pilot or use Architive as recurring production support. Scale the engagement around your pipeline while keeping your own templates, standards and approvals.</p>
                <a class="link-arrow mt-3" href="{{ pu('services.outsourcing') }}" data-reveal style="--d:.25s">Explore production support <x-icon name="arrow-right" /></a>
            </div>
            <div class="col-lg-7">
                <div class="row g-3 pillars">
                    @foreach ([['users', 'One point of contact', 'A named person who knows your brief, your standards and your deadline.'], ['layers', 'Your templates and standards', 'Title blocks, layers, Revit structure and naming—applied from day one.'], ['refresh', 'Agreed review stages', 'Checkpoints are fixed in the quotation, so feedback arrives at the right moment.'], ['zap', 'Flexible project-based or ongoing support', 'From a single task to recurring capacity as your pipeline changes.']] as [$ic, $t, $d])
                        <div class="col-sm-6" data-reveal style="--d: {{ $loop->index * .1 }}s">
                            <div class="pillar"><span class="pillar__icon"><x-icon :name="$ic" /></span><h3>{{ $t }}</h3><p>{{ $d }}</p></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ===================== AUDIENCE ===================== --}}
<section class="section section--alt" aria-labelledby="aud-title">
    <div class="wrap">
        <p class="eyebrow" data-reveal>Audience</p>
        <h2 class="display-h" id="aud-title" data-split>Who We <em>Support.</em></h2>
        <div class="row g-4 mt-2">
            <div class="col-lg-6" data-reveal>
                <article class="aud-card">
                    <header><span class="aud-card__icon"><x-icon name="building" /></span><span class="tag">For firms &amp; practices</span></header>
                    <h3>For Architecture &amp; Design Practices</h3>
                    <p>You lead the design and client relationship. We turn approved design information into accurate drawings, structured models and presentation-ready visuals in your formats.</p>
                    <ul class="aud-list">
                        <li><strong>Architecture Firms</strong><span>→ CAD drafting, Revit production and visualization capacity for live projects, deadlines and workload peaks.</span></li>
                        <li><strong>Interior Design Studios</strong><span>→ Floor plans, elevations, working drawings and client-ready visuals that carry an approved concept.</span></li>
                        <li><strong>Developers and Contractors</strong><span>→ Coordinated drawings, models and presentation visuals for approvals, procurement and marketing.</span></li>
                    </ul>
                    <a class="btn-ay btn-ay--block" href="{{ pu('contact', [], ['audience' => 'firm']) }}">Start your project (firm) <x-icon name="arrow-right" /></a>
                </article>
            </div>
            <div class="col-lg-6" data-reveal style="--d:.12s">
                <article class="aud-card">
                    <header><span class="aud-card__icon"><x-icon name="home" /></span><span class="tag">For residences &amp; renovations</span></header>
                    <h3>For Homeowners &amp; Private Clients</h3>
                    <p>Drawings and realistic visuals for renovations, additions and new homes, so key decisions are clearer before construction begins.</p>
                    <ul class="aud-list">
                        <li><strong>Renovations &amp; Additions</strong><span>→ Visuals and drawings to compare options, check layouts and talk to contractors with confidence.</span></li>
                        <li><strong>New-Home Visualization</strong><span>→ Photorealistic exterior and interior renderings to understand the design before it is built.</span></li>
                        <li><strong>Permit-Support Drawings</strong><span>→ Where local regulations require drawings to be signed or sealed, we prepare the package for review.</span></li>
                    </ul>
                    <p class="callout callout--sm"><x-icon name="info" /><span><b>Note:</b> Where local law requires drawings to be signed or sealed, we prepare the package for review by your locally licensed professional.</span></p>
                    <a class="btn-ay btn-ay--block" href="{{ pu('contact', [], ['audience' => 'homeowner']) }}">Start your project (homeowner) <x-icon name="arrow-right" /></a>
                </article>
            </div>
        </div>
    </div>
</section>

{{-- ===================== FOUNDER ===================== --}}
<section class="section" aria-labelledby="founder-title">
    <div class="wrap">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6" data-reveal="left">
                <div class="studio-card" data-tilt>
                    <div class="studio-card__top"><span>Studio discipline // Revit &amp; CAD</span><b>Multan · Delaware</b></div>
                    @include('partials.studio-drawing')
                    <div class="studio-badge" data-float>
                        <strong>12<sup>+</sup><i></i></strong>
                        <span>Team specialists</span>
                        <em>Engineers &amp; modelers</em>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <p class="eyebrow" data-reveal>About us</p>
                <h2 class="display-h" id="founder-title" data-split>From Freelance Practice to <em>Architectural Production Studio.</em></h2>
                <p class="lead-p" data-reveal style="--d:.1s">I started Architive in 2017 as a freelance architectural engineer on Fiverr and Upwork. What began with me helping clients turn ideas into drawings and 3D visuals grew through repeat projects and long-term working relationships.</p>
                <p class="lead-p" data-reveal style="--d:.18s">Today, Architive is a strong multidisciplinary studio providing architectural visualization, BIM and Revit, and CAD drafting under one roof. The team has grown, but the way we work remains personal: understand the brief, communicate clearly and make the client's next step easier.</p>
                <div class="signature" data-reveal style="--d:.26s">
                    <div><span class="signature__name">Madiha Altaf</span><span class="signature__role">Madiha Altaf · Founder and Architectural Engineer</span></div>
                    <a class="link-arrow" href="{{ pu('about') }}">Read our story <x-icon name="arrow-right" /></a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ===================== COLLABORATIONS ===================== --}}
<section class="section section--alt" aria-labelledby="collab-title">
    <div class="wrap">
        <div class="section-head">
            <div>
                <p class="eyebrow" data-reveal>Collaborations</p>
                <h2 class="display-h mb-2" id="collab-title" data-split>Proof in the Work—<em>not in the Promises.</em></h2>
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

{{-- ===================== PROCESS (dark) ===================== --}}
<section class="section section--dark process" aria-labelledby="process-title">
    <div class="process__glow" aria-hidden="true"></div>
    <div class="wrap">
        <div class="row g-4 align-items-end mb-5">
            <div class="col-lg-7">
                <p class="eyebrow eyebrow--light" data-reveal>Process</p>
                <h2 class="display-h display-h--light mb-0" id="process-title" data-split>Brief Once. Review Clearly. <em>Receive Work Ready for the Next Stage.</em></h2>
            </div>
            <div class="col-lg-5">
                <p class="lead-p mb-0" data-reveal>We define what is being produced, which standards apply, who approves it and when it is due—before production begins.</p>
            </div>
        </div>
        @include('partials.process-steps', ['steps' => Content::process()])
        <div class="mt-5" data-reveal><a class="link-arrow link-arrow--light" href="{{ pu('process') }}">See how it works <x-icon name="arrow-right" /></a></div>
    </div>
</section>

{{-- ===================== TESTIMONIAL / COMMITMENTS ===================== --}}
<section class="section quotes-sec" aria-labelledby="quote-title">
    <div class="wrap wrap--narrow text-center">
        <p class="eyebrow eyebrow--center" id="quote-title" data-reveal>Client feedback</p>
        @include('partials.quote-slider', ['slides' => Content::slides()])
    </div>
</section>

{{-- ===================== FAQ ===================== --}}
<section class="section section--alt" aria-labelledby="faq-title">
    <div class="wrap wrap--narrow">
        <div class="text-center mb-5">
            <p class="eyebrow eyebrow--center" data-reveal>Frequently asked questions</p>
            <h2 class="display-h" id="faq-title" data-split>Questions Buyers Ask <em>Before Starting.</em></h2>
            <p class="lead-p mx-auto" data-reveal>Clear answers regarding office standards, small trial pilots, architectural design boundaries, permit packages, pricing and revisions.</p>
        </div>
        @include('partials.faq-toolbar')
        @include('partials.faq-list', ['items' => Content::faqs(), 'uid' => 'homefaq'])
        <p class="text-center mt-4" data-reveal><a class="link-arrow" href="{{ pu('faqs') }}">See all FAQs <x-icon name="arrow-right" /></a></p>
    </div>
</section>

@include('partials.cta-band')
@endsection

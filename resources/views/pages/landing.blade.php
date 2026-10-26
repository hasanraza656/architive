@extends('layouts.app')
{{-- Google Ads landing page. Props: $slug, $p (config/landing.php pages.*), $trial (config/landing.php trial). --}}
@php
    use App\Support\Content;
    use App\Support\Portfolio;
    $img = Portfolio::img($p['image']['key']);
@endphp

@push('head')
    <link rel="stylesheet" href="{{ asset_v('assets/css/landing.css') }}">
    <link rel="preload" as="image" href="{{ $img['thumb'] }}" imagesrcset="{{ $img['thumb'] }} {{ $img['tw'] }}w, {{ $img['src'] }} {{ $img['w'] }}w" imagesizes="(min-width: 992px) 50vw, 100vw">
@endpush

@section('content')
<section class="lp-hero">
    <div class="wrap wrap--wide">
        <div class="lp-hero__grid">
            <div class="lp-hero__copy">
                <p class="eyebrow">{{ $p['eyebrow'] }}</p>
                <h1 class="display-h lp-hero__h1">{!! $p['h1'] !!}</h1>
                <p class="lead-p">{{ $p['lead'] }}</p>
                <ul class="check-list lp-hero__list">@foreach ($p['bullets'] as $b)<li><x-icon name="check" /> {{ $b }}</li>@endforeach</ul>
            </div>
            <div class="lp-hero__form">
                @include('partials.lp-form')
            </div>
            <div class="lp-hero__visual">
                <figure class="lp-hero__media">
                    <img src="{{ $img['thumb'] }}" srcset="{{ $img['thumb'] }} {{ $img['tw'] }}w, {{ $img['src'] }} {{ $img['w'] }}w" sizes="(min-width: 992px) 50vw, 100vw"
                         width="{{ $img['w'] }}" height="{{ $img['h'] }}" alt="{{ $p['image']['alt'] }}" fetchpriority="high" decoding="async">
                </figure>
                <p class="lp-hero__trust"><x-icon name="shield" /> Architive LLC, a US company registered in Delaware, with its own production studio.</p>
            </div>
        </div>
    </div>
</section>

<section class="section section--alt" aria-labelledby="lp-work">
    <div class="wrap">
        <div class="section-head">
            <div>
                <p class="eyebrow">Our work</p>
                <h2 class="display-h display-h--md mb-0" id="lp-work">{!! $p['galleryTitle'] ?? 'Drawing Sets <em>We Have Drafted</em>' !!}</h2>
            </div>
            <p class="lead-p mb-0" style="max-width: 44ch">Real projects from our studio. Select any image to enlarge it.</p>
        </div>
        @if (! empty($p['sets']))
            <div class="set-grid">
                @foreach (Portfolio::sets($p['sets']) as $set)
                    @include('partials.set-card', ['set' => $set])
                @endforeach
            </div>
            <p class="note-line mt-3">Title blocks and owner details are removed to protect client privacy. Where drawings must be signed or sealed, your locally licensed professional reviews and stamps them.</p>
        @else
            @include('partials.work-gallery', ['only' => $p['gallery'], 'withNew' => true])
        @endif
    </div>
</section>

<section class="section" aria-labelledby="lp-trial">
    <div class="wrap">
        <div class="text-center mb-5">
            <p class="eyebrow eyebrow--center">{{ $trial['title'] }}</p>
            <h2 class="display-h" id="lp-trial">See Our Work on <em>Your Project First</em></h2>
            <p class="lead-p mx-auto" style="max-width: 62ch">{{ $trial['text'] }}</p>
        </div>
        <div class="row g-4">
            @foreach ($trial['steps'] as $s)
                <div class="col-md-4">
                    <article class="feature-card h-100">
                        <span class="feature-card__ic"><x-icon :name="$s['icon']" /></span>
                        <p class="lp-step-n">{{ $s['n'] }}</p>
                        <h3 class="display-h display-h--sm">{{ $s['title'] }}</h3>
                        <p class="mb-0">{{ $s['text'] }}</p>
                    </article>
                </div>
            @endforeach
        </div>
        <p class="text-center mt-4"><a class="btn-ay btn-ay--lg" href="#trial">Start my free trial <x-icon name="arrow-right" /></a></p>
    </div>
</section>

<section class="section section--alt" aria-labelledby="lp-reviews">
    <div class="wrap">
        <div class="text-center mb-4">
            <p class="eyebrow eyebrow--center">Client reviews</p>
            <h2 class="display-h" id="lp-reviews">What Repeat Clients <em>Say</em></h2>
        </div>
        @include('partials.testimonials', ['items' => Content::testimonials()])
    </div>
</section>

<section class="section" aria-labelledby="lp-why">
    <div class="wrap">
        <div class="text-center mb-5">
            <p class="eyebrow eyebrow--center">Why teams stay</p>
            <h2 class="display-h" id="lp-why">Extra Capacity Without <em>Extra Hires</em></h2>
        </div>
        <div class="row g-4">
            @foreach ([
                ['clock', 'Work while you sleep', 'Our studio works while US offices are closed. Send comments at the end of your day and find progress in the morning.'],
                ['ruler', 'Your standards, not ours', 'We follow your templates, title blocks, finishes and references, so the work looks like it came from your office.'],
                ['receipt', 'No surprise invoices', 'A written quote lists every deliverable, review stage, revision round and date. If we miss something we agreed, we fix it at no charge.'],
                ['lock', 'Confidential by default', 'We sign an NDA before you share detailed files, and only the team on your project can open them.'],
            ] as [$ic, $t, $x])
                <div class="col-md-6 col-lg-3">
                    <article class="feature-card h-100">
                        <span class="feature-card__ic"><x-icon :name="$ic" /></span>
                        <h3 class="display-h display-h--sm">{{ $t }}</h3>
                        <p class="mb-0">{{ $x }}</p>
                    </article>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="section section--alt" aria-labelledby="lp-faq">
    <div class="wrap wrap--narrow">
        <p class="eyebrow eyebrow--center">Questions</p>
        <h2 class="display-h text-center mb-5" id="lp-faq">Before You <em>Start</em></h2>
        @include('partials.faq-list', ['items' => $p['faqs'], 'uid' => 'lpfaq'])
    </div>
</section>

<section class="lp-final section--dark">
    <div class="wrap text-center">
        <h2 class="display-h display-h--light">Try Us on a <em>Real Task</em>, Free</h2>
        <p class="lead-p mx-auto" style="max-width: 52ch">Up to 3 working days of real work on your project. You decide after you have seen it.</p>
        <a class="btn-ay btn-ay--lg" href="#trial">Start my free trial <x-icon name="arrow-right" /></a>
    </div>
</section>

<div class="lp-sticky" data-lp-sticky>
    <a class="btn-ay btn-ay--block" href="#trial">Start free trial</a>
    <a class="lp-sticky__wa" href="https://wa.me/{{ ltrim(config('site.phone'), '+') }}" target="_blank" rel="noopener" aria-label="Chat with Architive on WhatsApp">WhatsApp</a>
</div>
@endsection

@push('scripts')
<script>
    /* Sticky mobile bar: hidden while the trial form itself is on screen */
    (function () {
        var bar = document.querySelector('[data-lp-sticky]'), form = document.getElementById('trial');
        if (!bar || !form || !('IntersectionObserver' in window)) { return; }
        new IntersectionObserver(function (en) { bar.classList.toggle('is-hidden', en[0].isIntersecting); }).observe(form);
        document.querySelectorAll('a[href="#trial"]').forEach(function (a) {
            a.addEventListener('click', function () { setTimeout(function () { var i = document.getElementById('lp-name'); if (i) { i.focus({ preventScroll: true }); } }, 450); });
        });
    })();
</script>
@endpush

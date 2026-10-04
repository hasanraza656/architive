@extends('layouts.app', ['seo' => ['title' => 'Page Not Found | Architive', 'description' => 'The page you are looking for could not be found. Explore Architive services, collaborations or contact us.', 'robots' => 'noindex,follow']])

@section('content')
<section class="notfound">
    <div class="wrap text-center">
        <svg class="notfound__art" viewBox="0 0 400 200" aria-hidden="true" focusable="false">
            <line class="draw" x1="10" y1="170" x2="390" y2="170"/>
            <path class="draw" d="M60 170V70h70v100M130 70l-35-30-35 30"/>
            <path class="draw draw--accent" d="M170 170V110h60v60M230 170h60V60h50v110"/>
            <circle class="nf-sun" cx="330" cy="36" r="14"/>
            <path class="draw" d="M120 170v-26h20v26"/>
        </svg>
        <p class="eyebrow eyebrow--center">Error 404</p>
        <h1 class="display-h">This Page Is <em>Off the Drawing.</em></h1>
        <p class="lead-p mx-auto">The address may have changed or the page may no longer exist. Head back to a page that does.</p>
        <div class="notfound__links">
            <a class="btn-ay" href="{{ pu('home') }}">Back to home <x-icon name="arrow-right" /></a>
            <a class="btn-ay btn-ay--ghost" href="{{ pu('services.index') }}">Our services</a>
            <a class="btn-ay btn-ay--ghost" href="{{ pu('contact') }}">Contact</a>
        </div>
    </div>
</section>
@endsection

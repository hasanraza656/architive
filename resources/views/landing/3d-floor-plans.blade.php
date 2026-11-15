@extends('layouts.landing', ['lp' => true, 'seo' => [
    'title'       => '3D Floor Plan Rendering for Listings and Developers | Free 3-Day Trial',
    'description' => 'Turn 2D plans, PDFs or scans into furnished 3D floor plans buyers understand in seconds. One plan or hundreds in one style. Try us free for 3 days.',
    'robots'      => 'noindex,follow',
]])
{{-- Google Ads landing page: /lp/3d-floor-plans/. All text of this page is in this file. The shared sections (proof, free-trial steps, reviews, why-us, final call, sticky bar) live in layouts/landing.blade.php. --}}
@php
    $slug        = '3d-floor-plans';
    $service     = 'visualization';          // sent with the form
    $topic       = '3D Floor Plan';
    $formTitle   = 'Start your free trial';
    $placeholder = 'For example: 3D floor plans for 4 apartment types from the attached PDFs, top-down view, light oak floors, for a sales brochure.';
    $image       = ['key' => 'viz/cabin-floorplan-iso', 'alt' => 'Isometric 3D floor plan of a furnished timber vacation cabin with decks and a hot tub, by Architive'];
    $faqs        = [
        1 => ['q' => 'What does the free 3-day trial include?', 'a' => 'One real task, worked on free for up to 3 working days. For floor plans that is usually your first plan, taken to a finished draft in the style you want, so you can check accuracy and style before ordering more.'],
        2 => ['q' => 'Can you work from a PDF, scan or hand sketch?', 'a' => 'Yes, if the walls are clear and the key dimensions are readable. Clean DWG files are fastest. Mark anything unusual, such as steps, half-height walls or sloped ceilings.'],
        3 => ['q' => 'How much does a 3D floor plan cost after the trial?', 'a' => 'It depends on the size and complexity of the plan, the furniture and detail, and how many plans you need. Ordering many in one agreed style lowers the cost per plan. You get a written quote before any paid work.'],
        4 => ['q' => 'Can all my plans look the same?', 'a' => 'Yes. We agree colours, furniture, labels and image size on the first plan and every plan after it follows the same style sheet.'],
        5 => ['q' => 'What files will I get?', 'a' => 'High-resolution JPG or PNG files sized for your listing, brochure or print. Tell us where they will be used and we will size them for it.'],
        6 => ['q' => 'Who is Architive?', 'a' => 'Architive LLC is a US company registered in Delaware with its own production studio. We have produced more than 500 3D floor plans for a UK client alone, all in one consistent style.'],
    ];
@endphp

@section('eyebrow')
3D floor plans for agents, developers and designers
@endsection

@section('h1')
Floor Plans Buyers Understand <em>in Seconds.</em>
@endsection

@section('lead')
We turn your 2D plans, PDFs or scans into furnished 3D floor plans, top-down or isometric, in one consistent style whether you need one or one hundred. Try us free first.
@endsection

@section('bullets')
    <li><x-icon name="check" /> From DWG, PDF, scan or sketch</li>
    <li><x-icon name="check" /> Top-down and isometric views</li>
    <li><x-icon name="check" /> One style across every plan</li>
    <li><x-icon name="check" /> More than 500 plans made</li>
@endsection

@section('work')
<section class="section section--alt" aria-labelledby="lp-work">
    <div class="wrap">
        <div class="section-head">
            <div>
                <p class="eyebrow">Our work</p>
                <h2 class="display-h display-h--md mb-0" id="lp-work">3D Floor Plans <em>We Have Made</em></h2>
            </div>
            <p class="lead-p mb-0" style="max-width: 44ch">Real projects from our studio. Select any image to enlarge it.</p>
        </div>
        @include('partials.work-gallery', ['only' => ['floorplans'], 'withNew' => true])
    </div>
</section>
@endsection

@section('price')
<section class="section" aria-labelledby="lp-price">
    <div class="wrap">
        <div class="row g-4 align-items-stretch">
            <div class="col-lg-7">
                <article class="feature-card h-100">
                    <span class="feature-card__ic"><x-icon name="receipt" /></span>
                    <p class="eyebrow">Pricing</p>
                    <h2 class="display-h display-h--sm" id="lp-price">How a floor plan is <em>priced</em></h2>
                    <p>3D floor plans are quoted per plan. The main factors are the size and number of rooms, how much furniture is needed, the view types and whether we start from CAD or redraw from a scan.</p>
                    <p class="mb-0">Several plans in the same style cost less each, because the furniture and materials are set up once. You get one written quote listing every plan, view, revision round and delivery date, and the free trial comes before any paid work.</p>
                </article>
            </div>
            <div class="col-lg-5">
                <article class="feature-card h-100 lp-talk">
                    <span class="feature-card__ic"><x-icon name="message" /></span>
                    <p class="eyebrow">Prefer to talk first?</p>
                    <h2 class="display-h display-h--sm">Ask us <em>anything</em></h2>
                    <p>Call or message us and we reply the same working day. No form needed.</p>
                    <p class="lp-talk__links mb-0">
                        <a class="btn-ay btn-ay--ghost" href="tel:{{ config('site.phone') }}">Call {{ config('site.phone_display') }}</a>
                        <a class="btn-ay btn-ay--ghost" href="https://wa.me/{{ ltrim(config('site.phone'), '+') }}" target="_blank" rel="noopener">WhatsApp us</a>
                    </p>
                </article>
            </div>
        </div>
    </div>
</section>
@endsection

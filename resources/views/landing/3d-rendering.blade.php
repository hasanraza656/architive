@extends('layouts.landing', ['lp' => true, 'seo' => [
    'title'       => '3D Rendering Services for US Architects and Designers | Free 3-Day Trial',
    'description' => 'Photoreal interior and exterior renderings from your CAD, Revit or SketchUp files. Try us free for 3 days on a real task. US company, overnight turnaround.',
    'robots'      => 'noindex,follow',
]])
{{-- Google Ads landing page: /lp/3d-rendering/. All text of this page is in this file. The shared sections (proof, free-trial steps, reviews, why-us, final call, sticky bar) live in layouts/landing.blade.php. --}}
@php
    $slug        = '3d-rendering';
    $service     = 'visualization';          // sent with the form
    $topic       = '3D Rendering';
    $formTitle   = 'Start your free trial';
    $placeholder = 'For example: two interior views of a kitchen and living room from the attached plans, modern warm style, needed for a client meeting on Friday.';
    $image       = ['key' => 'viz/ranch-house-rear', 'alt' => 'Photoreal rendering of a single-storey ranch house with a covered timber patio and lawn, by Architive'];
    $faqs        = [
        1 => ['q' => 'What does the free 3-day trial include?', 'a' => 'One real task from your project, worked on free for up to 3 working days. For most clients that is one interior or exterior view taken to a clear draft, so you can judge quality and communication. We confirm exactly what is included before we start.'],
        2 => ['q' => 'How much do renderings cost after the trial?', 'a' => 'It depends on the number of views, how much custom furniture and context is needed, the revision rounds and the deadline. You get a written quote that lists every view, review stage and delivery date before any paid work starts.'],
        3 => ['q' => 'How fast is the turnaround?', 'a' => 'A simple view usually takes one to three working days and a complex one longer. Because our studio works while US offices are closed, comments you send at the end of your day are often worked on overnight.'],
        4 => ['q' => 'What files do you need?', 'a' => 'Plans and elevations as DWG or PDF, or your Revit, SketchUp or Rhino model, plus finish references and the camera angle you want. If you are unsure, send what you have and we will tell you what is missing.'],
        5 => ['q' => 'Are my drawings kept confidential?', 'a' => 'Yes. We can sign an NDA before you share detailed files, and access is limited to the team working on your project.'],
        6 => ['q' => 'Who is Architive?', 'a' => 'Architive LLC is a US company registered in Delaware with its own production studio. We support architecture firms, interior designers and developers with visualization, BIM and CAD drafting.'],
    ];
@endphp

@section('eyebrow')
3D rendering for architects, designers and developers
@endsection

@section('h1')
Renders Your Clients <em>Say Yes To.</em>
@endsection

@section('lead')
Photoreal interiors and exteriors built from your drawings, matched to your finishes, ready for client sign-off or marketing. Try us free on a real project first.
@endsection

@section('bullets')
    <li><x-icon name="check" /> Interiors, exteriors and aerials</li>
    <li><x-icon name="check" /> From CAD, Revit, SketchUp or PDF</li>
    <li><x-icon name="check" /> Feedback in the evening, progress by morning</li>
    <li><x-icon name="check" /> Written quote before any paid work</li>
@endsection

@section('work')
<section class="section section--alt" aria-labelledby="lp-work">
    <div class="wrap">
        <div class="section-head">
            <div>
                <p class="eyebrow">Our work</p>
                <h2 class="display-h display-h--md mb-0" id="lp-work">Recent <em>Renders</em></h2>
            </div>
            <p class="lead-p mb-0" style="max-width: 44ch">Real projects from our studio. Select any image to enlarge it.</p>
        </div>
        @include('partials.work-gallery', ['only' => ['interiors', 'exteriors'], 'withNew' => true])
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
                    <h2 class="display-h display-h--sm" id="lp-price">How a render is <em>priced</em></h2>
                    <p>Renderings are quoted per view. The surroundings and the level of detail move the price more than the building itself, then come the number of views, day and dusk versions, and the revision rounds you want included.</p>
                    <p class="mb-0">Extra views of the same scene cost less, because the model already exists. You get one written quote listing every view, review stage, revision round and delivery date, and the free trial comes before any paid work.</p>
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

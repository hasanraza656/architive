@extends('layouts.app')

@section('content')
@php
    $items = ['Hero and street-level exterior renderings', 'Aerial and site views', 'Twilight and night renderings', 'Design option and material studies', 'Renovation and before-and-after exteriors', 'Planning and marketing image sets'];

    $views = [
        ['icon' => 'star',   'title' => 'Hero view', 'text' => 'The image on the brochure cover. We find the angle that shows the entrance and the strongest facade, then build the light around it.'],
        ['icon' => 'users',  'title' => 'Street-level view', 'text' => 'The building as a person walking past would see it, with the real neighbours and pavement. This is the view planners and neighbours look at first.'],
        ['icon' => 'globe',  'title' => 'Aerial and site view', 'text' => 'The whole plot, access, parking and landscape in one picture. Useful for multi-unit schemes, masterplans and investor packs.'],
        ['icon' => 'moon',   'title' => 'Twilight and night view', 'text' => 'Warm windows against a deep evening sky. Usually the image that gets shared, and the one that sells the mood of a place.'],
        ['icon' => 'layers', 'title' => 'Option studies', 'text' => 'Two or three facade, colour or material options from the same camera, so your client compares like with like and decides faster.'],
        ['icon' => 'refresh','title' => 'Renovation and before-and-after', 'text' => 'Your existing photo next to the proposed change. Clear for homeowners, lenders and anyone who finds drawings hard to read.'],
    ];

    $send = [
        'Plans, elevations, sections and roof plan (DWG or PDF), or your Revit or SketchUp model',
        'Site plan, levels and a few photos of the plot and the buildings next to it',
        'A materials schedule, with a photo or product link for each finish',
        'Landscape plan, or a short note on the planting you want',
        'Rough camera positions, even arrows marked on a plan',
        'Two or three reference images for mood and time of day',
        'Where the images will be used and your deadline',
    ];

    $steps = [
        ['n' => '01', 'icon' => 'upload', 'title' => 'Send the drawings', 'text' => 'Share what you have. We check it, list anything missing and confirm views, revision rounds, delivery date and price in a written quote.'],
        ['n' => '02', 'icon' => 'box', 'title' => 'Model and camera', 'text' => 'We build the building and its site, then send plain grey draft views so you approve the shape and the camera angles before any finishing work.'],
        ['n' => '03', 'icon' => 'layers', 'title' => 'Materials and landscape', 'text' => 'Facade finishes, glazing, paving, planting, people and cars are added and matched to your specification. You review in one round.'],
        ['n' => '04', 'icon' => 'sun', 'title' => 'Light and final image', 'text' => 'Time of day, sky and the final polish. Because shape and materials are already approved, this round is about mood, not rework.'],
        ['n' => '05', 'icon' => 'download', 'title' => 'Delivery', 'text' => 'High-resolution files sized for screen, print or planning, as agreed in the quote. Need another view later? The model is ready, so it costs less.'],
    ];

    $faqs = [
        1 => ['q' => 'How much do exterior rendering services cost?', 'a' => 'Exterior renderings are priced per view. The main things that move the price are how much of the surroundings must be built, how detailed the facade is, how many views you need and how many revision rounds you want. Extra views of the same scene cost less because the model and site already exist. Send your drawings and we will reply with a written quote that lists every view, round and date.'],
        2 => ['q' => 'How long does an exterior rendering take?', 'a' => 'It depends on the size of the scene and how quickly reviews come back. A single house view is much quicker than an aerial of a full site or a tower in a city block. Your quote gives a delivery date for each stage, and a complete set of drawings at the start is the biggest time saver.'],
        3 => ['q' => 'Can you work from my Revit model or only from drawings?', 'a' => 'Both. A coordinated Revit or SketchUp model saves modelling time. From DWG or PDF drawings, we build the 3D model first and send it to you as a grey draft for approval.'],
        4 => ['q' => 'How many revisions are included?', 'a' => 'Your quotation states the review stages and the revision rounds included. If we miss an agreed instruction, we fix it at no charge. New design directions, changed drawings or extra views are quoted before any extra work starts.'],
        5 => ['q' => 'Can exterior renderings be used for planning applications?', 'a' => 'Yes, and they often help people understand a proposal. For planning we keep the camera at a height a person could actually stand at, show the real neighbours at true scale and avoid planting that will not exist. Check your local authority guidance on whether verified views are needed for your scheme.'],
        6 => ['q' => 'Do you use AI to make exterior renderings?', 'a' => 'Every exterior image is built from your drawings, so window counts, materials and the building next door are correct. AI can be useful for early mood images, but an image that clients, buyers or a planning officer will rely on needs to match the design exactly.'],
        7 => ['q' => 'Do you work with firms outside the US?', 'a' => 'Yes. Architive LLC is registered in Delaware, USA, and supports architecture and design teams in the US, Canada, Australia, Europe and the Gulf. We agree time zones, review times and file transfer at the start.'],
        8 => ['q' => 'Will you keep my unbuilt project confidential?', 'a' => 'Yes. We can sign an NDA before detailed files are shared, project access is limited to the assigned team, and we agree how files are transferred and stored before production starts.'],
    ];
@endphp
@include('partials.service-schema', ['svcName' => 'Exterior Rendering Services', 'svcType' => 'Exterior architectural visualization', 'svcDesc' => config('seo.pages')['services.exterior']['description'], 'svcItems' => $items])

@include('partials.svc-head', [
    'eyebrow' => 'Exterior rendering services',
    'title' => 'Exterior Renderings That Help <em>Your Clients Say Yes.</em>',
    'lead' => 'Photoreal 3D exterior visualization built from your CAD drawings or Revit model, for client approvals, planning submissions, investor decks and pre-sales marketing.',
    'items' => ['Hero and street-level views', 'Aerial and site views', 'Twilight and night views', 'Facade and material options'],
    'image' => ['key' => 'viz/bandon-dusk-wrap', 'alt' => 'Two-storey timber residence with a wraparound porch at dusk, an exterior rendering by Architive'],
    'cta' => 'Get an exterior quote', 'ctaUrl' => pu('contact', [], ['service' => 'visualization']), 'topic' => 'Exterior Rendering',
])

{{-- EXTERIOR WORK (real renders only) --}}
<section class="section" id="work" aria-labelledby="ext-work">
    <div class="wrap">
        <div class="section-head">
            <div>
                <p class="eyebrow" data-reveal>Exterior work</p>
                <h2 class="display-h display-h--md mb-0" id="ext-work" data-split>Exteriors <em>We Have Rendered</em></h2>
            </div>
            <p class="lead-p mb-0" data-reveal style="max-width: 44ch">Homes, mixed-use buildings, commercial options and a full site aerial. Select any image to enlarge it.</p>
        </div>
        @include('partials.work-gallery', ['only' => 'exteriors'])
    </div>
</section>

{{-- VIEW TYPES --}}
<section class="section section--alt" aria-labelledby="ext-views">
    <div class="wrap">
        <div class="section-head">
            <div>
                <p class="eyebrow" data-reveal>What we render</p>
                <h2 class="display-h display-h--md mb-0" id="ext-views" data-split>One Building, <em>Different Jobs</em></h2>
            </div>
            <p class="lead-p mb-0" data-reveal style="max-width: 46ch">Each view answers a different question for a different person. Most projects need two or three of these, and ordering them together costs less than one at a time.</p>
        </div>
        <div class="row g-4">
            @foreach ($views as $v)
                <div class="col-md-6 col-lg-4" data-reveal style="--d: {{ ($loop->index % 3) * .08 }}s">
                    <article class="feature-card h-100">
                        <span class="feature-card__ic"><x-icon :name="$v['icon']" /></span>
                        <h3 class="display-h display-h--sm">{{ $v['title'] }}</h3>
                        <p>{{ $v['text'] }}</p>
                    </article>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- WHO IT IS FOR + WHAT TO SEND --}}
<section class="section" aria-labelledby="ext-send">
    <div class="wrap">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-6" data-reveal>
                <p class="eyebrow">Who it is for</p>
                <h2 class="display-h display-h--sm" data-split>Built for the People <em>Who Have to Decide</em></h2>
                <p><b>Architects</b> use our exterior renderings to get design approval and to win competitions without pulling designers off live projects.</p>
                <p><b>Developers</b> use them to sell units, raise finance and explain a scheme to the community long before groundbreaking.</p>
                <p><b>Builders and design-build firms</b> use them to close homeowners who cannot picture a house from a plan.</p>
                <p><b>Interior and landscape designers</b> use them to show how their work sits with the building and the street.</p>
            </div>
            <div class="col-lg-6" data-reveal style="--d:.1s">
                <p class="eyebrow">What to send</p>
                <h2 class="display-h display-h--sm" id="ext-send">Your Starting <em>Pack</em></h2>
                <ul class="check-list">@foreach ($send as $s)<li><x-icon name="check" /> {{ $s }}</li>@endforeach</ul>
                <p class="note-line">Missing something? Send what you have. We will tell you exactly what else we need before we quote.</p>
            </div>
        </div>
    </div>
</section>

{{-- PROCESS --}}
<section class="section section--alt" aria-labelledby="ext-steps">
    <div class="wrap">
        <div class="text-center mb-5">
            <p class="eyebrow eyebrow--center" data-reveal>How it works</p>
            <h2 class="display-h" id="ext-steps" data-split>Approve in the Right Order. <em>Pay for Fewer Changes.</em></h2>
            <p class="lead-p mx-auto" data-reveal style="max-width: 60ch">Most extra cost in rendering comes from changing the building after the lighting is done. Our steps make sure shape, materials and light are approved one at a time.</p>
        </div>
        <ol class="vsteps" data-vsteps>
            @foreach ($steps as $s)
                <li class="vsteps__item" data-reveal="{{ $loop->odd ? 'left' : 'right' }}">
                    <span class="vsteps__node"><x-icon :name="$s['icon']" /></span>
                    <div class="vsteps__card">
                        <span class="vsteps__n">{{ $s['n'] }}</span>
                        <h3>{{ $s['title'] }}</h3>
                        <p>{{ $s['text'] }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- PRICE + CONFIDENTIALITY --}}
<section class="section" aria-labelledby="ext-price">
    <div class="wrap">
        <div class="row g-4">
            <div class="col-lg-7" data-reveal>
                <article class="feature-card h-100">
                    <span class="feature-card__ic"><x-icon name="receipt" /></span>
                    <p class="eyebrow">Pricing</p>
                    <h2 class="display-h display-h--sm" id="ext-price">What Decides <em>the Price</em></h2>
                    <p>Exterior renderings are quoted per view. The building itself is rarely what makes a quote high. The surroundings are: a house on a lawn is quick, a tower in a modelled city block is not. After that come facade detail, the number of views, day and dusk versions, and the revision rounds you want included.</p>
                    <p class="mb-0">You get one written quote listing every view, review stage, revision round and delivery date. No surprises later. For a fuller explanation, read <a href="{{ pu('blog.show', ['slug' => 'architectural-visualization-cost-guide']) }}">what drives the cost of architectural visualization</a>.</p>
                </article>
            </div>
            <div class="col-lg-5" data-reveal style="--d:.12s">
                <article class="feature-card feature-card--dark h-100">
                    <span class="feature-card__ic"><x-icon name="lock" /></span>
                    <p class="eyebrow eyebrow--light">Confidentiality</p>
                    <h2 class="display-h display-h--light display-h--sm">Unbuilt Projects <em>Stay Private</em></h2>
                    <p class="mb-0">We can sign an NDA before detailed files are shared. Project access is limited to the assigned team, and transfer and storage are agreed before production starts.</p>
                </article>
            </div>
        </div>
    </div>
</section>

{{-- FAQ --}}
<section class="section section--alt" aria-labelledby="ext-faq">
    <div class="wrap wrap--narrow">
        <p class="eyebrow eyebrow--center" data-reveal>Questions</p>
        <h2 class="display-h text-center mb-5" id="ext-faq" data-split>Exterior Rendering <em>Questions</em></h2>
        @include('partials.faq-list', ['items' => $faqs, 'uid' => 'extfaq'])
        <p class="note-line text-center mt-4">Need interiors or 3D floor plans too? See our <a href="{{ pu('services.visualization') }}">architectural visualization services</a>.</p>
    </div>
</section>

@include('partials.cta-band', ['title' => 'Send Your Drawings. <em>Get a Clear Exterior Quote.</em>', 'cta' => 'Get an exterior quote', 'ctaUrl' => pu('contact', [], ['service' => 'visualization'])])
@endsection

@extends('layouts.app')

@section('content')
@php
    use App\Support\Content;
    $items = ['Interior and exterior renderings', '3D floor plans and axonometric views', 'Renovation and before-and-after visuals', 'Material and design-option studies', 'Marketing stills, animations and 360-degree views by scope'];
@endphp
@include('partials.service-schema', ['svcName' => 'Architectural Visualization and Rendering', 'svcType' => 'Architectural visualization', 'svcDesc' => config('seo.pages')['services.visualization']['description'], 'svcItems' => $items])

@include('partials.page-hero', [
    'eyebrow' => 'Architectural visualization',
    'title' => 'Make the Design Easy to Understand—<em>and Easier to Approve.</em>',
    'lead' => 'Accurate, presentation-ready visuals for client decisions, design approvals, investor conversations and pre-construction marketing—developed from your drawings, models and references.',
    'cta' => 'Start a visualization project', 'ctaUrl' => pu('contact', [], ['service' => 'visualization']),
    'secondary' => ['How reviews work', '#process'],
    'image' => 'assets/img/photos/exterior-modern-house', 'imgPos' => '50% 60%',
    'chips' => ['Interior and exterior renderings', '3D floor plans and axonometric views', 'Renovation and option visuals'],
])

{{-- BUYER PROBLEM + compare --}}
<section class="section" aria-labelledby="viz-problem">
    <div class="wrap">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5">
                <p class="eyebrow" data-reveal>Buyer problem</p>
                <h2 class="display-h" id="viz-problem" data-split>A Clear Visual Can Resolve <em>What a Drawing Cannot.</em></h2>
                <p class="lead-p" data-reveal style="--d:.1s">A design team can read plans and sections. Clients, buyers and investors often cannot. When the finished space is difficult to picture, decisions slow down and expectations become harder to manage.</p>
                <p class="lead-p" data-reveal style="--d:.2s">We translate the agreed design into clear, realistic visuals, with geometry and camera views reviewed before final materials and lighting are developed.</p>
            </div>
            <div class="col-lg-7" data-reveal="zoom">
                <x-compare label-a="Grayscale review" label-b="Final render" aria="Drag to compare the grayscale geometry review with the final rendered image">
                    <x-slot:a><img class="compare__img compare__img--gray" src="{{ asset('assets/img/photos/exterior-modern-house.webp') }}" width="1600" height="1068" loading="lazy" decoding="async" alt="Grayscale geometry review of a modern residence exterior"></x-slot:a>
                    <x-slot:b><img class="compare__img" src="{{ asset('assets/img/photos/exterior-modern-house.webp') }}" width="1600" height="1068" loading="lazy" decoding="async" alt="Final rendered exterior of a modern residence with materials, landscape and lighting"></x-slot:b>
                </x-compare>
                <p class="mono-note mt-3">Drag to see how a grayscale geometry review becomes a finished image. Illustrative imagery.</p>
            </div>
        </div>
    </div>
</section>

{{-- DELIVERABLES --}}
<section class="section section--alt" aria-labelledby="viz-deliver">
    <div class="wrap">
        <div class="row g-5">
            <div class="col-lg-5">
                <p class="eyebrow" data-reveal>Deliverables</p>
                <h2 class="display-h" id="viz-deliver" data-split>Visualization <em>Services</em></h2>
                <p class="lead-p" data-reveal style="--d:.1s">Our architectural rendering services cover photorealistic interior and exterior rendering, 3D floor plans for planning and sales, renovation options and architectural animation or walkthroughs by scope.</p>
            </div>
            <div class="col-lg-7">
                <ul class="tile-grid">
                    @foreach ($items as $it)
                        <li class="tile" data-reveal style="--d: {{ $loop->index * .08 }}s"><span class="tile__ic"><x-icon name="{{ ['image','layers','refresh','target','eye'][$loop->index] }}" /></span><span>{{ $it }}</span></li>
                    @endforeach
                </ul>
            </div>
        </div>
        <div class="row g-3 mt-4 photo-row">
            @foreach ([['interior-kitchen-render', 'Interior views', 'Modern kitchen interior with timber cabinetry and a central island'], ['exterior-residence', 'Exterior views', 'Contemporary residence exterior with landscaped frontage'], ['renovation-interior', 'Renovation and option visuals', 'Interior mid-renovation with ladders and exposed walls']] as [$img, $cap, $alt])
                <div class="col-md-4" data-reveal style="--d: {{ $loop->index * .12 }}s">
                    <figure class="photo" data-tilt>
                        <img src="{{ asset('assets/img/photos/' . $img . '-800.webp') }}" width="800" height="533" loading="lazy" decoding="async" alt="{{ $alt }}">
                        <figcaption>{{ $cap }}</figcaption>
                    </figure>
                </div>
            @endforeach
        </div>
        <p class="mono-note text-center mt-3">Illustrative photography showing the types of space we visualize.</p>
    </div>
</section>

{{-- AUDIENCE --}}
<section class="section" aria-labelledby="viz-aud">
    <div class="wrap">
        <p class="eyebrow" data-reveal>Audience</p>
        <h2 class="display-h" id="viz-aud" data-split>Built for the Decision <em>in Front of You.</em></h2>
        <div class="row g-4 mt-2">
            @foreach ([['building', 'Architecture firms', 'presenting design options or planning proposals'], ['layers', 'Interior designers', 'reviewing layouts, furniture, finishes and lighting'], ['ruler', 'Developers', 'preparing investor or pre-construction marketing material'], ['home', 'Homeowners', 'comparing renovation, addition or new-home options']] as [$ic, $who, $what])
                <div class="col-sm-6 col-lg-3" data-reveal style="--d: {{ $loop->index * .1 }}s">
                    <div class="mini-card"><span class="mini-card__ic"><x-icon :name="$ic" /></span><h3>{{ $who }}</h3><p>{{ ucfirst($what) }}</p></div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- PROCESS --}}
<section class="section section--dark" id="process" aria-labelledby="viz-process">
    <div class="process__glow" aria-hidden="true"></div>
    <div class="wrap">
        <p class="eyebrow eyebrow--light" data-reveal>Process</p>
        <h2 class="display-h display-h--light" id="viz-process" data-split>A Review-Led <em>Rendering Process</em></h2>
        @include('partials.process-steps', ['steps' => [
            ['n' => '01', 'title' => 'Confirm', 'text' => 'Confirm the purpose, views, references and delivery date.'],
            ['n' => '02', 'title' => 'Grayscale review', 'text' => 'Build or clean the model and share a grayscale review.'],
            ['n' => '03', 'title' => 'Approve views', 'text' => 'Approve geometry and camera positions.'],
            ['n' => '04', 'title' => 'Develop', 'text' => 'Develop materials, lighting, landscape and styling.'],
            ['n' => '05', 'title' => 'Revise and deliver', 'text' => 'Complete the agreed revisions and deliver high-resolution files.'],
        ]])
    </div>
</section>

{{-- QUALITY --}}
<section class="section" aria-labelledby="viz-quality">
    <div class="wrap">
        <div class="callout-panel" data-reveal="zoom">
            <span class="callout-panel__ic"><x-icon name="shield" /></span>
            <div>
                <p class="eyebrow">Quality</p>
                <h2 class="display-h display-h--sm" id="viz-quality">Checked Against <em>the Design Information</em></h2>
                <p class="lead-p mb-0">If two drawings conflict or a material is unclear, we raise the question before final rendering. The goal is not simply a beautiful image; it is a visual that supports a real decision.</p>
            </div>
        </div>
    </div>
</section>

{{-- FAQ --}}
<section class="section section--alt" aria-labelledby="viz-faq">
    <div class="wrap wrap--narrow">
        <p class="eyebrow eyebrow--center" data-reveal>Questions</p>
        <h2 class="display-h text-center" id="viz-faq" data-split>Before You <em>Start</em></h2>
        @include('partials.faq-list', ['items' => Content::faqsById([5, 6, 8, 7]), 'uid' => 'vizfaq'])
    </div>
</section>

@include('partials.cta-band', ['title' => 'Ready to Make the Design <em>Easier to Approve?</em>', 'cta' => 'Discuss your visualization', 'ctaUrl' => pu('contact', [], ['service' => 'visualization'])])
@endsection

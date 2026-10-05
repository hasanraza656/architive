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
    'secondary' => ['See our work', '#work'],
    'image' => 'assets/img/work/viz/bandon-dusk-wrap', 'imgPos' => '50% 55%',
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
                @php $cmp = \App\Support\Portfolio::img('viz/brick-mixed-use'); @endphp
                <x-compare label-a="Grayscale review" label-b="Final render" ratio="16 / 9" aria="Drag to compare the grayscale geometry review with the final rendered image">
                    <x-slot:a><img class="compare__img compare__img--gray" src="{{ $cmp['src'] }}" width="{{ $cmp['w'] }}" height="{{ $cmp['h'] }}" loading="lazy" decoding="async" alt="Grayscale geometry review of a brick mixed-use building"></x-slot:a>
                    <x-slot:b><img class="compare__img" src="{{ $cmp['src'] }}" width="{{ $cmp['w'] }}" height="{{ $cmp['h'] }}" loading="lazy" decoding="async" alt="Final rendered brick mixed-use building with materials, landscape and lighting"></x-slot:b>
                </x-compare>
                <p class="mono-note mt-3">Drag to see how a grayscale geometry review becomes a finished image (the grayscale view is a simulation of the review stage).</p>
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
    </div>
</section>

{{-- SELECTED WORK (real renders) --}}
<section class="section" id="work" aria-labelledby="viz-work">
    <div class="wrap">
        <div class="section-head">
            <div>
                <p class="eyebrow" data-reveal>Selected work</p>
                <h2 class="display-h mb-0" id="viz-work" data-split>Visuals <em>We Have Delivered</em></h2>
            </div>
            <p class="lead-p mb-0" data-reveal style="max-width: 44ch">Exteriors, interiors, 3D floor plans and concept visuals. Select any image to enlarge it.</p>
        </div>
        @include('partials.work-gallery')
        <p class="note-line">Selected examples of Architive visualization work; some images carry the Architive studio mark.</p>
    </div>
</section>

{{-- AUDIENCE --}}
<section class="section section--alt" aria-labelledby="viz-aud">
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

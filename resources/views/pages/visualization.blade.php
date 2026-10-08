@extends('layouts.app')

@section('content')
@php
    use App\Support\Content;
    $items = ['Interior and exterior renderings', '3D floor plans and axonometric views', 'Renovation and before-and-after visuals', 'Material and design-option studies', 'Marketing stills, animations and 360-degree views by scope'];
@endphp
@include('partials.service-schema', ['svcName' => 'Architectural Visualization and Rendering', 'svcType' => 'Architectural visualization', 'svcDesc' => config('seo.pages')['services.visualization']['description'], 'svcItems' => $items])

@include('partials.svc-head', [
    'eyebrow' => 'Architectural visualization',
    'title' => 'Make the Design Easy to Understand—<em>and Easier to Approve.</em>',
    'lead' => 'Accurate, presentation-ready visuals for client decisions, design approvals, investor conversations and pre-construction marketing—developed from your drawings, models and references.',
    'items' => ['Interior and exterior renderings', '3D floor plans and axonometric views', 'Booth design', 'Self-storage containers', 'Renovation and option visuals'],
    'image' => ['key' => 'viz/bandon-dusk-lawn', 'alt' => 'Timber-and-stone residence at dusk, an architectural exterior visualization by Architive'],
    'cta' => 'Start a project', 'ctaUrl' => pu('contact', [], ['service' => 'visualization']), 'topic' => 'Architectural Visualization',
])

@include('partials.sample-reel', ['category' => 'visualization', 'limit' => 12])

{{-- SELECTED WORK (real renders) --}}
<section class="section" id="work" aria-labelledby="viz-work">
    <div class="wrap">
        <div class="section-head">
            <div>
                <p class="eyebrow" data-reveal>Selected work</p>
                <h2 class="display-h display-h--md mb-0" id="viz-work" data-split>Visuals <em>We Have Delivered</em></h2>
            </div>
            <p class="lead-p mb-0" data-reveal style="max-width: 44ch">Select any image to enlarge it.</p>
        </div>
        @include('partials.work-gallery')
        <p class="note-line">Selected examples of Architive visualization work; some images carry the Architive studio mark.</p>
    </div>
</section>

@include('partials.cta-band', ['title' => 'Ready to Make the Design <em>Easier to Approve?</em>', 'cta' => 'Start a project', 'ctaUrl' => pu('contact', [], ['service' => 'visualization'])])
@endsection

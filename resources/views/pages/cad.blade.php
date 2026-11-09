@extends('layouts.app')

@push('head')
    <link rel="stylesheet" href="{{ asset_v('assets/css/cad-pairs.css') }}">
@endpush

@section('content')
@php
    use App\Support\Content;
    $items = ['Floor plans, reflected ceiling plans and space plans', 'Interior and exterior elevations', 'Building, wall and detail sections', 'Door and window schedules', 'PDF, sketch and scan to DWG', 'As-built and renovation drawings', 'Redline and revision updates', 'Construction and shop-drawing support from approved information'];
@endphp
@include('partials.service-schema', ['svcName' => 'CAD Drafting and Permit Drawing Support', 'svcType' => 'Architectural CAD drafting', 'svcDesc' => config('seo.pages')['services.cad']['description'], 'svcItems' => $items])

@include('partials.svc-head', [
    'eyebrow' => 'CAD drafting and permit support',
    'title' => 'Your Redlines Should Not Be <em>Waiting Tomorrow Morning.</em>',
    'lead' => 'Send the sketches, PDFs, surveys or markups. Receive organized, editable AutoCAD drawings set up around your title block, layers, conventions and next project stage.',
    'items' => ['Plans, elevations and sections', 'PDF, sketch and scan to DWG', 'Permit-support drawing packages', 'As-built, renovation and redline updates'],
    'image' => ['key' => 'sets/adr/02-proposed-floor-plans', 'alt' => 'Proposed floor plans from an adaptive reuse permit set', 'sheet' => true],
    'cta' => 'Start a project', 'ctaUrl' => pu('contact', [], ['service' => 'cad']), 'topic' => 'CAD Drafting',
    'note' => 'Where local regulations require drawings to be signed or sealed, we prepare the package for review by your locally licensed professional.',
])

@include('partials.sample-reel', ['category' => 'cad', 'limit' => 12])

{{-- AUTOCAD BEFORE / AFTER (client's own slides, cut into panels) --}}
<section class="section" id="autocad" aria-labelledby="cad-autocad">
    <div class="wrap">
        <div class="section-head">
            <div>
                <p class="eyebrow" data-reveal>AutoCAD drafting samples</p>
                <h2 class="display-h mb-0" id="cad-autocad" data-split>From Sketch and Markup <em>to Scaled AutoCAD</em></h2>
            </div>
            <p class="lead-p mb-0" data-reveal style="max-width: 46ch">Hand sketches, surveys and marked-up plans turned into dimensioned, scaled drawings. Drag the slider to compare.</p>
        </div>
        <div class="cadpairs">
            @foreach (\App\Support\Portfolio::cadPairs() as $pair)
                <article class="cadpair" data-reveal>
                    <x-compare label-a="Before" label-b="After" ratio="1100 / 1104" :aria="'Drag to compare: ' . $pair['capA'] . ' and ' . strtolower($pair['capB'])">
                        <x-slot:a><img class="compare__img" src="{{ $pair['a']['src'] }}" width="{{ $pair['a']['w'] }}" height="{{ $pair['a']['h'] }}" alt="{{ $pair['altA'] }}" loading="lazy" decoding="async"></x-slot:a>
                        <x-slot:b><img class="compare__img" src="{{ $pair['b']['src'] }}" width="{{ $pair['b']['w'] }}" height="{{ $pair['b']['h'] }}" alt="{{ $pair['altB'] }}" loading="lazy" decoding="async"></x-slot:b>
                    </x-compare>
                    <div class="cadpair__body">
                        <p class="cadpair__meta">{{ $pair['meta'] }}</p>
                        <h3>{{ $pair['title'] }}</h3>
                        <p class="cadpair__caps"><span><b>Before</b> {{ $pair['capA'] }}</span><span><b>After</b> {{ $pair['capB'] }}</span></p>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- SAMPLE PERMIT / DRAWING SETS (real, redacted sheets) --}}
<section class="section section--alt" id="samples" aria-labelledby="cad-samples">
    <div class="wrap">
        <div class="section-head">
            <div>
                <p class="eyebrow" data-reveal>Sample drawing sets</p>
                <h2 class="display-h mb-0" id="cad-samples" data-split>Permit and Construction <em>Drawing Samples</em></h2>
            </div>
            <p class="lead-p mb-0" data-reveal style="max-width: 46ch">Drawing sets prepared by Architive. Select a set to browse its sheets, then zoom in to read the detail.</p>
        </div>
        <div class="set-grid">
            @foreach (\App\Support\Portfolio::sets('cad') as $set)
                @include('partials.set-card', ['set' => $set])
            @endforeach
        </div>
        <p class="callout callout--wide mt-4" data-reveal><x-icon name="info" /><span><b>Note:</b> These samples show drawing production. Where local law requires drawings to be signed or sealed, the client appoints the locally licensed professional. Cover sheets and site plans are omitted, and title blocks (owner, address and contractor details) are removed to protect client privacy.</span></p>
    </div>
</section>

<section class="section pt-0"><div class="wrap"><p class="note-line text-center">Got city comments on a permit set? <a href="{{ pu('permit-revisions') }}">Get a permit revision estimate</a>. Does your team draft in Rayon? See our <a href="{{ pu('services.rayon') }}">Rayon CAD drafting services</a>, or turn plans into <a href="{{ pu('services.floorplans') }}">3D floor plans</a>.</p></div></section>

@include('partials.cta-band', ['title' => 'Send Your Drawings <em>or Markups.</em>', 'cta' => 'Start a project', 'ctaUrl' => pu('contact', [], ['service' => 'cad'])])
@endsection

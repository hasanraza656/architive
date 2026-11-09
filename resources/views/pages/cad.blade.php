@extends('layouts.app')

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
    'image' => ['key' => 'cad/adu-both-after', 'alt' => 'Dimensioned AutoCAD plans of an ADU drawn from marked-up concept plans', 'sheet' => true],
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
                @include('partials.cad-pair', ['pair' => $pair])
            @endforeach
        </div>
    </div>
</section>

<section class="section pt-0"><div class="wrap"><p class="note-line text-center">Got city comments on a permit set? <a href="{{ pu('permit-revisions') }}">Get a permit revision estimate</a>. Does your team draft in Rayon? See our <a href="{{ pu('services.rayon') }}">Rayon CAD drafting services</a>, or turn plans into <a href="{{ pu('services.floorplans') }}">3D floor plans</a>.</p></div></section>

@include('partials.cta-band', ['title' => 'Send Your Drawings <em>or Markups.</em>', 'cta' => 'Start a project', 'ctaUrl' => pu('contact', [], ['service' => 'cad'])])
@endsection

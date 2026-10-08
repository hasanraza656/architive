@extends('layouts.app')

@section('content')
@php
    use App\Support\Content;
    $items = ['Architectural Revit modeling', 'CAD to BIM conversion', 'Scan to BIM from surveyor-supplied point clouds', 'Plans, sections, elevations and schedules', 'Revit family creation', 'Existing-model updates and cleanup'];
@endphp
@include('partials.service-schema', ['svcName' => 'BIM Modeling and Revit Services', 'svcType' => 'BIM modeling', 'svcDesc' => config('seo.pages')['services.bim']['description'], 'svcItems' => $items])

@include('partials.svc-head', [
    'eyebrow' => 'BIM and Revit',
    'title' => 'Revit Capacity That Fits <em>the Way Your Office Works.</em>',
    'lead' => 'Structured architectural models and documentation built around your Revit version, project purpose, naming conventions and delivery standards—not ours.',
    'items' => ['Architectural Revit modeling', 'CAD to BIM and scan to BIM', 'Plans, sections, elevations and schedules', 'Revit families and model updates'],
    'image' => ['key' => 'scan/house-1-model', 'alt' => 'Revit existing-conditions model of a historic brick house built from a surveyor point cloud'],
    'cta' => 'Start a project', 'ctaUrl' => pu('contact', [], ['service' => 'bim']), 'topic' => 'BIM and Revit',
])

@include('partials.sample-reel', ['category' => 'bim', 'limit' => 12])

{{-- SCAN TO BIM: real point cloud vs. Revit model --}}
<section class="section" id="convert" aria-labelledby="bim-convert">
    <div class="wrap">
        <div class="section-head">
            <div>
                <p class="eyebrow" data-reveal>Scan to BIM</p>
                <h2 class="display-h display-h--md mb-0" id="bim-convert" data-split>From Point Cloud <em>to Revit Model</em></h2>
            </div>
            <p class="lead-p mb-0" data-reveal style="max-width: 46ch">Drag the handle: registered point cloud on the left, the Revit model built from it on the right.</p>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-9">
@php $pairs = \App\Support\Portfolio::scanPairs(); $p0 = $pairs[0]; $fac = \App\Support\Portfolio::scanFacade(); @endphp
                            <div data-pairs>
                                <div class="filters filters--left" role="group" aria-label="Choose a view">
                                    @foreach ($pairs as $i => $p)
                                        <button type="button" class="{{ $i === 0 ? 'is-active' : '' }}" data-pair aria-pressed="{{ $i === 0 ? 'true' : 'false' }}"
                                                data-a="{{ $p['a']['src'] }}" data-b="{{ $p['b']['src'] }}" data-alt-a="{{ $p['altA'] }}" data-alt-b="{{ $p['altB'] }}"
                                                data-ratio="{{ $p['ratio'] }}" data-w="{{ $p['a']['w'] }}" data-h="{{ $p['a']['h'] }}">{{ $p['label'] }}</button>
                                    @endforeach
                                </div>
                                <x-compare label-a="Point cloud" label-b="Revit model" :ratio="$p0['ratio']" aria="Drag to compare the surveyor point cloud with the Revit existing-conditions model">
                                    <x-slot:a><img class="compare__img" src="{{ $p0['a']['src'] }}" width="{{ $p0['a']['w'] }}" height="{{ $p0['a']['h'] }}" alt="{{ $p0['altA'] }}" loading="lazy" decoding="async"></x-slot:a>
                                    <x-slot:b><img class="compare__img" src="{{ $p0['b']['src'] }}" width="{{ $p0['b']['w'] }}" height="{{ $p0['b']['h'] }}" alt="{{ $p0['altB'] }}" loading="lazy" decoding="async"></x-slot:b>
                                </x-compare>
                            </div>
                                        </div>
        </div>
        <div class="duo">
                        <figure><img src="{{ $fac['a']['src'] }}" width="{{ $fac['a']['w'] }}" height="{{ $fac['a']['h'] }}" alt="{{ $fac['altA'] }}" loading="lazy" decoding="async"><figcaption>Point cloud</figcaption></figure>
                        <figure><img src="{{ $fac['b']['src'] }}" width="{{ $fac['b']['w'] }}" height="{{ $fac['b']['h'] }}" alt="{{ $fac['altB'] }}" loading="lazy" decoding="async"><figcaption>Revit model</figcaption></figure>
                    </div>
                    <p class="note-line text-center">Another existing facade: scan data and the resulting architectural model.</p>
    </div>
</section>

{{-- REVIT / BIM SAMPLE SETS (real, redacted sheets) --}}
<section class="section section--alt" id="samples" aria-labelledby="bim-samples">
    <div class="wrap">
        <div class="section-head">
            <div>
                <p class="eyebrow" data-reveal>Sample sets</p>
                <h2 class="display-h mb-0" id="bim-samples" data-split>Revit and BIM <em>Samples</em></h2>
            </div>
            <p class="lead-p mb-0" data-reveal style="max-width: 46ch">Existing-conditions drawing sets built in Revit from registered scan data and measured information. Select a set to browse its sheets.</p>
        </div>
        <div class="set-grid set-grid--3">
            @foreach (\App\Support\Portfolio::sets('bim') as $set)
                @include('partials.set-card', ['set' => $set])
            @endforeach
        </div>
        <p class="callout callout--wide mt-4" data-reveal><x-icon name="info" /><span><b>Note:</b> Architive does not perform on-site scanning; these models were built from point-cloud data and information supplied by the client. Cover sheets and site plans are omitted, and title blocks (owner, address and client details) are removed to protect client privacy.</span></p>
    </div>
</section>

@include('partials.cta-band', ['title' => 'Need Revit Capacity <em>That Fits Your Office?</em>', 'cta' => 'Start a project', 'ctaUrl' => pu('contact', [], ['service' => 'bim'])])
@endsection

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
    'image' => ['key' => 'sets/ny/01-main-level-plan', 'alt' => 'Main level floor plan from a residential addition permit set', 'sheet' => true],
    'cta' => 'Start a project', 'ctaUrl' => pu('contact', [], ['service' => 'cad']), 'topic' => 'CAD Drafting',
    'note' => 'Where local regulations require drawings to be signed or sealed, we prepare the package for review by your locally licensed professional.',
])

@include('partials.sample-reel', ['category' => 'cad', 'limit' => 12])

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

@include('partials.cta-band', ['title' => 'Send Your Drawings <em>or Markups.</em>', 'cta' => 'Start a project', 'ctaUrl' => pu('contact', [], ['service' => 'cad'])])
@endsection

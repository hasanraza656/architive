@extends('layouts.app', ['overlay' => true])

@section('content')
@php
    use App\Support\Content;
    $items = ['Floor plans, reflected ceiling plans and space plans', 'Interior and exterior elevations', 'Building, wall and detail sections', 'Door and window schedules', 'PDF, sketch and scan to DWG', 'As-built and renovation drawings', 'Redline and revision updates', 'Construction and shop-drawing support from approved information'];
    $icons = ['layers', 'building', 'ruler', 'file-text', 'upload', 'home', 'refresh', 'pencil-ruler'];
@endphp
@include('partials.service-schema', ['svcName' => 'CAD Drafting and Permit Drawing Support', 'svcType' => 'Architectural CAD drafting', 'svcDesc' => config('seo.pages')['services.cad']['description'], 'svcItems' => $items])

@include('partials.page-hero', [
    'eyebrow' => 'CAD drafting and permit support',
    'title' => 'Your Redlines Should Not Be <em>Waiting Tomorrow Morning.</em>',
    'lead' => 'Send the sketches, PDFs, surveys or markups. Receive organized, editable AutoCAD drawings set up around your title block, layers, conventions and next project stage.',
    'cta' => 'Start a CAD project', 'ctaUrl' => pu('contact', [], ['service' => 'cad']),
    'secondary' => ['Explore the layers', '#layers'],
    'image' => 'assets/img/photos/cad-drafting-desk', 'imgPos' => '50% 40%',
    'chips' => ['Plans, elevations and sections', 'PDF, sketch and scan to DWG', 'Permit-support drawing packages'],
])

<section class="section" aria-labelledby="cad-problem">
    <div class="wrap">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <p class="eyebrow" data-reveal>Buyer problem</p>
                <h2 class="display-h" id="cad-problem" data-split>Drawings That Drop into the Set—<em>not Back onto Your Correction List.</em></h2>
            </div>
            <div class="col-lg-6">
                <p class="lead-p" data-reveal>Redlines are piling up, the set needs to move and your senior team should not be checking basic layer, dimension and plotting issues.</p>
                <p class="lead-p" data-reveal style="--d:.1s">We convert the information you provide into clear drawings, raise missing or conflicting details early and check the set against the agreed standards before delivery.</p>
            </div>
        </div>
    </div>
</section>

<section class="section section--alt" aria-labelledby="cad-deliver">
    <div class="wrap">
        <p class="eyebrow" data-reveal>Deliverables</p>
        <h2 class="display-h" id="cad-deliver" data-split>CAD <em>Drafting Services</em></h2>
        <p class="lead-p" data-reveal>Architectural drafting and CAD conversion, from floor plan drafting and elevations to as-built and redline updates, drawn to your conventions.</p>
        <ul class="tile-grid tile-grid--4 mt-4">
            @foreach ($items as $it)
                <li class="tile tile--col" data-reveal style="--d: {{ $loop->index * .06 }}s"><span class="tile__ic"><x-icon :name="$icons[$loop->index]" /></span><span>{{ $it }}</span></li>
            @endforeach
        </ul>
    </div>
</section>

{{-- INTERACTIVE LAYERS --}}
<section class="section" id="layers" aria-labelledby="cad-standards">
    <div class="wrap">
        <div class="row g-5 align-items-start">
            <div class="col-lg-5">
                <p class="eyebrow" data-reveal>Standards</p>
                <h2 class="display-h" id="cad-standards" data-split>Confirmed <em>Before Drafting Begins</em></h2>
                <p class="lead-p" data-reveal>Every set is built on layers, line weights and conventions you approve first. Switch the layers of this sample plan on and off to see how an organized drawing is structured.</p>
                <ul class="layers" data-layers aria-label="Toggle drawing layers">
                    @foreach ([['A-WALL', 'Walls', 'walls'], ['A-DOOR', 'Doors and windows', 'doors'], ['A-FURN', 'Furniture', 'furn'], ['A-DIMS', 'Dimensions', 'dims'], ['A-ANNO', 'Annotations', 'anno'], ['G-TTLB', 'Title block', 'title']] as [$code, $label, $key])
                        <li>
                            <button type="button" class="layer is-on" data-layer="{{ $key }}" aria-pressed="true">
                                <span class="layer__sw layer__sw--{{ $key }}"></span>
                                <span class="layer__code">{{ $code }}</span>
                                <span class="layer__label">{{ $label }}</span>
                                <span class="layer__eye"><x-icon name="eye" /></span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="col-lg-7" data-reveal="zoom">
                <div class="cad-canvas" data-layers-target>
                    <div class="cad-canvas__bar"><span><i></i><i></i><i></i></span><b>Drawing1.dwg — Model</b><em>AutoCAD · 1:100</em></div>
                    <svg class="cad-plan" viewBox="0 0 640 430" role="img" aria-label="Sample architectural floor plan with toggleable drawing layers" focusable="false">
                        <g data-layer-group="furn" class="L L-furn">
                            <rect x="66" y="68" width="112" height="90"/><rect x="74" y="74" width="40" height="22" rx="4"/><rect x="122" y="74" width="40" height="22" rx="4"/>
                            <rect x="190" y="48" width="64" height="22"/>
                            <rect x="268" y="48" width="104" height="44" rx="3"/><circle cx="342" cy="126" r="14"/><rect x="326" y="146" width="32" height="20" rx="4"/>
                            <rect x="392" y="48" width="200" height="26"/><rect x="392" y="74" width="26" height="80"/><circle cx="470" cy="116" r="9"/><circle cx="506" cy="116" r="9"/>
                            <rect x="70" y="288" width="150" height="40" rx="6"/><rect x="104" y="240" width="82" height="34" rx="4"/>
                            <circle cx="430" cy="280" r="32"/><circle cx="430" cy="232" r="9"/><circle cx="430" cy="328" r="9"/><circle cx="382" cy="280" r="9"/><circle cx="478" cy="280" r="9"/>
                        </g>
                        <g data-layer-group="walls" class="L L-walls">
                            <path d="M40 40H600V340H40Z" stroke-width="6" fill="none"/>
                            <path d="M260 40V200M380 40V200M40 200H150M190 200H300M340 200H440M540 200H600" stroke-width="5" fill="none"/>
                        </g>
                        <g data-layer-group="doors" class="L L-doors">
                            <path d="M150 200V160a40 40 0 0 1 40 40"/><path d="M300 200V160a40 40 0 0 1 40 40"/><path d="M300 340V280a60 60 0 0 1 60 60"/>
                            <path d="M90 34H210M90 46H210M430 34H560M430 46H560M34 240V300M46 240V300"/>
                        </g>
                        <g data-layer-group="dims" class="L L-dims">
                            <path d="M40 20H600M40 12v16M600 12v16M20 40V340M12 40h16M12 340h16"/>
                            <path d="M40 366H260M260 366H380M380 366H600" stroke-dasharray="3 3"/>
                            <text x="320" y="14" text-anchor="middle">11 200</text><text x="14" y="190" transform="rotate(-90 14 190)" text-anchor="middle">6 000</text>
                            <text x="150" y="380" text-anchor="middle">4 400</text><text x="320" y="380" text-anchor="middle">2 400</text><text x="490" y="380" text-anchor="middle">4 400</text>
                        </g>
                        <g data-layer-group="anno" class="L L-anno">
                            <text x="150" y="130" text-anchor="middle">BEDROOM</text><text x="320" y="125" text-anchor="middle">BATH</text><text x="490" y="105" text-anchor="middle">KITCHEN</text><text x="300" y="256" text-anchor="middle">LIVING / DINING</text>
                            <text x="150" y="146" text-anchor="middle" class="sub">12.1 m²</text><text x="490" y="121" text-anchor="middle" class="sub">14.6 m²</text>
                        </g>
                        <g data-layer-group="title" class="L L-title">
                            <rect x="404" y="384" width="216" height="40"/><path d="M476 384V424"/>
                            <text x="440" y="400" text-anchor="middle">A-101</text><text x="440" y="416" text-anchor="middle" class="sub">REV 02</text>
                            <text x="548" y="400" text-anchor="middle">FLOOR PLAN</text><text x="548" y="416" text-anchor="middle" class="sub">ARCHITIVE · 1:100</text>
                        </g>
                    </svg>
                </div>
            </div>
        </div>

        <ul class="check-grid mt-5">
            @foreach (['Units, scale and reference dimensions', 'AutoCAD version and deliverables', 'Layer names, colours and line weights', 'Title block, sheet size and plot setup', 'Dimension, annotation and symbol conventions', 'Xrefs, fonts and folder structure', 'Revision rounds and approval responsibility'] as $s)
                <li data-reveal style="--d: {{ $loop->index * .06 }}s"><span><x-icon name="check" /></span>{{ $s }}</li>
            @endforeach
        </ul>
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

{{-- PERMIT + RENOVATIONS --}}
<section class="section" aria-labelledby="cad-permit">
    <div class="wrap">
        <div class="row g-4">
            <div class="col-lg-6" data-reveal>
                <article class="feature-card feature-card--dark">
                    <span class="feature-card__ic"><x-icon name="shield" /></span>
                    <p class="eyebrow eyebrow--light">Permit support</p>
                    <h2 class="display-h display-h--light display-h--sm" id="cad-permit">Permit <em>Drawing Packages</em></h2>
                    <p>We prepare and revise permit-support drawing packages from the project information and local requirements supplied by you or your appointed professional. Where local law requires drawings to be reviewed, signed or sealed, the client appoints the locally licensed architect, engineer or other authorized professional.</p>
                </article>
            </div>
            <div class="col-lg-6" data-reveal style="--d:.12s">
                <article class="feature-card feature-card--photo">
                    <img src="{{ asset('assets/img/photos/renovation-room-800.webp') }}" alt="Room being prepared for renovation with a stepladder and table" width="800" height="533" loading="lazy" decoding="async">
                    <div class="feature-card__body">
                        <p class="eyebrow eyebrow--light">Renovations</p>
                        <h2 class="display-h display-h--light display-h--sm">Drawings for <em>Renovations and Additions</em></h2>
                        <p>We develop existing-condition and proposed plans from sketches, measured information, surveys and available records. These drawings help homeowners and project teams discuss the scope clearly with contractors and local professionals before construction.</p>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="section section--alt" aria-labelledby="cad-faq">
    <div class="wrap wrap--narrow">
        <p class="eyebrow eyebrow--center" data-reveal>Questions</p>
        <h2 class="display-h text-center" id="cad-faq" data-split>Before You <em>Start</em></h2>
        @include('partials.faq-list', ['items' => Content::faqsById([4, 1, 6, 8]), 'uid' => 'cadfaq'])
    </div>
</section>

@include('partials.cta-band', ['title' => 'Send Your Drawings <em>or Markups.</em>', 'cta' => 'Send your drawings or markups', 'ctaUrl' => pu('contact', [], ['service' => 'cad'])])
@endsection

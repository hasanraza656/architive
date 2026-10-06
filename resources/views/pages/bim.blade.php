@extends('layouts.app', ['overlay' => true])

@section('content')
@php
    use App\Support\Content;
    $items = ['Architectural Revit modeling', 'CAD to BIM conversion', 'Scan to BIM from surveyor-supplied point clouds', 'Plans, sections, elevations and schedules', 'Revit family creation', 'Existing-model updates and cleanup'];

    // Shared plan geometry for the CAD→BIM and Scan→BIM comparison drawings.
    $walls = [[60,50,580,50],[580,50,580,350],[580,350,60,350],[60,350,60,50],[300,50,300,230],[60,230,300,230],[440,230,440,350]];
    mt_srand(11);
    $cloud = '';
    foreach ($walls as [$x1,$y1,$x2,$y2]) {
        $len = max(abs($x2-$x1), abs($y2-$y1));
        for ($t = 0; $t <= $len; $t += 4) {
            if (mt_rand(0, 100) < 9) { continue; }
            $x = $x1 + ($x2-$x1) * ($t/$len) + mt_rand(-30, 30)/10;
            $y = $y1 + ($y2-$y1) * ($t/$len) + mt_rand(-30, 30)/10;
            $cloud .= 'M' . round($x, 1) . ' ' . round($y, 1) . 'h.01';
        }
    }
@endphp
@include('partials.service-schema', ['svcName' => 'BIM Modeling and Revit Services', 'svcType' => 'BIM modeling', 'svcDesc' => config('seo.pages')['services.bim']['description'], 'svcItems' => $items])

@include('partials.page-hero', [
    'eyebrow' => 'BIM and Revit',
    'title' => 'Revit Capacity That Fits <em>the Way Your Office Works.</em>',
    'lead' => 'Structured architectural models and documentation built around your Revit version, project purpose, naming conventions and delivery standards—not ours.',
    'cta' => 'Start a BIM project', 'ctaUrl' => pu('contact', [], ['service' => 'bim']),
    'secondary' => ['CAD to BIM and scan to BIM', '#convert'],
    'image' => 'assets/img/photos/bim-house-model', 'imgPos' => '50% 40%',
    'chips' => ['Architectural Revit modeling', 'CAD to BIM and scan to BIM', 'Plans, sections, elevations and schedules'],
])

<section class="section" aria-labelledby="bim-problem">
    <div class="wrap">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <p class="eyebrow" data-reveal>Buyer problem</p>
                <h2 class="display-h" id="bim-problem" data-split>Outside BIM Support Should Reduce Checking—<em>not Create More of It.</em></h2>
            </div>
            <div class="col-lg-6">
                <p class="lead-p" data-reveal>When internal teams are overloaded, outside BIM support should reduce pressure—not return a model that needs to be reorganized before anyone can use it.</p>
                <p class="lead-p" data-reveal style="--d:.1s">We agree the model use, level of detail, version, views, naming and file structure first, then build and review the model around those requirements.</p>
            </div>
        </div>
    </div>
</section>

<section class="section section--alt" aria-labelledby="bim-deliver">
    <div class="wrap">
        <p class="eyebrow" data-reveal>Deliverables</p>
        <h2 class="display-h" id="bim-deliver" data-split>BIM and <em>Revit Services</em></h2>
        <p class="lead-p" data-reveal>From architectural BIM modeling and Revit documentation to family creation and model cleanup, each service is scoped to your project purpose and standards.</p>
        <ul class="tile-grid tile-grid--3 mt-4">
            @foreach ($items as $it)
                <li class="tile" data-reveal style="--d: {{ $loop->index * .07 }}s"><span class="tile__ic"><x-icon name="{{ ['box','file-text','scan','layers','pencil-ruler','refresh'][$loop->index] }}" /></span><span>{{ $it }}</span></li>
            @endforeach
        </ul>
    </div>
</section>

{{-- CAD TO BIM / SCAN TO BIM (interactive) --}}
<section class="section" id="convert" aria-labelledby="bim-convert">
    <div class="wrap">
        <div class="text-center mb-5">
            <p class="eyebrow eyebrow--center" data-reveal>Source to model</p>
            <h2 class="display-h" id="bim-convert" data-split>From Your Source Information <em>into Revit.</em></h2>
        </div>
        <div class="tabs-ay" data-reveal>
            <div class="nav" role="tablist" aria-label="Conversion types">
                <button class="nav-link active" id="tab-cad-bim" data-bs-toggle="tab" data-bs-target="#pane-cad-bim" type="button" role="tab" aria-controls="pane-cad-bim" aria-selected="true"><x-icon name="file-text" /> CAD to BIM</button>
                <button class="nav-link" id="tab-scan-bim" data-bs-toggle="tab" data-bs-target="#pane-scan-bim" type="button" role="tab" aria-controls="pane-scan-bim" aria-selected="false"><x-icon name="scan" /> Scan to BIM</button>
            </div>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="pane-cad-bim" role="tabpanel" aria-labelledby="tab-cad-bim" tabindex="0">
                    <div class="row g-5 align-items-center">
                        <div class="col-lg-5">
                            <p class="eyebrow">CAD to BIM</p>
                            <h3 class="display-h display-h--sm">Move Coordinated Drawings <em>into Revit</em></h3>
                            <p class="lead-p">Send your DWG or PDF plans, sections and elevations. We establish the agreed levels, grids, model detail and required outputs before modeling. Conflicts in the source information are listed for your decision rather than resolved by assumption.</p>
                        </div>
                        <div class="col-lg-7">
                            <x-compare label-a="DWG source" label-b="Revit model" ratio="16 / 10" aria="Drag to compare the DWG source drawing with the Revit model">
                                <x-slot:a>
                                    <svg class="plan plan--dwg" viewBox="0 0 640 400" role="img" aria-label="Line plan from a DWG file" focusable="false">
                                        <g class="dwg">
                                            <path d="M60 50H580V350H60Z M70 60H570V340H70Z M300 50V230 M310 50V230 M60 230H300 M60 240H300 M440 230V350 M450 230V350"/>
                                            <path class="dwg-door" d="M240 240a40 40 0 0 1 40 -40 M450 290a40 40 0 0 1 -40 40"/>
                                            <path class="dwg-dim" d="M60 28H580M60 22v12M580 22v12M30 50V350M24 50h12M24 350h12"/>
                                        </g>
                                        <text class="plan__t" x="320" y="24" text-anchor="middle">26 000</text>
                                        <text class="plan__t" x="26" y="205" transform="rotate(-90 26 205)" text-anchor="middle">15 000</text>
                                    </svg>
                                </x-slot:a>
                                <x-slot:b>
                                    <svg class="plan plan--bim" viewBox="0 0 640 400" role="img" aria-label="Revit model plan with walls, rooms and tags" focusable="false">
                                        <rect class="bim-floor" x="70" y="60" width="500" height="280"/>
                                        <g class="bim-room"><rect x="70" y="60" width="230" height="170"/><rect x="310" y="60" width="260" height="280"/><rect x="70" y="240" width="370" height="100" opacity=".6"/></g>
                                        <path class="bim-wall" d="M60 50H580V350H60Z M70 60V340H570V60Z M300 50H310V230H300Z M60 230H300V240H60Z M440 230H450V350H440Z" fill-rule="evenodd"/>
                                        <path class="bim-door" d="M240 240a40 40 0 0 1 40 -40 M450 290a40 40 0 0 1 -40 40"/>
                                        <g class="bim-tags"><text x="150" y="150">RM 101</text><text x="410" y="210">RM 102</text><text x="230" y="300">RM 103</text></g>
                                        <g class="bim-grid"><circle cx="60" cy="24" r="12"/><text x="60" y="28">A</text><circle cx="580" cy="24" r="12"/><text x="580" y="28">B</text><circle cx="24" cy="50" r="12"/><text x="24" y="54">1</text></g>
                                        <text class="bim-lod" x="580" y="384" text-anchor="end">REVIT · LOD 300</text>
                                    </svg>
                                </x-slot:b>
                            </x-compare>
                            <p class="mono-note mt-3">Illustrative diagram: a DWG plan and the Revit model built from it. Drag the handle.</p>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="pane-scan-bim" role="tabpanel" aria-labelledby="tab-scan-bim" tabindex="0">
                    <div class="row g-5 align-items-center">
                        <div class="col-lg-5">
                            <p class="eyebrow">Scan to BIM</p>
                            <h3 class="display-h display-h--sm">Existing Conditions <em>from Point Cloud to Revit</em></h3>
                            <p class="lead-p">We turn registered point-cloud data supplied by your surveyor into an architectural existing-conditions model for renovation, extension or refurbishment work. File formats, required accuracy and modeled elements are confirmed before production. Architive does not perform on-site scanning.</p>
                        </div>
                        <div class="col-lg-7">
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
                            <p class="mono-note mt-3">Drag the handle: registered point cloud on the left, the Revit model built from it on the right.</p>
                        </div>
                    </div>
                    <div class="duo">
                        <figure><img src="{{ $fac['a']['src'] }}" width="{{ $fac['a']['w'] }}" height="{{ $fac['a']['h'] }}" alt="{{ $fac['altA'] }}" loading="lazy" decoding="async"><figcaption>Point cloud</figcaption></figure>
                        <figure><img src="{{ $fac['b']['src'] }}" width="{{ $fac['b']['w'] }}" height="{{ $fac['b']['h'] }}" alt="{{ $fac['altB'] }}" loading="lazy" decoding="async"><figcaption>Revit model</figcaption></figure>
                    </div>
                    <p class="note-line text-center">Another existing facade: scan data and the resulting architectural model.</p>
                </div>
            </div>
        </div>
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

<section class="section" aria-labelledby="bim-standards">
    <div class="wrap">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5">
                <p class="eyebrow" data-reveal>Standards</p>
                <h2 class="display-h" id="bim-standards" data-split>Set Up <em>Around Your Office</em></h2>
                <p class="lead-p" data-reveal>Before modeling starts, these points are agreed in writing so the model opens, reads and behaves the way your team expects.</p>
            </div>
            <div class="col-lg-7">
                <ul class="check-grid">
                    @foreach (['Revit version and delivery method', 'Coordinates, units, levels and grids', 'Model detail by element', 'Worksets, links and naming', 'Families, views, sheets and schedules', 'Review points and source information'] as $s)
                        <li data-reveal style="--d: {{ $loop->index * .07 }}s"><span><x-icon name="check" /></span>{{ $s }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="section section--dark" aria-labelledby="bim-scope">
    <div class="wrap">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5">
                <p class="eyebrow eyebrow--light" data-reveal>Scope boundary</p>
                <h2 class="display-h display-h--light" id="bim-scope" data-split>Clear Architectural <em>BIM Scope</em></h2>
                <p class="lead-p" data-reveal>Our core scope is architectural modeling and documentation. Clash detection, multidisciplinary coordination, engineering design and on-site laser scanning are excluded unless explicitly agreed with qualified project partners.</p>
            </div>
            <div class="col-lg-7">
                <div class="scope" data-reveal="zoom">
                    <div class="scope__col scope__col--in"><h3><x-icon name="check" /> Core scope</h3><ul><li>Architectural modeling</li><li>Architectural documentation</li><li>Plans, sections, elevations and schedules</li></ul></div>
                    <div class="scope__col"><h3><x-icon name="x" /> Excluded unless explicitly agreed</h3><ul><li>Clash detection</li><li>Multidisciplinary coordination</li><li>Engineering design</li><li>On-site laser scanning</li></ul></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section" aria-labelledby="bim-faq">
    <div class="wrap wrap--narrow">
        <p class="eyebrow eyebrow--center" data-reveal>Questions</p>
        <h2 class="display-h text-center" id="bim-faq" data-split>Before You <em>Start</em></h2>
        @include('partials.faq-list', ['items' => Content::faqsById([1, 8, 6, 7]), 'uid' => 'bimfaq'])
    </div>
</section>

@include('partials.cta-band', ['title' => 'Need Revit Capacity <em>That Fits Your Office?</em>', 'cta' => 'Discuss your BIM requirements', 'ctaUrl' => pu('contact', [], ['service' => 'bim'])])
@endsection

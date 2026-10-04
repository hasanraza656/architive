@extends('layouts.app')

@section('content')
@php
    use App\Support\Content;
    $services = Content::services();
    $picker = [
        'sketches' => ['I have sketches, PDFs or markups', 'cad', 'CAD drafting turns sketches, PDFs, surveys and markups into scaled, editable AutoCAD drawings set up to your title block, layers and conventions.'],
        'revit'    => ['I have CAD files and need a Revit model', 'bim', 'BIM and Revit services build structured models from CAD files, surveys and point clouds, with the model purpose, version and standards agreed first.'],
        'visuals'  => ['I need visuals for a client decision', 'visualization', 'Architectural visualization produces photorealistic interiors, exteriors and 3D floor plans, with geometry reviewed before materials and lighting.'],
        'capacity' => ['I need extra capacity on live projects', 'outsourcing', 'Production support extends your studio with one point of contact, your templates and standards, and agreed review stages—project-based or ongoing.'],
    ];
@endphp

@include('partials.page-hero', [
    'eyebrow' => 'Services',
    'title' => 'One Team for Drawings, Models <em>and Visuals.</em>',
    'lead' => 'Three connected services—architectural visualization, BIM and Revit, and CAD drafting—delivered as one coordinated production partner, with flexible engagement around your pipeline.',
    'cta' => 'Start your project', 'ctaUrl' => pu('contact'),
    'secondary' => ['Which service do I need?', '#picker'],
    'image' => 'assets/img/photos/glass-towers', 'imgPos' => '50% 40%',
])

{{-- PICKER --}}
<section class="section" id="picker" aria-labelledby="pick-title">
    <div class="wrap wrap--narrow">
        <div class="text-center mb-4">
            <p class="eyebrow eyebrow--center" data-reveal>Find your fit</p>
            <h2 class="display-h" id="pick-title" data-split>Which Service <em>Do I Need?</em></h2>
        </div>
        <div class="picker" data-picker data-reveal>
            <div class="picker__opts" role="group" aria-label="Describe your situation">
                @foreach ($picker as $k => [$label, $svc, $text])
                    <button type="button" class="picker__opt" data-pick="{{ $k }}" data-svc="{{ $svc }}" data-text="{{ $text }}" data-url="{{ pu($services[$svc]['route']) }}" data-title="{{ $services[$svc]['title'] }}" aria-pressed="false">
                        <span class="picker__dot"></span>{{ $label }}
                    </button>
                @endforeach
            </div>
            <div class="picker__result" aria-live="polite" hidden>
                <p class="eyebrow">We'd suggest</p>
                <h3 data-pick-title></h3>
                <p data-pick-text></p>
                <a class="btn-ay" data-pick-link href="#">Explore this service <x-icon name="arrow-right" /></a>
            </div>
        </div>
    </div>
</section>

{{-- SERVICE ROWS --}}
<section class="section section--alt" aria-label="Service overview">
    <div class="wrap">
        @foreach ($services as $key => $s)
            <article class="svc-row {{ $loop->even ? 'svc-row--rev' : '' }}">
                <div class="row g-5 align-items-center">
                    <div class="col-lg-6 {{ $loop->even ? 'order-lg-2' : '' }}" data-reveal="{{ $loop->even ? 'right' : 'left' }}">
                        <a class="svc-row__media" href="{{ pu($s['route']) }}" tabindex="-1" aria-hidden="true" data-tilt>
                            <img src="{{ asset($s['image'] . '-800.webp') }}" alt="{{ $s['alt'] }}" width="800" height="533" loading="lazy" decoding="async">
                            <span class="svc-row__badge"><x-icon :name="$s['icon']" /></span>
                        </a>
                    </div>
                    <div class="col-lg-6 {{ $loop->even ? 'order-lg-1' : '' }}">
                        <p class="eyebrow" data-reveal>0{{ $loop->iteration }} · {{ $key === 'outsourcing' ? 'Engagement model' : 'Core service' }}</p>
                        <h2 class="display-h display-h--sm" data-split>{{ $s['title'] }}</h2>
                        <p class="lead-p" data-reveal>{{ $s['short'] }}</p>
                        <ul class="check-list" data-reveal>
                            @foreach ($s['items'] as $it)<li><x-icon name="check" /> {{ $it }}</li>@endforeach
                        </ul>
                        <a class="btn-ay" href="{{ pu($s['route']) }}" data-reveal>{{ $s['link'] }} <x-icon name="arrow-right" /></a>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</section>

{{-- Combined deliverable promise --}}
<section class="section" aria-labelledby="svc-promise">
    <div class="wrap">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <p class="eyebrow" data-reveal>Engagement model</p>
                <h2 class="display-h" id="svc-promise" data-split>Production Support Is <em>How You Work With Us</em>—Not a Fourth Service.</h2>
                <p class="lead-p" data-reveal>Bring us one defined assignment, begin with a paid pilot or use Architive as recurring production support. Your templates, standards and approvals stay yours.</p>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    @foreach (['One point of contact', 'Your templates and standards', 'Agreed review stages', 'Flexible project-based or ongoing support'] as $p)
                        <div class="col-6" data-reveal style="--d: {{ $loop->index * .08 }}s"><div class="mini-card mini-card--flat"><span class="mini-card__ic"><x-icon name="check" /></span><p class="mb-0">{{ $p }}</p></div></div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

@include('partials.cta-band')
@endsection

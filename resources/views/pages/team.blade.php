@extends('layouts.app')

@section('content')
@include('partials.page-hero', [
    'eyebrow' => 'Team',
    'title' => 'Specialists Who <em>Work as One Studio.</em>',
    'lead' => 'Our CAD, Revit and visualization specialists work within one production structure, with a named point of contact and an architectural review appropriate to the scope.',
    'cta' => 'Start your project', 'ctaUrl' => pu('contact'),
    'image' => 'assets/img/photos/developer-tower-frame', 'imgPos' => '50% 40%',
])

<section class="section" aria-labelledby="team-structure">
    <div class="wrap">
        <div class="text-center mb-5">
            <p class="eyebrow eyebrow--center" data-reveal>Studio structure</p>
            <h2 class="display-h" id="team-structure" data-split>One Brief. <em>Three Disciplines. One Review.</em></h2>
        </div>
        <ol class="flow" data-reveal>
            <li><span><x-icon name="users" /></span><b>Named point of contact</b><small>Your single line to the studio</small></li>
            <li><span><x-icon name="layers" /></span><b>Discipline specialists</b><small>CAD · Revit · Visualization</small></li>
            <li><span><x-icon name="shield" /></span><b>Architectural review</b><small>Appropriate to the scope</small></li>
            <li><span><x-icon name="check" /></span><b>Delivery</b><small>In your agreed formats</small></li>
        </ol>
    </div>
</section>

<section class="section section--alt" aria-labelledby="team-disc">
    <div class="wrap">
        <p class="eyebrow" data-reveal>Disciplines</p>
        <h2 class="display-h" id="team-disc" data-split>Who Does <em>What</em></h2>
        <div class="row g-4 mt-2">
            @foreach ([
                ['file-text', 'CAD drafting', 'Convert sketches, PDFs, surveys and markups into scaled, editable AutoCAD drawings set up to your title block, layers and conventions.', ['Plans, elevations and sections', 'PDF, sketch and scan to DWG', 'Redlines and permit-support packages']],
                ['box', 'BIM and Revit', 'Build structured architectural Revit models and documentation around your version, naming and delivery standards.', ['Architectural Revit modeling', 'CAD to BIM and scan to BIM', 'Families, schedules and model updates']],
                ['eye', 'Visualization', 'Develop accurate, presentation-ready interiors, exteriors and 3D floor plans from your drawings, models and references.', ['Interior and exterior renderings', 'Renovation and option visuals', 'Animation and 360-degree views by scope']],
            ] as [$ic, $t, $d, $li])
                <div class="col-lg-4" data-reveal style="--d: {{ $loop->index * .12 }}s">
                    <article class="disc-card" data-spotlight>
                        <span class="disc-card__ic"><x-icon :name="$ic" /></span>
                        <h3>{{ $t }}</h3>
                        <p>{{ $d }}</p>
                        <ul>@foreach ($li as $i)<li><x-icon name="check" /> {{ $i }}</li>@endforeach</ul>
                    </article>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="section" aria-labelledby="team-founder">
    <div class="wrap">
        <div class="row g-5 align-items-center">
            <div class="col-lg-4" data-reveal="left">
                <div class="founder-card">
                    <div class="founder-card__mono" aria-hidden="true">MA</div>
                    <h3>{{ config('site.founder') }}</h3>
                    <p>{{ config('site.founder_role') }}</p>
                </div>
            </div>
            <div class="col-lg-8">
                <p class="eyebrow" data-reveal>Leadership</p>
                <h2 class="display-h" id="team-founder" data-split>Closely Involved <em>in Every Engagement</em></h2>
                <p class="lead-p" data-reveal>Madiha Altaf founded Architive in 2017 and remains closely involved in client communication, project scoping and delivery coordination. The team has grown, but the way we work remains personal: understand the brief, communicate clearly and make the client's next step easier.</p>
                <a class="link-arrow" href="{{ pu('about') }}" data-reveal>Read our story <x-icon name="arrow-right" /></a>
            </div>
        </div>
    </div>
</section>

@include('partials.cta-band')
@endsection

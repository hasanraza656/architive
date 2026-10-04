@extends('layouts.app')

@section('content')
@php use App\Support\Content; @endphp

@include('partials.page-hero', [
    'eyebrow' => 'About Architive',
    'title' => 'Built from the Same Production Problem <em>Our Clients Still Face.</em>',
    'lead' => 'Architive began because good design teams were losing time coordinating separate people for drawings, BIM and visualization.',
    'image' => 'assets/img/photos/blueprint-tools', 'imgPos' => '50% 45%',
])

<section class="section" aria-labelledby="about-open">
    <div class="wrap">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <p class="eyebrow" data-reveal>Our opening</p>
                <h2 class="display-h" id="about-open" data-split>One Coordinated Studio, <em>Without Taking Over the Design.</em></h2>
            </div>
            <div class="col-lg-6">
                <p class="lead-p" data-reveal>We built one coordinated production studio to carry approved design information through the next stage—without taking over the design or the client relationship.</p>
            </div>
        </div>
    </div>
</section>

{{-- FOUNDER STORY --}}
<section class="section section--alt" aria-labelledby="about-story">
    <div class="wrap">
        <div class="row g-5">
            <div class="col-lg-4">
                <div class="founder-card" data-reveal="left">
                    <div class="founder-card__mono" aria-hidden="true">MA</div>
                    <h3>{{ config('site.founder') }}</h3>
                    <p>{{ config('site.founder_role') }}</p>
                    <ul>
                        <li><x-icon name="calendar" /> Founded Architive in {{ config('site.founded') }}</li>
                        <li><x-icon name="map-pin" /> Studio in Multan, Pakistan</li>
                        <li><x-icon name="building" /> Architive LLC, Delaware, USA</li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-8">
                <p class="eyebrow" data-reveal>Founder story</p>
                <h2 class="display-h" id="about-story" data-split>How <em>Architive Began</em></h2>
                <p class="lead-p" data-reveal>I started Architive in 2017 as a freelance architectural engineer on Fiverr and Upwork. In the beginning, it was simply me working directly with architects, interior designers and homeowners across different time zones.</p>
                <p class="lead-p" data-reveal>There was no single project that changed everything. Clients kept returning. A rendering request became a complete visual package. A small drafting task became ongoing CAD or Revit support. As the work grew, I built a team around the same approach: understand the information properly, ask when something is unclear and deliver work that is ready for the next stage.</p>
                <p class="lead-p" data-reveal>Along the way, I saw how often clients had to coordinate one person for CAD, another for BIM and someone else for rendering. Architive grew to bring those services together under one roof.</p>
                <p class="lead-p" data-reveal>Today, Architive has grown into a strong team supporting architecture firms, interior design studios, developers, contractors and homeowners worldwide. I remain closely involved in client communication, project scoping and delivery coordination.</p>
                <p class="signature-line" data-reveal>— Madiha Altaf, Founder and Architectural Engineer</p>
            </div>
        </div>

        <ol class="journey" aria-label="How the studio grew">
            @foreach ([['2017', 'Freelance beginnings', 'Working directly with architects, interior designers and homeowners across time zones.'], ['Repeat work', 'Clients kept returning', 'A rendering request became a visual package; a drafting task became ongoing CAD or Revit support.'], ['Growth', 'A team built around one approach', 'Understand the information, ask when it is unclear, deliver work ready for the next stage.'], ['Today', 'One studio, three disciplines', 'Visualization, BIM and Revit, and CAD drafting under one roof.']] as [$k, $t, $d])
                <li data-reveal style="--d: {{ $loop->index * .12 }}s"><span class="journey__k">{{ $k }}</span><h3>{{ $t }}</h3><p>{{ $d }}</p></li>
            @endforeach
        </ol>
    </div>
</section>

{{-- PRINCIPLES --}}
<section class="section" aria-labelledby="about-how">
    <div class="wrap">
        <p class="eyebrow" data-reveal>Principles</p>
        <h2 class="display-h" id="about-how" data-split>How <em>We Work</em></h2>
        <div class="row g-4 mt-2">
            @foreach ([['We clarify before we assume.', 'Missing dimensions and conflicting information are raised early.', 'help'], ['Accuracy comes before polish.', 'A drawing, model or image must reflect the agreed design information.', 'ruler'], ['Your standards guide the work.', 'We confirm templates, versions, file structure and review points before production.', 'layers'], ['We take responsibility.', 'If we miss an agreed instruction, we correct it without charging for our oversight.', 'shield']] as [$t, $d, $ic])
                <div class="col-md-6 col-xl-3" data-reveal style="--d: {{ $loop->index * .1 }}s">
                    <div class="principle" data-spotlight><span class="principle__n">0{{ $loop->iteration }}</span><h3>{{ $t }}</h3><p>{{ $d }}</p></div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- TEAM TEASER --}}
<section class="section section--dark" aria-labelledby="about-team">
    <div class="process__glow" aria-hidden="true"></div>
    <div class="wrap">
        <div class="row g-5 align-items-center">
            <div class="col-lg-7">
                <p class="eyebrow eyebrow--light" data-reveal>Team</p>
                <h2 class="display-h display-h--light" id="about-team" data-split>Specialists Who <em>Work as One Studio</em></h2>
                <p class="lead-p" data-reveal>Our CAD, Revit and visualization specialists work within one production structure, with a named point of contact and an architectural review appropriate to the scope.</p>
                <a class="btn-ay mt-2" href="{{ pu('team') }}" data-reveal>Meet the team <x-icon name="arrow-right" /></a>
                <p class="mono-note mono-note--light" data-reveal>Start with a free consultation or a small paid pilot. No commitment until you approve the scope.</p>
            </div>
            <div class="col-lg-5" data-reveal="zoom">
                <div class="stat-stack">
                    <div><strong data-count="2017" data-from="1990">2017</strong><span>Established</span></div>
                    <div><strong data-count="1500" data-suffix="+">1,500+</strong><span>Projects delivered</span></div>
                    <div><strong data-count="12" data-suffix="+">12+</strong><span>Team specialists</span></div>
                </div>
            </div>
        </div>
    </div>
</section>

@include('partials.cta-band')
@endsection

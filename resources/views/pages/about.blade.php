@extends('layouts.app')

@section('content')
@include('partials.svc-head', [
    'eyebrow' => 'Who we are',
    'title' => 'Built from the Same Production Problem <em>Our Clients Still Face.</em>',
    'lead' => 'Architive began because good design teams were losing time coordinating separate people for drawings, BIM and visualization.',
])

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
                        <li><x-icon name="map-pin" /> Production studio in Pakistan</li>
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
    </div>
</section>

@include('partials.cta-band', ['title' => 'One Brief. <em>One Team.</em>', 'cta' => 'Start your project', 'ctaUrl' => pu('contact')])
@endsection

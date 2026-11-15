@extends('layouts.landing', ['lp' => true, 'seo' => [
    'title'       => 'CAD Drafting Services for US Permit Sets | Free 3-Day Trial',
    'description' => 'Permit and construction drawing sets drafted from your sketches, markups or PDFs, to your standards. Samples include ADU plans and sketch-to-CAD drawings. Try us free for 3 days.',
    'robots'      => 'noindex,follow',
]])
{{-- Google Ads landing page: /lp/cad-drafting/. All text of this page is in this file. The shared sections (proof, free-trial steps, reviews, why-us, final call, sticky bar) live in layouts/landing.blade.php. --}}
@php
    $slug        = 'cad-drafting';
    $service     = 'cad';          // sent with the form
    $topic       = 'CAD Drafting';
    $formTitle   = 'Start your free trial';
    $placeholder = 'For example: redraw the attached marked-up PDF floor plan and elevations in AutoCAD using our title block, for a permit submission next week.';
    $image       = ['key' => 'cad/adu-both-after', 'alt' => 'Dimensioned AutoCAD plans of an ADU drafted by Architive from marked-up concept plans'];
    $faqs        = [
        1 => ['q' => 'What does the free 3-day trial include?', 'a' => 'One real drafting task, worked on free for up to 3 working days, such as a floor plan and elevations from your markups. You see how closely we follow your standards before committing.'],
        2 => ['q' => 'Do you stamp or seal drawings?', 'a' => 'No. We prepare the drawings. Where local rules require drawings to be signed or sealed, your locally licensed professional reviews and stamps them.'],
        3 => ['q' => 'Will you use our templates and standards?', 'a' => 'Yes. Send your title block, layer standards and a recent example set, and we match them.'],
        4 => ['q' => 'Which software do you use?', 'a' => 'AutoCAD for 2D drafting and Revit for BIM work. We deliver DWG, PDF or RVT, whichever your team needs.'],
        5 => ['q' => 'How fast is the turnaround?', 'a' => 'It depends on the size of the set. Because our studio works while US offices are closed, markups you send at the end of your day are often drafted by the next morning.'],
        6 => ['q' => 'Who is Architive?', 'a' => 'Architive LLC is a US company registered in Delaware with its own production studio, supporting architecture firms and builders with CAD drafting, BIM and visualization.'],
    ];
@endphp

@section('eyebrow')
CAD drafting for architects, designers and builders
@endsection

@section('h1')
Drawing Sets Done <em>While You Sleep.</em>
@endsection

@section('lead')
Floor plans, elevations, sections and details drafted from your sketches, markups or PDFs, to your templates and layer standards. Try us free on a real set first.
@endsection

@section('bullets')
    <li><x-icon name="check" /> Permit and construction drawing sets</li>
    <li><x-icon name="check" /> Your templates, title blocks and layers</li>
    <li><x-icon name="check" /> PDF and scan to DWG conversion</li>
    <li><x-icon name="check" /> Sample ADU, sketch-to-CAD and survey plans</li>
@endsection

@section('work')
<section class="section section--alt" aria-labelledby="lp-work">
    <div class="wrap">
        <div class="section-head">
            <div>
                <p class="eyebrow">Our work</p>
                <h2 class="display-h display-h--md mb-0" id="lp-work">Drawing Sets <em>We Have Drafted</em></h2>
            </div>
            <p class="lead-p mb-0" style="max-width: 44ch">Real projects from our studio. Select any image to enlarge it.</p>
        </div>
        <div class="cadpairs">
            @foreach (\App\Support\Portfolio::cadPairs() as $pair)
                @include('partials.cad-pair', ['pair' => $pair])
            @endforeach
        </div>
        <p class="note-line mt-3">Where drawings must be signed or sealed, your locally licensed professional reviews and stamps them.</p>
    </div>
</section>
@endsection

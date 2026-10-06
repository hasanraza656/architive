@extends('layouts.app', ['overlay' => true])

@section('content')
@php
    use App\Support\Content;
    $core = Content::coreServices();
    // Each situation opens the enquiry form with that service ticked, so the visitor can add address, scope and files straight away
    $picker = [
        'sketches' => ['I have sketches, PDFs or markups', 'cad'],
        'revit'    => ['I have CAD files and need a Revit model', 'bim'],
        'visuals'  => ['I need visuals for a client decision', 'visualization'],
        'capacity' => ['I need extra capacity on live projects', 'outsourcing'],
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

{{-- PICKER: choosing a situation opens the form --}}
<section class="section" id="picker" aria-labelledby="pick-title">
    <div class="wrap wrap--narrow">
        <div class="text-center mb-4">
            <p class="eyebrow eyebrow--center" data-reveal>Find your fit</p>
            <h2 class="display-h" id="pick-title" data-split>Where Are You <em>Right Now?</em></h2>
            <p class="lead-p mx-auto" data-reveal>Pick the situation closest to yours. The enquiry form opens with the right service selected, so you can add your address, scope and files.</p>
        </div>
        <div class="picker" data-picker data-reveal>
            <div class="picker__opts" role="group" aria-label="Describe your situation">
                @foreach ($picker as $k => [$label, $svc])
                    <button type="button" class="picker__opt" data-pick="{{ $k }}" data-svc="{{ $svc }}" aria-pressed="false" aria-haspopup="dialog">
                        <span class="picker__dot"></span>{{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- SERVICE ROWS --}}
<section class="section section--alt" aria-label="Service overview">
    <div class="wrap">
        @foreach ($core as $key => $s)
            <article class="svc-row {{ $loop->even ? 'svc-row--rev' : '' }}">
                <div class="row g-5 align-items-center">
                    <div class="col-lg-6 {{ $loop->even ? 'order-lg-2' : '' }}" data-reveal="{{ $loop->even ? 'right' : 'left' }}">
                        <div class="svc-row__slider">@include('partials.service-slider', ['s' => $s, 'n' => $loop->iteration])</div>
                    </div>
                    <div class="col-lg-6 {{ $loop->even ? 'order-lg-1' : '' }}">
                        <p class="eyebrow" data-reveal>0{{ $loop->iteration }} · Core service</p>
                        <h2 class="display-h display-h--sm" data-split>{{ $s['title'] }}</h2>
                        <p class="lead-p" data-reveal>{{ $s['short'] }}</p>
                        <ul class="check-list" data-reveal>
                            @foreach ($s['items'] as $it)<li><x-icon name="check" /> {{ $it }}</li>@endforeach
                        </ul>
                        <div class="d-flex flex-wrap align-items-center gap-3" data-reveal>
                            <a class="btn-ay" href="{{ pu($s['route']) }}">{{ $s['link'] }} <x-icon name="arrow-right" /></a>
                            <a class="link-arrow" href="{{ pu('contact', [], ['service' => $key]) }}">Start a project <x-icon name="arrow-right" /></a>
                        </div>
                    </div>
                </div>
            </article>
        @endforeach
        @include('partials.support-strip')
    </div>
</section>

@include('partials.cta-band')
@endsection

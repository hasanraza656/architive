@extends('layouts.app', ['overlay' => true])

@section('content')
@php
    use App\Support\Content;
    $engagements = Content::engagements();
    $svcItems = collect($engagements)->pluck('title')->all();
@endphp
@include('partials.service-schema', ['svcName' => 'Architectural Production Support', 'svcType' => 'Architectural outsourcing and production support', 'svcDesc' => config('seo.pages')['services.outsourcing']['description'], 'svcItems' => $svcItems])

@include('partials.page-hero', [
    'eyebrow' => 'Architectural production support',
    'title' => 'Keep the Design In-House. <em>Extend the Production Capacity.</em>',
    'lead' => 'Add CAD, Revit and visualization capacity when the workload demands it—without recruiting permanent staff or managing a scattered group of freelancers.',
    'cta' => 'Discuss production support', 'ctaUrl' => pu('contact', [], ['service' => 'outsourcing']),
    'secondary' => ['Choose an engagement', '#engagements'],
    'image' => 'assets/img/photos/bim-steel-structure', 'imgPos' => '50% 50%',
    'chips' => ['CAD drafting capacity', 'Revit production support', 'Visualization capacity'],
])

{{-- WHY FIRMS USE US --}}
<section class="section" aria-labelledby="out-why">
    <div class="wrap">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <p class="eyebrow" data-reveal>Why firms use us</p>
                <h2 class="display-h" id="out-why" data-split>Your Client Relationship Stays Yours. <em>The Production Load Does Not Have To.</em></h2>
                <p class="lead-p" data-reveal>You retain the design decisions, client relationship and project leadership. We work behind your team to produce the agreed drawings, models or visuals in your templates and formats.</p>
                <p class="lead-p" data-reveal style="--d:.1s">One named contact, written scopes and defined review stages reduce the management burden usually associated with distributed freelancers.</p>
            </div>
            <div class="col-lg-6" data-reveal="zoom">
                <div class="split-card">
                    <div class="split-card__col">
                        <h3>You keep</h3>
                        <ul><li><x-icon name="check" /> Design decisions</li><li><x-icon name="check" /> Client relationship</li><li><x-icon name="check" /> Project leadership</li><li><x-icon name="check" /> Your templates and approvals</li></ul>
                    </div>
                    <div class="split-card__mid" aria-hidden="true"><x-icon name="arrow-right" /></div>
                    <div class="split-card__col split-card__col--dark">
                        <h3>We carry</h3>
                        <ul><li><x-icon name="check" /> CAD drafting</li><li><x-icon name="check" /> Revit production</li><li><x-icon name="check" /> Visualization</li><li><x-icon name="check" /> One named point of contact</li></ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ENGAGEMENTS (interactive) --}}
<section class="section section--alt" id="engagements" aria-labelledby="out-engage">
    <div class="wrap">
        <p class="eyebrow" data-reveal>Engagements</p>
        <h2 class="display-h" id="out-engage" data-split>Choose the Level <em>of Support You Need</em></h2>
        <div class="engage mt-4" data-reveal>
            <div class="engage__rail nav" role="tablist" aria-label="Engagement models" aria-orientation="vertical">
                @foreach ($engagements as $e)
                    <button class="engage__btn nav-link {{ $loop->first ? 'active' : '' }}" id="eng-tab-{{ $e['key'] }}" data-bs-toggle="tab" data-bs-target="#eng-{{ $e['key'] }}" type="button" role="tab" aria-controls="eng-{{ $e['key'] }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                        <span class="engage__n">0{{ $loop->iteration }}</span>
                        <span class="engage__t">{{ $e['title'] }}</span>
                        <x-icon name="arrow-right" />
                    </button>
                @endforeach
            </div>
            <div class="engage__panels tab-content">
                @foreach ($engagements as $e)
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="eng-{{ $e['key'] }}" role="tabpanel" aria-labelledby="eng-tab-{{ $e['key'] }}" tabindex="0">
                        <span class="engage__ic"><x-icon :name="$e['ic']" /></span>
                        <h3>{{ $e['title'] }}@if (! empty($e['badge'])) <span class="engage__badge">{{ $e['badge'] }}</span>@endif</h3>
                        <p class="engage__lead">{{ $e['text'] }}</p>
                        <p class="mono-note">Suited to: {{ $e['best'] }}.</p>
                        <dl class="gauges">
                            <div><dt>Scope size</dt><dd class="gauge" data-v="{{ $e['scope'] }}" aria-label="{{ $e['scope'] }} of 4">@for ($i = 1; $i <= 4; $i++)<i class="{{ $i <= $e['scope'] ? 'on' : '' }}"></i>@endfor</dd></div>
                            <div><dt>Ongoing commitment</dt><dd class="gauge" data-v="{{ $e['commit'] }}" aria-label="{{ $e['commit'] }} of 4">@for ($i = 1; $i <= 4; $i++)<i class="{{ $i <= $e['commit'] ? 'on' : '' }}"></i>@endfor</dd></div>
                        </dl>
                        <a class="link-arrow" href="{{ pu('contact', [], ['service' => 'outsourcing']) }}" data-topic="{{ $e['title'] }}">Discuss this option <x-icon name="arrow-right" /></a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- TIME ZONES (interactive) --}}
<section class="section section--dark" aria-labelledby="out-tz">
    <div class="process__glow" aria-hidden="true"></div>
    <div class="wrap">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5">
                <p class="eyebrow eyebrow--light" data-reveal>Across time zones</p>
                <h2 class="display-h display-h--light" id="out-tz" data-split>Work Can Continue <em>While Your Office Is Closed.</em></h2>
                <p class="lead-p" data-reveal>For US and Canadian firms, suitable and well-defined redline, drawing and model-update tasks can move forward during the time difference. Overnight delivery is agreed per assignment; larger or ambiguous scopes follow a confirmed project schedule.</p>
            </div>
            <div class="col-lg-7" data-reveal="zoom">
                <div class="tz" data-tz>
                    <div class="tz__top">
                        <label for="tzRegion">Your office</label>
                        <select id="tzRegion" data-tz-region>
                            <option value="-5">US / Canada Eastern (UTC−5)</option>
                            <option value="-6">US / Canada Central (UTC−6)</option>
                            <option value="-7">US / Canada Mountain (UTC−7)</option>
                            <option value="-8">US / Canada Pacific (UTC−8)</option>
                        </select>
                    </div>
                    <div class="tz__rows">
                        <div class="tz__row"><span class="tz__who">Your office <b data-tz-local>21:00</b></span><div class="tz__bar tz__bar--you" data-tz-bar="you" aria-hidden="true"></div></div>
                        <div class="tz__row"><span class="tz__who">Architive studio <b data-tz-studio>07:00</b></span><div class="tz__bar tz__bar--studio" data-tz-bar="studio" aria-hidden="true"></div></div>
                        <div class="tz__marker" data-tz-marker aria-hidden="true"></div>
                    </div>
                    <input type="range" min="0" max="23" value="21" step="1" data-tz-slider aria-label="Time of day in your office">
                    <p class="tz__status" data-tz-status aria-live="polite">Your office is closed. The studio is at the start of its working day.</p>
                    <p class="mono-note mono-note--light">Illustrative: a typical 09:00–17:00 day in your office and a 09:00–18:00 day in Pakistan (UTC+5), standard time. Daylight saving shifts the overlap by one hour.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- YOUR STANDARDS LOOP --}}
<section class="section" aria-labelledby="out-std">
    <div class="wrap">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5">
                <p class="eyebrow" data-reveal>Your standards</p>
                <h2 class="display-h" id="out-std" data-split>Brief Once. <em>Build Consistency Over Time.</em></h2>
                <p class="lead-p" data-reveal>Share your templates, sample sheets, Revit structure and naming conventions. We document what matters and apply it across future assignments, reducing repeated explanation as the relationship develops.</p>
            </div>
            <div class="col-lg-7">
                <ol class="loop">
                    @foreach ([['upload', 'You share', 'Templates, sample sheets, Revit structure and naming conventions.'], ['file-text', 'We document', 'What matters, written down and confirmed before production.'], ['refresh', 'We apply it', 'Across every future assignment—less repeated explanation each time.']] as [$ic, $t, $d])
                        <li data-reveal style="--d: {{ $loop->index * .15 }}s"><span class="loop__ic"><x-icon :name="$ic" /></span><div><h3>{{ $t }}</h3><p>{{ $d }}</p></div></li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="section section--alt" aria-labelledby="out-faq">
    <div class="wrap wrap--narrow">
        <p class="eyebrow eyebrow--center" data-reveal>Questions</p>
        <h2 class="display-h text-center" id="out-faq" data-split>Before You <em>Start</em></h2>
        @include('partials.faq-list', ['items' => Content::faqsById([2, 1, 5, 7]), 'uid' => 'outfaq'])
    </div>
</section>

@include('partials.cta-band', ['title' => 'Test Us on One Defined Task. <em>Start With a Pilot.</em>', 'cta' => 'Start with a pilot', 'ctaUrl' => pu('contact', [], ['service' => 'outsourcing'])])
@endsection

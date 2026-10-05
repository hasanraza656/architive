@extends('layouts.app')

@section('content')
@php
    use App\Support\Content;
    $collabs = Content::collaborations();
@endphp

@include('partials.page-hero', [
    'eyebrow' => 'Selected collaborations',
    'title' => 'Real Collaborations. Clear Responsibilities. <em>Verifiable Work.</em>',
    'lead' => "No vague portfolio captions. Each collaboration shows the client's situation, our exact role, the deliverables and how the work supported the wider team.",
    'image' => 'assets/img/photos/city-towers', 'imgPos' => '50% 50%',
])

<section class="section" aria-label="Collaborations">
    <div class="wrap">
        <div class="filters" role="group" aria-label="Filter collaborations" data-reveal>
            <button type="button" class="is-active" data-filter="all">All <b>3</b></button>
            <button type="button" data-filter="visualization">Visualization</button>
            <button type="button" data-filter="cad">CAD</button>
            <button type="button" data-filter="bim">BIM and Revit</button>
        </div>

        <div class="collab-list" data-filter-list>
            @foreach ($collabs as $slug => $c)
                <article class="collab-row" data-kind="{{ $c['key'] }}" data-reveal>
                    <div class="row g-4 g-lg-5 align-items-center">
                        <div class="col-lg-5">
                            @php $m = \App\Support\Portfolio::caseMedia($slug); @endphp
                            <a class="collab-card collab-card--big" href="{{ pu('collaborations.show', ['slug' => $slug]) }}" aria-label="{{ $c['title'] }}" data-spotlight>
                                <div class="collab-card__art {{ $m ? 'collab-card__art--photo' : '' }}">
                                    <div class="collab-card__row"><span>Project {{ $c['num'] }}</span><b class="tag-{{ $c['key'] }}">{{ $c['tag'] }}</b></div>
                                    @if ($m)
                                        <span class="collab-card__photo"><img src="{{ $m['cover']['thumb'] }}" width="{{ $m['cover']['tw'] }}" height="{{ $m['cover']['th'] }}" alt="{{ $m['alt'] }}" loading="lazy" decoding="async"></span>
                                    @else
                                        @include('partials.blueprint-card', ['kind' => $c['key']])
                                    @endif
                                    <div class="collab-card__row"><span>{{ $m ? $m['caption'] : 'Scale 1:100' }}</span><i class="collab-card__go"><x-icon name="arrow-up-right" /></i></div>
                                </div>
                            </a>
                        </div>
                        <div class="col-lg-7">
                            <p class="eyebrow">{{ $c['tag'] }} · {{ $c['place'] }}</p>
                            <h2 class="display-h display-h--sm"><a href="{{ pu('collaborations.show', ['slug' => $slug]) }}">{{ $c['title'] }}</a></h2>
                            <dl class="facts">
                                <div><dt>Client</dt><dd>{{ $c['client'] }}</dd></div>
                                <div><dt>Situation</dt><dd>{{ $c['situation'] }}</dd></div>
                                <div><dt>Our role</dt><dd>{{ $c['role'] }}</dd></div>
                                <div><dt>Deliverables</dt><dd>{{ implode(', ', $c['deliverables']) }}</dd></div>
                            </dl>
                            @if ($c['note'])<p class="callout callout--sm"><x-icon name="info" /><span>{{ $c['note'] }}</span></p>@endif
                            <a class="link-arrow" href="{{ pu('collaborations.show', ['slug' => $slug]) }}">Read the collaboration <x-icon name="arrow-right" /></a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

@include('partials.cta-band', ['title' => 'Have a Project <em>Like This One?</em>', 'cta' => 'Discuss a similar project', 'ctaUrl' => pu('contact')])
@endsection

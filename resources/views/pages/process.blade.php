@extends('layouts.app')

@section('content')
@php use App\Support\Content; @endphp

@include('partials.page-hero', [
    'eyebrow' => 'How it works',
    'title' => 'No Guesswork between <em>the Brief and the Final Files.</em>',
    'lead' => 'We define what is being produced, which standards apply, who approves it and when it is due—before production begins.',
    'cta' => 'Start your project', 'ctaUrl' => pu('contact'),
    'image' => 'assets/img/photos/cad-plan-pen', 'imgPos' => '50% 40%',
])

<section class="section" aria-labelledby="proc-steps">
    <div class="wrap">
        <div class="text-center mb-5">
            <p class="eyebrow eyebrow--center" data-reveal>Steps</p>
            <h2 class="display-h" id="proc-steps" data-split>Five <em>Steps</em></h2>
        </div>
        <ol class="vsteps" data-vsteps>
            @foreach (Content::process() as $s)
                <li class="vsteps__item" data-reveal="{{ $loop->odd ? 'left' : 'right' }}">
                    <span class="vsteps__node"><x-icon :name="$s['icon']" /></span>
                    <div class="vsteps__card">
                        <span class="vsteps__n">{{ $s['n'] }}</span>
                        <h3>{{ $s['title'] }}</h3>
                        <p>{{ $s['text'] }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</section>

<section class="section section--alt" aria-labelledby="proc-rev">
    <div class="wrap">
        <div class="row g-4">
            <div class="col-lg-7" data-reveal>
                <article class="feature-card">
                    <span class="feature-card__ic"><x-icon name="refresh" /></span>
                    <p class="eyebrow">Revisions</p>
                    <h2 class="display-h display-h--sm" id="proc-rev">Clear Feedback. <em>Fewer Surprises.</em></h2>
                    <p>Your quotation identifies review stages and included revision rounds. If Architive misses an agreed instruction or markup, we correct it at no charge. New design directions, changed source information or additional deliverables are quoted before the extra work begins.</p>
                </article>
            </div>
            <div class="col-lg-5" data-reveal style="--d:.12s">
                <article class="feature-card feature-card--dark">
                    <span class="feature-card__ic"><x-icon name="lock" /></span>
                    <p class="eyebrow eyebrow--light">Confidentiality</p>
                    <h2 class="display-h display-h--light display-h--sm">Your <em>Project Information</em></h2>
                    <p>We can sign an NDA before detailed files are shared. Project access is limited to the assigned team, and the agreed transfer and storage method is confirmed before production.</p>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="section" aria-labelledby="proc-faq">
    <div class="wrap wrap--narrow">
        <p class="eyebrow eyebrow--center" data-reveal>Questions</p>
        <h2 class="display-h text-center" id="proc-faq" data-split>Before You <em>Start</em></h2>
        @include('partials.faq-list', ['items' => Content::faqsById([6, 5, 7, 2]), 'uid' => 'procfaq'])
    </div>
</section>

@include('partials.cta-band', ['title' => 'Ready to Share <em>the Brief?</em>'])
@endsection

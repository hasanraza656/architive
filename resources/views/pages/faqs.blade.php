@extends('layouts.app')

@section('content')
@php use App\Support\Content; @endphp

@include('partials.page-hero', [
    'eyebrow' => 'Frequently asked questions',
    'title' => 'Questions Buyers Ask <em>Before Starting.</em>',
    'lead' => 'Clear answers regarding office standards, small trial pilots, architectural design boundaries, permit packages, pricing and revisions.',
    'image' => 'assets/img/photos/bim-grid-facade', 'imgPos' => '50% 50%',
])

<section class="section" aria-label="FAQs">
    <div class="wrap wrap--narrow">
        <h2 class="visually-hidden">All questions</h2>
        @include('partials.faq-toolbar')
        @include('partials.faq-list', ['items' => Content::faqs(), 'uid' => 'allfaq'])
        <div class="callout-panel callout-panel--sm mt-5" data-reveal>
            <span class="callout-panel__ic"><x-icon name="message" /></span>
            <div>
                <h2 class="display-h display-h--sm mb-2">Still have a question?</h2>
                <p class="mb-3">Tell us about your project—we will reply with a practical next step.</p>
                <a class="btn-ay btn-ay--sm" href="{{ pu('contact') }}">Contact us <x-icon name="arrow-right" /></a>
            </div>
        </div>
    </div>
</section>

@include('partials.cta-band')
@endsection

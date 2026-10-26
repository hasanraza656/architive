@extends('layouts.app')
{{-- Thank-you page after an ad landing page form. The lead conversion is sent on the form page before arriving here,
     so reloading this page never counts twice. $ref = request number (optional). --}}
@push('head')
    <link rel="stylesheet" href="{{ asset_v('assets/css/landing.css') }}">
@endpush

@section('content')
<section class="lp-thanks">
    <div class="wrap wrap--narrow text-center">
        <span class="lp-thanks__ic"><x-icon name="check" /></span>
        <h1 class="display-h">Your trial request <em>is in.</em></h1>
        @if ($ref)
            <p class="lp-thanks__ref">Request <b>{{ $ref }}</b></p>
        @endif
        <p class="lead-p mx-auto" style="max-width: 56ch">We have e-mailed you a confirmation. Here is what happens next:</p>
        <ol class="lp-thanks__steps">
            <li><b>Within one business day</b> we read your files and reply with what the free trial will cover and when you will see it, or ask for anything missing.</li>
            <li><b>During the trial</b> you get real work on your own project, with one contact person for questions.</li>
            <li><b>After you have seen it</b> you decide. If you continue, you get a written quote before any paid work starts.</li>
        </ol>
        <div class="lp-thanks__actions">
            <a class="btn-ay" href="https://wa.me/{{ ltrim(config('site.phone'), '+') }}" target="_blank" rel="noopener">Message us on WhatsApp</a>
            <a class="btn-ay btn-ay--ghost" href="{{ route('customer.login') }}" rel="nofollow">Follow my request</a>
        </div>
        <p class="note-line mt-4">While you wait: see our <a href="{{ pu('collaborations.index') }}">collaborations</a> or <a href="{{ pu('process') }}">how we work</a>.</p>
    </div>
</section>
@endsection

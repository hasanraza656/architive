@extends('layouts.app')

@section('content')
@php
    $captchaKey = config('services.recaptcha.site_key');
    $selService  = old('service', request('service'));
    $selAudience = old('audience', request('audience'));
    $services = ['visualization' => ['Visualization', 'eye'], 'bim' => ['BIM and Revit', 'box'], 'cad' => ['CAD drafting', 'file-text'], 'outsourcing' => ['Production support', 'users'], 'unsure' => ['Not sure yet', 'message']];
    $audiences = ['firm' => 'Architecture firm', 'interior' => 'Interior design studio', 'developer' => 'Developer / contractor', 'homeowner' => 'Homeowner'];
@endphp

<section class="contact-top">
    <div class="wrap">
        @include('partials.breadcrumbs')
        <div class="contact-intro">
            <p class="eyebrow" data-reveal>Contact</p>
            <h1 class="display-h" data-split>Your Next Deadline Does Not Need <em>Another Rushed Hire.</em></h1>
            <p class="lead-p mb-0" data-reveal style="--d:.15s">Share the files, deadline and outcome you need. We will review the information and recommend a practical scope during a free consultation.</p>
        </div>
    </div>
</section>

<section class="section section--tight-top" aria-label="Start your project">
    <div class="wrap">
        <div class="row g-5">
            <div class="col-lg-7">
                <div class="enquiry-card" id="enquiry" data-reveal="zoom">
                    @if (session('sent'))
                        <div class="enquiry-success is-shown" role="status">
                            <span class="enquiry-success__ic"><x-icon name="check" /></span>
                            <h2 class="display-h display-h--sm">Message received.</h2>
                            <p>{{ session('sent') }}</p>
                            <a class="btn-ay" href="{{ pu('home') }}">Back to home <x-icon name="arrow-right" /></a>
                        </div>
                    @endif

                    <form action="{{ route('contact.send') }}" method="post" novalidate data-contact-form @if(session('sent')) hidden @endif>
                        @csrf
                        <div class="hp" aria-hidden="true"><label>Leave this field empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                        <h2 class="display-h display-h--sm">Start your project</h2>
                        <p class="mono-note mt-0">Start with a free consultation or a small paid pilot. No commitment until you approve the scope.</p>

                        <fieldset class="f-group">
                            <legend>I am a…</legend>
                            <div class="pills">
                                @foreach ($audiences as $k => $label)
                                    <label class="pill"><input type="radio" name="audience" value="{{ $k }}" @checked($selAudience === $k)><span>{{ $label }}</span></label>
                                @endforeach
                            </div>
                        </fieldset>

                        <fieldset class="f-group">
                            <legend>I need help with</legend>
                            <div class="opt-grid">
                                @foreach ($services as $k => [$label, $ic])
                                    <label class="opt"><input type="radio" name="service" value="{{ $k }}" @checked($selService === $k)><span><x-icon :name="$ic" />{{ $label }}</span></label>
                                @endforeach
                            </div>
                        </fieldset>

                        <div class="row g-3">
                            <div class="col-md-6 f-field">
                                <label for="f-name">Your name <b>*</b></label>
                                <input id="f-name" name="name" type="text" autocomplete="name" required maxlength="120" value="{{ old('name') }}" @error('name') aria-invalid="true" @enderror>
                                <p class="f-err" data-err-for="name">@error('name'){{ $message }}@enderror</p>
                            </div>
                            <div class="col-md-6 f-field">
                                <label for="f-email">Email <b>*</b></label>
                                <input id="f-email" name="email" type="email" autocomplete="email" required maxlength="160" value="{{ old('email') }}" @error('email') aria-invalid="true" @enderror>
                                <p class="f-err" data-err-for="email">@error('email'){{ $message }}@enderror</p>
                            </div>
                            <div class="col-md-6 f-field">
                                <label for="f-company">Company / studio</label>
                                <input id="f-company" name="company" type="text" autocomplete="organization" maxlength="160" value="{{ old('company') }}">
                            </div>
                            <div class="col-md-6 f-field">
                                <label for="f-deadline">Target deadline</label>
                                <input id="f-deadline" name="deadline" type="date" value="{{ old('deadline') }}">
                            </div>
                            <div class="col-12 f-field">
                                <label for="f-links">Links to files, plans or references</label>
                                <textarea id="f-links" name="links" rows="3" maxlength="1000" placeholder="Share links to drawings, models or references. We'll agree a secure transfer method before detailed files are shared.">{{ old('links') }}</textarea>
                            </div>
                            <div class="col-12 f-field">
                                <label for="f-message">Project details and the outcome you need <b>*</b></label>
                                <textarea id="f-message" name="message" rows="5" required minlength="10" maxlength="4000" @error('message') aria-invalid="true" @enderror>{{ old('message') }}</textarea>
                                <p class="f-err" data-err-for="message">@error('message'){{ $message }}@enderror</p>
                            </div>
                        </div>

                        <label class="check"><input type="checkbox" name="nda" value="1" @checked(old('nda') || request('nda'))><span>Please send a mutual NDA before I share detailed files.</span></label>
                        <label class="check"><input type="checkbox" name="consent" value="1" required @checked(old('consent'))><span>I agree to be contacted about this enquiry. See our <a href="{{ pu('privacy') }}">Privacy Policy</a>. <b>*</b></span></label>
                        <p class="f-err" data-err-for="consent">@error('consent'){{ $message }}@enderror</p>

                        @if ($captchaKey)
                            <div class="captcha">
                                <div data-captcha data-sitekey="{{ $captchaKey }}" aria-label="Security check"></div>
                                <p class="f-err" data-err-for="captcha">@error('g-recaptcha-response'){{ $message }}@enderror</p>
                            </div>
                        @endif

                        <button class="btn-ay btn-ay--lg btn-ay--block" type="submit" data-submit><span class="btn-label">Send enquiry</span> <x-icon name="send" /><span class="spinner" aria-hidden="true"></span></button>
                        <p class="f-global" role="alert" data-form-error @unless ($errors->has('mail')) hidden @endunless>{{ $errors->first('mail') }}</p>
                    </form>

                    <div class="enquiry-success" data-success hidden role="status">
                        <span class="enquiry-success__ic"><x-icon name="check" /></span>
                        <h2 class="display-h display-h--sm">Message received.</h2>
                        <p data-success-text>Thank you—your enquiry is in. We will review the information and reply with a practical next step.</p>
                        <a class="btn-ay" href="{{ pu('home') }}">Back to home <x-icon name="arrow-right" /></a>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="contact-aside">
                    <h2 class="eyebrow" data-reveal>What happens next</h2>
                    <ol class="next-steps">
                        @foreach ([['Send the details', 'Tell us about the project, deadline and the outcome you need.'], ['We review and reply', 'We review the information and recommend a practical scope.'], ['Free consultation or paid pilot', 'No commitment until you approve the written scope.']] as [$t, $d])
                            <li data-reveal style="--d: {{ $loop->index * .1 }}s"><span>{{ $loop->iteration }}</span><div><h3>{{ $t }}</h3><p>{{ $d }}</p></div></li>
                        @endforeach
                    </ol>

                    <ul class="contact-list" data-reveal>
                        <li><x-icon name="mail" /><div><small>Email</small><a href="mailto:{{ config('site.email') }}">{{ config('site.email') }}</a></div></li>
                        @foreach (config('site.address') as $a)
                            <li><x-icon name="map-pin" /><div><small>{{ $a['label'] }}</small><span>{{ $a['value'] }}</span></div></li>
                        @endforeach
                        <li><x-icon name="lock" /><div><small>Confidentiality</small><span>Mutual NDA available before detailed files are shared</span></div></li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</section>
@endsection

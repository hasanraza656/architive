{{-- Short trial form for ad landing pages. Same endpoint and validation as the contact form; on success interactive.js
     fires the lead conversion and continues to /thank-you/ (data-thanks). Props: $p (page config), $slug. --}}
@php $captchaKey = config('services.recaptcha.site_key'); @endphp
<div class="enquiry-card lp-form" id="trial">
    <form action="{{ route('contact.send') }}" method="post" enctype="multipart/form-data" novalidate data-contact-form
          data-thanks="{{ pu('thanks') }}" data-form-name="lp-{{ $slug }}">
        @csrf
        <div class="hp" aria-hidden="true"><label>Leave this field empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
        <input type="hidden" name="service" value="{{ $p['service'] }}">
        <input type="hidden" name="topic" value="{{ $p['topic'] }} (free trial)">
        <input type="hidden" name="lp" value="{{ $slug }}">

        <p class="lp-form__badge"><x-icon name="sparkles" /> Free 3-day trial</p>
        <h2 class="display-h display-h--sm">{{ $p['formTitle'] }}</h2>
        <p class="lp-form__sub">We reply within one business day with what the trial will cover.</p>

        <div class="f-field">
            <label for="lp-name">Your name <b>*</b></label>
            <input id="lp-name" name="name" type="text" autocomplete="name" required maxlength="120">
            <p class="f-err" data-err-for="name"></p>
        </div>
        <div class="f-field">
            <label for="lp-email">Work email <b>*</b></label>
            <input id="lp-email" name="email" type="email" autocomplete="email" required maxlength="160">
            <p class="f-err" data-err-for="email"></p>
        </div>
        <div class="f-field">
            <label for="lp-company">Company <span class="opt-tag">optional</span></label>
            <input id="lp-company" name="company" type="text" autocomplete="organization" maxlength="160">
        </div>
        <div class="f-field">
            <label for="lp-message">What do you need? <b>*</b></label>
            <textarea id="lp-message" name="message" rows="3" required minlength="10" maxlength="4000" placeholder="{{ $p['placeholder'] }}"></textarea>
            <p class="f-err" data-err-for="message"></p>
        </div>

        <div class="f-field f-files">
            <label class="f-drop f-drop--sm" data-files-drop>
                <x-icon name="upload" />
                <span><b>Attach files</b> (optional)</span>
                <small>Up to 5 files · 10 MB each</small>
                <input type="file" name="files[]" multiple data-files-input>
            </label>
            <ul class="f-filelist" data-files-list></ul>
            <p class="f-err" data-err-for="files"></p>
        </div>

        <label class="check"><input type="checkbox" name="consent" value="1" required><span>I agree to be contacted about this enquiry. <a href="{{ pu('privacy') }}">Privacy Policy</a> <b>*</b></span></label>
        <p class="f-err" data-err-for="consent"></p>

        @if ($captchaKey)
            <div class="captcha">
                <div data-captcha data-sitekey="{{ $captchaKey }}" aria-label="Security check"></div>
                <p class="f-err" data-err-for="captcha"></p>
            </div>
        @endif

        <button class="btn-ay btn-ay--lg btn-ay--block" type="submit" data-submit><span class="btn-label">Start my free trial</span> <x-icon name="arrow-right" /><span class="spinner" aria-hidden="true"></span></button>
        <p class="f-global" role="alert" data-form-error hidden></p>
        <p class="lp-form__fine"><x-icon name="lock" /> No card, no contract. NDA available on request.</p>
    </form>
    <div data-success hidden><p data-success-text></p></div>
</div>

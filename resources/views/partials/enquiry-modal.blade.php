{{-- Quick enquiry pop-up. Opens from any "Start your project" / "Let's talk" link (see app.js) so the visitor lands on the form immediately.
     Without JavaScript those links still go to /contact/. Not rendered on the contact page, which already has the full form. --}}
@php
    $captchaKey = config('services.recaptcha.site_key');
    $svcOptions = ['visualization' => ['Visualization', 'eye'], 'bim' => ['BIM and Revit', 'box'], 'cad' => ['CAD drafting', 'file-text'], 'outsourcing' => ['Ongoing production support', 'users'], 'unsure' => ['Not sure yet', 'message']];
@endphp
<div class="modal fade enquiry-modal" id="enquiryModal" tabindex="-1" aria-labelledby="enquiryModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <button type="button" class="enquiry-modal__close" data-bs-dismiss="modal" aria-label="Close enquiry form"><x-icon name="x" /></button>
            <div class="enquiry-card enquiry-card--modal">
                <form action="{{ route('contact.send') }}" method="post" enctype="multipart/form-data" novalidate data-contact-form>
                    @csrf
                    <div class="hp" aria-hidden="true"><label>Leave this field empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                    <input type="hidden" name="topic" value="">
                    <input type="hidden" name="audience" value="">

                    <h2 class="display-h display-h--sm" id="enquiryModalTitle">Start your project</h2>
                    <p class="mono-note mt-0">Free consultation and free start. No commitment until you approve the scope.</p>
                    <p class="enquiry-context" data-enquiry-context hidden></p>

                    <fieldset class="f-group f-group--tight">
                        <legend>I need help with</legend>
                        <div class="opt-grid opt-grid--sm">
                            @foreach ($svcOptions as $k => [$label, $ic])
                                <label class="opt"><input type="radio" name="service" value="{{ $k }}"><span><x-icon :name="$ic" />{{ $label }}</span></label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div class="row mt-4">
                        <div class="col-sm-6 f-field">
                            <label for="m-name">Your name <b>*</b></label>
                            <input id="m-name" name="name" type="text" autocomplete="name" required maxlength="120">
                            <p class="f-err" data-err-for="name"></p>
                        </div>
                        <div class="col-sm-6 f-field">
                            <label for="m-email">Email <b>*</b></label>
                            <input id="m-email" name="email" type="email" autocomplete="email" required maxlength="160">
                            <p class="f-err" data-err-for="email"></p>
                        </div>
                        <div class="col-12 f-field">
                            <label for="m-company">Company / studio <span class="opt-tag">optional</span></label>
                            <input id="m-company" name="company" type="text" autocomplete="organization" maxlength="160">
                        </div>
                        <div class="col-12 f-field">
                            <label for="m-message">Project details and the outcome you need <b>*</b></label>
                            <textarea id="m-message" name="message" rows="3" required minlength="10" maxlength="4000" placeholder="Tell us what you have (sketches, PDFs, CAD files, a model) and what you need back."></textarea>
                            <p class="f-err" data-err-for="message"></p>
                        </div>
                    </div>

                    <div class="f-field f-files mt-3">
                    <label>Attach files <span class="opt-tag">optional</span></label>
                    <label class="f-drop" data-files-drop>
                        <x-icon name="upload" />
                        <span><b>Drop files here</b> or browse</span>
                        <small>Up to 5 files · 10 MB each · PDF, DWG, RVT, images, ZIP</small>
                        <input type="file" name="files[]" multiple data-files-input>
                    </label>
                    <ul class="f-filelist" data-files-list></ul>
                    <p class="f-err" data-err-for="files"></p>
                        </div>

                    <label class="check"><input type="checkbox" name="nda" value="1"><span>Please send a mutual NDA before I share detailed files.</span></label>
                    <label class="check"><input type="checkbox" name="consent" value="1" required><span>I agree to be contacted about this enquiry. See our <a href="{{ pu('privacy') }}">Privacy Policy</a>. <b>*</b></span></label>
                    <p class="f-err" data-err-for="consent"></p>

                    @if ($captchaKey)
                        <div class="captcha">
                            <div data-captcha data-sitekey="{{ $captchaKey }}" aria-label="Security check"></div>
                            <p class="f-err" data-err-for="captcha"></p>
                        </div>
                    @endif

                    <button class="btn-ay btn-ay--lg btn-ay--block" type="submit" data-submit><span class="btn-label">Send enquiry</span> <x-icon name="send" /><span class="spinner" aria-hidden="true"></span></button>
                    <p class="f-global" role="alert" data-form-error hidden></p>
                    <p class="enquiry-alt">Prefer the full form? <a href="{{ pu('contact') }}" data-no-modal>Open the contact page</a>.</p>
                </form>

                <div class="enquiry-success" data-success hidden role="status">
                    <span class="enquiry-success__ic"><x-icon name="check" /></span>
                    <h2 class="display-h display-h--sm">Message received.</h2>
                    <p data-success-text>Thank you—your enquiry is in. We will review the information and reply with a practical next step.</p>
                    <p class="enquiry-success__ref" data-success-ref hidden></p>
                    <div class="enquiry-success__next" data-success-next hidden>
                        <b>What happens next:</b> we review your brief and reply in your client area, then send a custom offer. We have e-mailed you a link. No password needed, just your e-mail.
                    </div>
                    <div class="enquiry-success__actions">
                        <a class="btn-ay" data-success-portal href="{{ route('customer.login') }}" hidden>Follow my request <x-icon name="arrow-right" /></a>
                        <button type="button" class="btn-ay btn-ay--ghost" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

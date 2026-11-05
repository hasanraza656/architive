@extends('layouts.app')

@push('head')
    <link rel="stylesheet" href="{{ asset_v('assets/css/permit.css') }}">
@endpush

@section('content')
<section class="pr-top">
    <div class="wrap pr-narrow">
        @include('partials.breadcrumbs')
        <p class="eyebrow" data-reveal>CAD drafting and permit support</p>
        <h1 class="display-h" data-split>Permit drawing revisions, <em>made simple.</em></h1>
        <p class="lead-p" data-reveal style="--d:.15s">Tell us what the city asked for. Upload your drawings and comment letter, answer a few quick questions and see an estimated cost. A written quotation follows once we have reviewed your files.</p>
    </div>
</section>

<section class="pr-main" aria-label="Permit revisions enquiry">
    <div class="wrap pr-narrow">
        <div class="pr-progress" id="pr-progress" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div>
        <div class="pr-stepname" id="pr-stepname" aria-live="polite"></div>

        <form id="pr-form" class="pr-card" action="{{ route('permit-revisions.send') }}" method="post" enctype="multipart/form-data" novalidate>
            @csrf
            <div class="hp" aria-hidden="true"><label>Leave this field empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

            {{-- STEP 1 --}}
            <section data-step="1">
                <h2 class="pr-h">What do you need?</h2>
                <p class="pr-sub">Pick the closest match. You can add detail later.</p>
                <fieldset class="pr-fs">
                    <legend class="pr-hide">Service needed</legend>
                    <div class="pr-opts">
                        <label class="pr-opt"><input type="radio" name="need" value="fix_comments"><span>Fix city or plan-check comments<small>Revise drawings to clear a correction letter</small></span></label>
                        <label class="pr-opt"><input type="radio" name="need" value="design_change"><span>Revise for a design change<small>Owner, engineer or scope changes</small></span></label>
                        <label class="pr-opt"><input type="radio" name="need" value="new_set"><span>Prepare a new permit drawing set<small>Quoted after review</small></span></label>
                        <label class="pr-opt"><input type="radio" name="need" value="unsure"><span>Not sure, review my set<small>We will look and advise</small></span></label>
                    </div>
                </fieldset>
                <p class="pr-err" id="pr-err1" role="alert"></p>
            </section>

            {{-- STEP 2 --}}
            <section data-step="2" hidden>
                <h2 class="pr-h">About the project</h2>
                <p class="pr-sub">This helps us match local requirements.</p>
                <fieldset class="pr-fs">
                    <legend class="pr-l">Project type</legend>
                    <div class="pr-opts">
                        <label class="pr-opt"><input type="radio" name="ptype" value="residential"><span>Residential</span></label>
                        <label class="pr-opt"><input type="radio" name="ptype" value="adu"><span>ADU / guest house</span></label>
                        <label class="pr-opt"><input type="radio" name="ptype" value="commercial"><span>Commercial</span></label>
                        <label class="pr-opt"><input type="radio" name="ptype" value="ti"><span>Tenant improvement</span></label>
                        <label class="pr-opt"><input type="radio" name="ptype" value="other"><span>Other</span></label>
                    </div>
                </fieldset>
                <fieldset class="pr-fs">
                    <legend class="pr-l">Scope of work</legend>
                    <div class="pr-opts">
                        <label class="pr-opt"><input type="radio" name="scope" value="new"><span>New build</span></label>
                        <label class="pr-opt"><input type="radio" name="scope" value="addition"><span>Addition</span></label>
                        <label class="pr-opt"><input type="radio" name="scope" value="remodel"><span>Remodel / interior</span></label>
                        <label class="pr-opt"><input type="radio" name="scope" value="change_use"><span>Change of use</span></label>
                    </div>
                </fieldset>
                <div class="pr-row">
                    <div><label class="pr-l" for="pr-country">Country</label>
                        <select id="pr-country" name="country" class="pr-input">
                            <option value="">Select</option>
                            @foreach ($countries as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                        </select></div>
                    <div><label class="pr-l" for="pr-state">State / province <span class="pr-hint">(optional)</span></label>
                        <input type="text" id="pr-state" name="state" class="pr-input" maxlength="80" placeholder="e.g. California" autocomplete="off"></div>
                </div>
                <div class="pr-gap"><label class="pr-l" for="pr-authority">City or county that issues the permit</label>
                    <input type="text" id="pr-authority" name="authority" class="pr-input" maxlength="120" placeholder="e.g. City of La Verne" autocomplete="off"></div>
                <p class="pr-err" id="pr-err2" role="alert"></p>
            </section>

            {{-- STEP 3 --}}
            <section data-step="3" hidden>
                <h2 class="pr-h">Upload your files</h2>
                <p class="pr-sub">Up to {{ $cfg['max_files'] }} files, {{ $cfg['max_file_mb'] }} MB each. PDF, DWG, RVT, images or ZIP. We check each file as you add it.</p>
                <label class="pr-drop" id="pr-drop" for="pr-filein">
                    <b>Drop files here or browse</b>
                    <small>Drawing set and city comment letter</small>
                    <input type="file" id="pr-filein" multiple accept=".pdf,.dwg,.rvt,.zip,.png,.jpg,.jpeg,.webp,.heic">
                </label>
                <ul class="pr-files" id="pr-filelist"></ul>
                <p class="pr-err" id="pr-err3" role="alert"></p>
                <div class="pr-note" id="pr-filetip"></div>
                <label class="pr-check"><input type="checkbox" id="pr-nocomment"><span>I do not have the comment letter yet</span></label>
            </section>

            {{-- STEP 4 --}}
            <section data-step="4" hidden>
                <h2 class="pr-h">A few quick questions</h2>
                <p class="pr-sub">These decide how we scope and estimate the work.</p>
                <div class="pr-row">
                    <div><label class="pr-l" for="pr-sheets">Sheets in the drawing set</label>
                        <input type="number" id="pr-sheets" name="sheets" class="pr-input" min="1" max="500" inputmode="numeric" placeholder="e.g. 12"></div>
                    <div id="pr-commentsbox"><label class="pr-l" for="pr-comments">Number of city comments</label>
                        <input type="number" id="pr-comments" name="comments" class="pr-input" min="0" max="500" inputmode="numeric" placeholder="e.g. 18"></div>
                </div>
                <fieldset class="pr-fs pr-gap-lg">
                    <legend class="pr-l">Which areas do the comments or changes touch? <span class="pr-hint">(tick all that apply)</span></legend>
                    <label class="pr-check"><input type="checkbox" name="areas[]" value="structural"><span>Structural (needs engineer input)</span></label>
                    <label class="pr-check"><input type="checkbox" name="areas[]" value="mep"><span>Mechanical / electrical / plumbing</span></label>
                    <label class="pr-check"><input type="checkbox" name="areas[]" value="energy"><span>Energy compliance (e.g. Title 24, REScheck)</span></label>
                    <label class="pr-check"><input type="checkbox" name="areas[]" value="zoning"><span>Zoning, setbacks, site plan</span></label>
                    <label class="pr-check"><input type="checkbox" name="areas[]" value="fire"><span>Fire, egress or accessibility</span></label>
                    <label class="pr-check"><input type="checkbox" name="areas[]" value="drafting"><span>Mostly drafting: notes, dimensions, details</span></label>
                </fieldset>
                <fieldset class="pr-fs">
                    <legend class="pr-l">What format are the original files?</legend>
                    <div class="pr-opts">
                        <label class="pr-opt"><input type="radio" name="format" value="native"><span>DWG or Revit<small>Editable originals</small></span></label>
                        <label class="pr-opt"><input type="radio" name="format" value="pdf"><span>PDF only<small>Drawings need rebuilding</small></span></label>
                        <label class="pr-opt"><input type="radio" name="format" value="scan"><span>Scans or photos<small>Hand drawn or old plans</small></span></label>
                    </div>
                </fieldset>
                <div class="pr-row">
                    <div><label class="pr-l" for="pr-deadline">Resubmission deadline <span class="pr-hint">(optional)</span></label>
                        <input type="date" id="pr-deadline" name="deadline" class="pr-input"></div>
                    <div><label class="pr-l" for="pr-seal">Stamped or sealed drawings?</label>
                        <select id="pr-seal" name="seal" class="pr-input">
                            <option value="">Select</option>
                            <option value="no">No</option>
                            <option value="have">Yes, I have a licensed professional</option>
                            <option value="need">Yes, I still need one</option>
                        </select></div>
                </div>
                <label class="pr-check pr-gap"><input type="checkbox" id="pr-rush"><span>I need this urgently. Please tell me what turnaround is possible.</span></label>
                <div class="pr-gap"><label class="pr-l" for="pr-details">Anything else we should know? <span class="pr-hint">(optional)</span></label>
                    <textarea id="pr-details" name="details" class="pr-input" maxlength="2000" rows="4" placeholder="Special conditions, tricky comments, preferred software..."></textarea></div>
                <p class="pr-err" id="pr-err4" role="alert"></p>
            </section>

            {{-- STEP 5 --}}
            <section data-step="5" hidden>
                <h2 class="pr-h">Your estimate</h2>
                <p class="pr-sub" id="pr-estsub"></p>
                <div id="pr-estbox"></div>

                <div id="pr-contactbox">
                    <div class="pr-row">
                        <div><label class="pr-l" for="pr-name">Your name</label><input type="text" id="pr-name" name="name" class="pr-input" maxlength="120" autocomplete="name"></div>
                        <div><label class="pr-l" for="pr-email">Email</label><input type="email" id="pr-email" name="email" class="pr-input" maxlength="160" autocomplete="email"></div>
                    </div>
                    <div class="pr-gap"><label class="pr-l" for="pr-company">Company / studio <span class="pr-hint">(optional)</span></label>
                        <input type="text" id="pr-company" name="company" class="pr-input" maxlength="160" autocomplete="organization"></div>
                    <label class="pr-check pr-gap"><input type="checkbox" id="pr-nda" name="nda" value="1"><span>Please send a mutual NDA before I share detailed files.</span></label>
                    <label class="pr-check"><input type="checkbox" id="pr-consent" name="consent" value="1"><span>I agree to be contacted about this enquiry and to Architive storing my files to prepare a quotation. See the <a href="{{ pu('privacy') }}">Privacy Policy</a>.</span></label>
                    @if ($captchaKey)
                        <div class="captcha pr-captcha">
                            <div data-captcha data-sitekey="{{ $captchaKey }}" aria-label="Security check"></div>
                            <p class="pr-err" data-err-for="captcha" role="alert"></p>
                        </div>
                    @endif
                    <p class="pr-err" id="pr-err5" role="alert"></p>
                </div>
            </section>

            {{-- DONE --}}
            <section id="pr-done" class="pr-done" hidden>
                <div class="pr-tick" aria-hidden="true">✓</div>
                <h2 class="pr-h" id="pr-donetitle">Enquiry sent</h2>
                <p class="pr-sub" id="pr-donemsg"></p>
                <pre class="pr-sum" id="pr-sumtxt"></pre>
                <div class="pr-doneact">
                    <a class="btn-ay" id="pr-portal" href="{{ route('customer.login') }}" hidden>Follow my request <x-icon name="arrow-right" /></a>
                    <a class="btn-ay" id="pr-mailbtn" href="#" hidden>Email this to Architive</a>
                    <button type="button" class="btn-ay btn-ay--ghost" id="pr-copybtn" hidden>Copy summary</button>
                    <a class="btn-ay btn-ay--ghost" href="{{ pu('home') }}">Back to home</a>
                </div>
            </section>

            <div class="pr-nav" id="pr-nav">
                <button type="button" class="btn-ay btn-ay--ghost" id="pr-back">Back</button>
                <button type="button" class="btn-ay" id="pr-next">Continue</button>
            </div>
        </form>

        <p class="pr-foot">Estimates are indicative and based on our introductory rate of {{ $cfg['currency'] }}{{ rtrim(rtrim(number_format($cfg['rate'], 2), '0'), '.') }} per hour. Your written quotation confirms scope, price, turnaround, revision rounds and delivery date before any work starts. Where local law requires a licence, signature or seal, the client appoints the appropriate professional. Your files are stored privately and reviewed by our team; see our <a href="{{ pu('privacy') }}">Privacy Policy</a>.</p>
    </div>
</section>

<script type="application/json" id="pr-config">@json($cfg)</script>
@endsection

@push('scripts')
    <script src="{{ asset_v('assets/js/permit.js') }}" defer></script>
@endpush

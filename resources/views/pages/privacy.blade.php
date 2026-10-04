@extends('layouts.app')

@section('content')
<section class="legal-hero"><div class="wrap wrap--narrow">
    @include('partials.breadcrumbs')
    <p class="eyebrow">Legal</p>
    <h1 class="display-h">Privacy <em>Policy</em></h1>
    <p class="mono-note">Last updated: October 2026</p>
</div></section>

<section class="section legal">
    <div class="wrap wrap--narrow">
        <p class="lead-p">{{ config('site.legal_name') }} ("Architive", "we", "us") respects your privacy. This policy explains what information we collect through this website, how we use it and the choices you have.</p>

        <h2>Information we collect</h2>
        <p>We collect only what you choose to send us: your name, email address, company, role, project details, target deadline and any links to files or references you include in the enquiry form or in an email to {{ config('site.email') }}.</p>

        <h2>How we use it</h2>
        <ul>
            <li>To reply to your enquiry and recommend a practical scope.</li>
            <li>To prepare a written quotation and, if you proceed, to carry out the agreed work.</li>
            <li>To keep a record of our communication with you.</li>
        </ul>
        <p>We do not sell your personal information.</p>

        <h2>Project files and confidentiality</h2>
        <p>Detailed project files are shared only through a transfer method confirmed with you. If you ask for it, we will review and sign a mutual NDA before detailed project information is shared. Project access is limited to the assigned team.</p>

        <h2>Cookies and local storage</h2>
        <p>This website stores a single preference in your browser's local storage—your light or dark theme choice—so the site looks the way you left it. It is not used to identify or track you. If we add analytics or marketing tools in future, we will update this policy and, where required, ask for consent.</p>

        <h2>Sharing</h2>
        <p>We share information only with the people and service providers who need it to respond to you or deliver the agreed work, and where required by law.</p>

        <h2>Retention</h2>
        <p>We keep enquiry and project records for as long as needed to respond, deliver the work and meet legal or accounting obligations, then delete or anonymize them.</p>

        <h2>Your choices</h2>
        <p>You may ask us to access, correct or delete the personal information we hold about you by emailing <a href="mailto:{{ config('site.email') }}">{{ config('site.email') }}</a>.</p>

        <h2>Contact</h2>
        <p>{{ config('site.legal_name') }}, Newark, Delaware, USA · <a href="mailto:{{ config('site.email') }}">{{ config('site.email') }}</a></p>
    </div>
</section>
@endsection

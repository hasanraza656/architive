@extends('layouts.app')

@section('content')
<section class="legal-hero"><div class="wrap wrap--narrow">
    @include('partials.breadcrumbs')
    <p class="eyebrow">Legal</p>
    <h1 class="display-h">Terms of <em>Service</em></h1>
    <p class="mono-note">Last updated: October 2026</p>
</div></section>

<section class="section legal">
    <div class="wrap wrap--narrow">
        <p class="lead-p">These terms apply to your use of the {{ config('site.name') }} website and to project enquiries made through it. Paid project work is governed by the written scope and quotation you approve.</p>

        <h2>Using this website</h2>
        <p>The information on this site is provided for general information about Architive's services. You agree to use the site lawfully and not to attempt to disrupt it or access it in unauthorized ways.</p>

        <h2>Enquiries are not contracts</h2>
        <p>Submitting an enquiry or receiving a consultation creates no obligation for either party. Work begins only after you approve a written scope, schedule, quotation and payment arrangement.</p>

        <h2>Scope of services</h2>
        <p>Architive provides architectural production support—visualization, BIM and Revit, and CAD drafting—based on approved designs and the information supplied to us. Any design responsibility is agreed explicitly in writing. We do not provide engineering services or on-site scanning unless explicitly agreed.</p>

        <h2>Licensed professionals and permits</h2>
        <p>We prepare permit-support drawing packages from the information and local requirements provided. Where local law requires drawings to be reviewed, signed or sealed, the client appoints the locally licensed architect, engineer or other authorized professional. Architive does not stamp or seal drawings on behalf of third parties.</p>

        <h2>Revisions</h2>
        <p>Included review stages and revision rounds are stated in each quotation. Corrections to missed agreed instructions are completed at no charge; new design directions, changed source information or additional deliverables are quoted before extra work begins.</p>

        <h2>Intellectual property</h2>
        <p>The content of this website—including text, graphics and the Architive name and logo—belongs to {{ config('site.legal_name') }} or its licensors. Ownership and usage rights for project deliverables are set out in the project quotation.</p>

        <h2>Confidentiality</h2>
        <p>We can sign an NDA before detailed project information is shared. Please do not send confidential files through the website enquiry form; we will confirm a suitable transfer method first.</p>

        <h2>Limitation of liability</h2>
        <p>The website is provided "as is". To the extent permitted by law, Architive is not liable for indirect or consequential losses arising from your use of the website. Liability for project work is set out in the approved written scope.</p>

        <h2>Changes</h2>
        <p>We may update these terms from time to time. The date above shows when they were last changed.</p>

        <h2>Contact</h2>
        <p>Questions about these terms? Email <a href="mailto:{{ config('site.email') }}">{{ config('site.email') }}</a>.</p>
    </div>
</section>
@endsection

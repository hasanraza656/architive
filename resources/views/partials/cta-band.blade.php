{{-- Dark closing call-to-action. Props: $title (HTML ok), $text, $cta, $ctaUrl --}}
<section class="cta-band section--dark" aria-labelledby="cta-title">
    <div class="cta-band__bg" aria-hidden="true"><span></span><span></span><span></span></div>
    <div class="wrap">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <h2 class="display-h display-h--light" id="cta-title" data-split>{!! $title ?? 'Your Next Deadline Does Not Need <em>Another Rushed Hire.</em>' !!}</h2>
                <p class="cta-band__text" data-reveal style="--d:.2s">{{ $text ?? 'Share the files, deadline and outcome you need. We will review the information and recommend a practical scope during a free consultation.' }}</p>
            </div>
            <div class="col-lg-4 text-lg-end" data-reveal style="--d:.3s">
                <a class="btn-ay btn-ay--lg" href="{{ $ctaUrl ?? pu('contact') }}" data-magnetic>{{ $cta ?? 'Start your project' }} <x-icon name="arrow-right" /></a>
            </div>
        </div>
        <div class="cta-band__foot" data-reveal style="--d:.4s">
            <ul>
                <li><x-icon name="check" /> Free Consultation or Paid Pilot</li>
                <li><x-icon name="check" /> Mutual NDA Available</li>
                <li><x-icon name="check" /> No Commitment Until Scope Approved</li>
            </ul>
            <a href="mailto:{{ config('site.email') }}">{{ config('site.email') }}</a>
        </div>
    </div>
</section>

{{-- Cookie and analytics notice. Shown until a choice is saved in localStorage ('architive-consent'); the footer "Cookie settings" button reopens it. Logic: public/assets/js/consent.js --}}
@php $ga = config('services.analytics'); @endphp
@if (! empty($ga['enabled']) && ! empty($ga['ga4_id']))
    <div class="consent" id="consent" role="region" aria-labelledby="consent-title" hidden>
        <div class="consent__text">
            <p class="consent__title" id="consent-title">Can we count your visit?</p>
            <p class="consent__body">We use Google Analytics and Google Ads to see which pages people read and which of our ads are useful, so we can improve both. They set cookies only if you accept. Read our <a href="{{ pu('privacy') }}">privacy policy</a>.</p>
        </div>
        <div class="consent__actions">
            <button type="button" class="consent__btn consent__btn--accept" data-consent="accept">Accept</button>
            <button type="button" class="consent__btn consent__btn--decline" data-consent="decline">Decline</button>
        </div>
    </div>
@endif

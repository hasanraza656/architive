{{-- Our own Trustpilot badge (drawn here, not the official logo file): green star wordmark, five star boxes filled to the score, the numbers,
     and a link to our Trustpilot page. Props: $variant ('light' on light backgrounds, 'dark' on the dark footer).
     The score and review count come from config('site.trustpilot_rating') (.env TRUSTPILOT_SCORE, TRUSTPILOT_REVIEWS). Until both are set
     the stars and numbers stay hidden, so the badge never claims a rating we have not confirmed. --}}
@php
    $tpUrl = config('site.social.trustpilot');
    $tpScore = (float) config('site.trustpilot_rating.score');
    $tpCount = (int) config('site.trustpilot_rating.count');
    $tpShow = $tpScore >= 1 && $tpScore <= 5;
    $tpVariant = $variant ?? 'light';
@endphp
@if ($tpUrl)
<a class="tp tp--{{ $tpVariant }}" href="{{ $tpUrl }}" target="_blank" rel="noopener" data-proof="trustpilot"
   aria-label="{{ $tpShow ? 'Rated ' . rtrim(rtrim(number_format($tpScore, 1), '0'), '.') . ' out of 5 on Trustpilot,' . ($tpCount > 0 ? ', based on ' . $tpCount . ' reviews' : '') . '. Opens Trustpilot.' : 'Read our reviews on Trustpilot. Opens Trustpilot.' }}">
    <span class="tp__brand">
        <svg class="tp__logo" viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false"><path fill="#00B67A" d="M12 1.5l3.09 7.2 7.81.66-5.92 5.15 1.76 7.64L12 17.9l-6.74 4.25 1.76-7.64L1.1 9.36l7.81-.66z"/></svg>
        <span class="tp__word">Trustpilot</span>
    </span>
    @if ($tpShow)
        <svg class="tp__stars" viewBox="0 0 156 28" width="156" height="28" role="img" aria-hidden="true" focusable="false">
            @for ($i = 0; $i < 5; $i++)
                @php $fill = max(0, min(1, $tpScore - $i)); @endphp
                <rect x="{{ $i * 32 }}" y="0" width="28" height="28" rx="3" fill="#DCDCE6"/>
                @if ($fill > 0)<rect x="{{ $i * 32 }}" y="0" width="{{ round(28 * $fill, 1) }}" height="28" rx="3" fill="#00B67A"/>@endif
                <path transform="translate({{ $i * 32 + 4 }} 4) scale(.833)" fill="#fff" d="M12 1.5l3.09 7.2 7.81.66-5.92 5.15 1.76 7.64L12 17.9l-6.74 4.25 1.76-7.64L1.1 9.36l7.81-.66z"/>
            @endfor
        </svg>
        <span class="tp__text"><b>TrustScore {{ rtrim(rtrim(number_format($tpScore, 1), '0'), '.') }}</b>@if ($tpCount > 0) <i>·</i> {{ number_format($tpCount) }} {{ $tpCount === 1 ? 'review' : 'reviews' }}@endif</span>
    @else
        <span class="tp__text tp__text--cta">Read our reviews <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
    @endif
</a>
@endif

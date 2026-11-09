{{-- Sample set shown as its cover thumbnail only (client feedback 9 Nov 2026). Prop: $set (App\Support\Portfolio::sets() entry).
     The full PDF is not published here: the button opens the client's own link (config/samples.php) and is left out until a link is set. --}}
@php
    $cv = $set['cover'];
    $grp = 'cover-' . $set['id'];
    $alt = $set['title'] . ': cover of the drawing set (' . $set['place'] . ')';
@endphp
<article class="set-card set-card--cover" data-reveal>
    <a class="set-card__media" href="{{ $cv['src'] }}" data-lightbox="{{ $grp }}" data-title="{{ $set['title'] }}" data-sub="{{ $set['place'] }}" data-alt="{{ $alt }}" aria-label="Enlarge the cover: {{ $set['title'] }}">
        <img src="{{ $cv['thumb'] }}" width="{{ $cv['tw'] }}" height="{{ $cv['th'] }}" alt="{{ $alt }}" loading="lazy" decoding="async">
        <span class="set-card__zoom"><x-icon name="search" /></span>
    </a>
    <div class="set-card__body">
        <p class="set-card__place">{{ $set['place'] }}</p>
        <h3>{{ $set['title'] }}</h3>
        <p class="set-card__sum">{{ $set['summary'] }}</p>
        <ul class="chips">@foreach ($set['chips'] as $c)<li>{{ $c }}</li>@endforeach</ul>
        @if (! empty($set['sheet_url']))
            <div class="set-card__foot">
                <small>Cover shown. The full set opens in a new tab.</small>
                <a class="btn-ay btn-ay--sm" href="{{ $set['sheet_url'] }}" target="_blank" rel="noopener">View complete sheet <x-icon name="arrow-right" /></a>
            </div>
        @endif
    </div>
</article>

{{-- Sample drawing/model set. Prop: $set (from App\Support\Portfolio::sets()). Sheets open in the lightbox viewer. --}}
@php
    $sheets = $set['sheets'];
    $first  = $sheets[0];
    $group  = 'set-' . $set['id'];
    $sub    = $set['title'] . ' · ' . $set['place'];
@endphp
<article class="set-card" data-reveal>
    <a class="set-card__media" href="{{ $first['src'] }}" data-lightbox="{{ $group }}" data-title="{{ $first['no'] }} · {{ $first['title'] }}" data-sub="{{ $sub }}" data-alt="{{ $first['alt'] }}"
       aria-label="Open sample sheets: {{ $set['title'] }}, {{ $set['place'] }}">
        <img src="{{ $first['thumb'] }}" width="{{ $first['tw'] }}" height="{{ $first['th'] }}" alt="{{ $first['alt'] }}" loading="lazy" decoding="async">
        <span class="set-card__badge">{{ count($sheets) }} sample sheets</span>
        <span class="set-card__zoom"><x-icon name="search" /></span>
    </a>
    <div class="set-card__body">
        <p class="set-card__place">{{ $set['place'] }}</p>
        <h3>{{ $set['title'] }}</h3>
        <p class="set-card__sum">{{ $set['summary'] }}</p>
        <ul class="chips">@foreach ($set['chips'] as $c)<li>{{ $c }}</li>@endforeach</ul>
        @if (count($sheets) > 1)
            <div class="set-card__strip" aria-label="More sheets in this set">
                @foreach (array_slice($sheets, 1) as $sh)
                    <a href="{{ $sh['src'] }}" data-lightbox="{{ $group }}" data-title="{{ $sh['no'] }} · {{ $sh['title'] }}" data-sub="{{ $sub }}" data-alt="{{ $sh['alt'] }}">
                        <img src="{{ $sh['thumb'] }}" width="{{ $sh['tw'] }}" height="{{ $sh['th'] }}" alt="{{ $sh['alt'] }}" loading="lazy" decoding="async">
                    </a>
                @endforeach
            </div>
        @endif
        <div class="set-card__foot">
            <small>Showing {{ count($sheets) }} of {{ $set['total'] }} sheets · covers and site plans omitted for client privacy</small>
            <a class="link-arrow" href="{{ $first['src'] }}" data-lightbox-trigger="{{ $group }}">View sheets <x-icon name="arrow-right" /></a>
        </div>
    </div>
</article>

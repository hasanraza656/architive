{{-- Auto-fading gallery of real work for one service. First slide is visible without JavaScript.
     Expects $s (Content::coreServices() entry) and $n (card number). --}}
<div class="slider" data-slider>
    @foreach ($s['gallery'] as $g)
        <div class="slider__slide {{ ! empty($g['sheet']) ? 'slider__slide--sheet' : '' }} {{ $loop->first ? 'is-active' : '' }}">
            <img src="{{ $g['thumb'] }}" srcset="{{ $g['thumb'] }} {{ $g['tw'] }}w, {{ $g['src'] }} {{ $g['w'] }}w" sizes="(min-width: 992px) 33vw, (min-width: 576px) 50vw, 100vw"
                 width="{{ $g['w'] }}" height="{{ $g['h'] }}" alt="{{ $g['alt'] }}" @if (! $loop->first || $n > 1) loading="lazy" @endif decoding="async">
        </div>
    @endforeach
    <a class="slider__link" href="{{ pu($s['route']) }}" aria-label="Explore {{ $s['title'] }}" tabindex="-1"></a>
    <span class="slider__num" aria-hidden="true">{{ sprintf('%02d', $n) }}</span>
    @if (count($s['gallery']) > 1)
        <div class="slider__dots" role="group" aria-label="{{ $s['title'] }} examples">
            @foreach ($s['gallery'] as $g)
                <button type="button" class="slider__dot {{ $loop->first ? 'is-active' : '' }}" aria-label="Show example {{ $loop->iteration }} of {{ count($s['gallery']) }}"></button>
            @endforeach
        </div>
    @endif
</div>

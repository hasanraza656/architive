{{-- Completed-project figures (Fiverr + Upwork) as a slim band under the hero. Expects $track (config('site.track_record')). --}}
<section class="track-band" aria-label="Completed projects">
    <div class="wrap">
        <ul class="track-band__list">
            @foreach ($track as $t)
                <li class="track-band__item" data-reveal style="--d: {{ $loop->index * .12 }}s">
                    <strong class="track-band__num" @if ($t['count']) data-count="{{ $t['count'] }}" data-suffix="{{ $t['suffix'] }}" @endif>{{ $t['display'] }}</strong>
                    <span class="track-band__label"><b>{{ $t['unit'] }}</b> on {{ $t['label'] }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</section>

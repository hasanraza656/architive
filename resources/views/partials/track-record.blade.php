{{-- Track record: the only place these figures appear. Expects $track (config('site.track_record')). --}}
<div class="track" data-track>
    <p class="track__eyebrow">Track record</p>
    <div class="track__tabs" role="tablist" aria-label="Completed work by channel">
        @foreach ($track as $t)
            <button type="button" class="track__tab" role="tab" id="track-tab-{{ $t['key'] }}" aria-controls="track-panel-{{ $t['key'] }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? '0' : '-1' }}">
                {{ $t['label'] }} <b>{{ $t['display'] }}</b>
            </button>
        @endforeach
    </div>
    @foreach ($track as $t)
        <div class="track__panel {{ $loop->first ? 'is-active' : '' }}" role="tabpanel" id="track-panel-{{ $t['key'] }}" aria-labelledby="track-tab-{{ $t['key'] }}">
            <strong class="track__num" @if ($t['count']) data-to="{{ $t['count'] }}" data-suffix="{{ $t['suffix'] }}" @endif>{{ $t['display'] }}</strong>
            <span class="track__unit">{{ $t['unit'] }} · {{ $t['label'] }}</span>
            <p class="track__text">{{ $t['text'] }}</p>
        </div>
    @endforeach
    <div class="track__meter" aria-hidden="true">
        @foreach ($track as $t)<i class="{{ $loop->first ? 'is-on' : '' }}"></i>@endforeach
    </div>
    <p class="track__foot">Figures supplied by the studio and updated {{ config('site.track_record_updated') }}.</p>
</div>

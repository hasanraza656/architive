{{-- Accessible carousel. $slides from Content::slides() --}}
<div class="quotes" data-quotes role="region" aria-roledescription="carousel" aria-label="Client feedback and working principles">
    <span class="quotes__mark"><x-icon name="quote" /></span>
    <div class="quotes__stage" aria-live="polite">
        @foreach ($slides as $s)
            <figure class="quotes__slide {{ $loop->first ? 'is-active' : '' }}" role="group" aria-roledescription="slide" aria-label="{{ $loop->iteration }} of {{ count($slides) }}">
                <blockquote><p>“{{ $s['quote'] }}”</p></blockquote>
                <figcaption><strong>{{ $s['name'] }}</strong><span>{{ $s['role'] }}</span><em>{{ $s['tag'] }}</em></figcaption>
            </figure>
        @endforeach
    </div>
    <div class="quotes__ctrl">
        <button type="button" data-q-prev aria-label="Previous"><x-icon name="chevron-left" /></button>
        <div class="quotes__dots">
            @foreach ($slides as $s)<button type="button" class="{{ $loop->first ? 'is-active' : '' }}" data-q-dot="{{ $loop->index }}" aria-label="Go to slide {{ $loop->iteration }}"></button>@endforeach
        </div>
        <button type="button" data-q-next aria-label="Next"><x-icon name="chevron-right" /></button>
    </div>
</div>

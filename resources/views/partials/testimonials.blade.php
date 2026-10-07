{{-- Real client reviews (Fiverr/Upwork), as an accessible auto-advancing slider — 3 cards per slide. $items from Content::testimonials(). --}}
@php $groups = array_chunk($items, 3); @endphp
<div class="quotes testimonials" data-quotes role="region" aria-roledescription="carousel" aria-label="Client reviews">
    <div class="quotes__stage" aria-live="polite">
        @foreach ($groups as $group)
            <figure class="quotes__slide {{ $loop->first ? 'is-active' : '' }}" role="group" aria-roledescription="slide" aria-label="{{ $loop->iteration }} of {{ count($groups) }}">
                <div class="testimonials__row">
                    @foreach ($group as $t)
                        <div class="testimonial">
                            <div class="testimonial__head">
                                <span class="testimonial__avatar" aria-hidden="true">{{ $t['initial'] }}</span>
                                <div>
                                    <strong class="testimonial__name">{{ $t['name'] }}</strong>
                                    <span class="testimonial__source">{{ $t['source'] }}</span>
                                </div>
                                <span class="testimonial__stars" aria-label="{{ $t['stars'] }} out of 5 stars">
                                    @for ($i = 0; $i < $t['stars']; $i++)<x-icon name="star" />@endfor
                                </span>
                            </div>
                            <blockquote><p>{{ $t['quote'] }}</p></blockquote>
                        </div>
                    @endforeach
                </div>
            </figure>
        @endforeach
    </div>
    <div class="quotes__ctrl">
        <button type="button" data-q-prev aria-label="Previous reviews"><x-icon name="chevron-left" /></button>
        <div class="quotes__dots">
            @foreach ($groups as $group)<button type="button" class="{{ $loop->first ? 'is-active' : '' }}" data-q-dot="{{ $loop->index }}" aria-label="Go to review set {{ $loop->iteration }}"></button>@endforeach
        </div>
        <button type="button" data-q-next aria-label="Next reviews"><x-icon name="chevron-right" /></button>
    </div>
</div>

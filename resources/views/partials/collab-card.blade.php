{{-- Collaboration teaser card. Props: $slug, $c. Uses the real project visual when available, else the line-art placeholder. --}}
@php $m = \App\Support\Portfolio::caseMedia($slug); @endphp
<a class="collab-card" href="{{ pu('collaborations.show', ['slug' => $slug]) }}" data-spotlight>
    <div class="collab-card__art {{ $m ? 'collab-card__art--photo' : '' }}">
        <div class="collab-card__row"><span>Project {{ $c['num'] }}</span><b class="tag-{{ $c['key'] }}">{{ $c['tag'] }}</b></div>
        @if ($m)
            <span class="collab-card__photo"><img src="{{ $m['cover']['thumb'] }}" width="{{ $m['cover']['tw'] }}" height="{{ $m['cover']['th'] }}" alt="{{ $m['alt'] }}" loading="lazy" decoding="async"></span>
        @else
            @include('partials.blueprint-card', ['kind' => $c['key']])
        @endif
        <div class="collab-card__row"><span>{{ $m ? $m['caption'] : 'Scale 1:100' }}</span><i class="collab-card__go"><x-icon name="arrow-up-right" /></i></div>
    </div>
    <h3 class="collab-card__title">{{ $c['name'] }}</h3>
    <p class="collab-card__meta">{{ $c['meta'] }}</p>
</a>

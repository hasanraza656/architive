{{-- Collaboration teaser card. Props: $slug, $c --}}
<a class="collab-card" href="{{ pu('collaborations.show', ['slug' => $slug]) }}" data-spotlight>
    <div class="collab-card__art">
        <div class="collab-card__row"><span>Project {{ $c['num'] }}</span><b class="tag-{{ $c['key'] }}">{{ $c['tag'] }}</b></div>
        @include('partials.blueprint-card', ['kind' => $c['key']])
        <div class="collab-card__row"><span>Scale 1:100</span><i class="collab-card__go"><x-icon name="arrow-up-right" /></i></div>
    </div>
    <h3 class="collab-card__title">{{ $c['name'] }}</h3>
    <p class="collab-card__meta">{{ $c['meta'] }}</p>
</a>

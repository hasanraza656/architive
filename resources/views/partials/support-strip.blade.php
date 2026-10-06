{{-- Production support as one horizontal strip (it is how clients work with us, not a fourth service). --}}
@php $support = \App\Support\Content::productionSupport(); @endphp
<div class="support-strip" data-reveal>
    <div>
        <span class="support-strip__kicker">Architectural production support</span>
        <h3 class="support-strip__title">A flexible extension <em>of your studio.</em></h3>
        <p class="support-strip__text">Bring one defined task, start with a paid pilot or use us for recurring production capacity.</p>
    </div>
    <ul class="support-strip__chips" aria-label="Ways to work with Architive">
        @foreach (\App\Support\Content::engagements() as $e)
            <li class="{{ ! empty($e['badge']) ? 'is-hourly' : '' }}"><x-icon :name="$e['ic']" />{{ $e['title'] }} @if (! empty($e['badge']))<b>{{ $e['badge'] }}</b>@endif</li>
        @endforeach
    </ul>
    <a class="btn-ay btn-ay--sm" href="{{ pu($support['route']) }}">Explore production support <x-icon name="arrow-right" /></a>
</div>

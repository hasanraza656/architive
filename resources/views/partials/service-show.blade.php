{{-- One service on the home / services page: title + the two buttons, then a large auto-running strip of its real samples.
     Expects $s (Content::coreServices() entry), $key (visualization|bim|cad) and $n (number). --}}
<article class="svc-show" data-reveal>
    <div class="wrap wrap--wide">
        <div class="svc-show__head">
            <h3 class="svc-show__title"><span class="svc-show__n">{{ sprintf('%02d', $n) }}</span>{{ $s['title'] }}</h3>
            <div class="svc-show__actions">
                <a class="btn-ay" href="{{ pu($s['route']) }}" aria-label="Explore more: {{ $s['title'] }}">Explore more <x-icon name="arrow-right" /></a>
                <a class="btn-line" href="{{ pu('contact', [], ['service' => $key]) }}" data-topic="{{ $s['title'] }}">Start a project</a>
            </div>
        </div>
    </div>
    @include('partials.sample-reel', ['category' => $key, 'limit' => 12, 'big' => true, 'rev' => $n % 2 === 0])
</article>

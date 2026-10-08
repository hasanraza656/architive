{{-- Home: the three core services as expanding panels. Hover / tap / arrow keys open a panel; each panel fades through that service's real work.
     Images never crop (blurred copy fills the frame). The Revit house render is left out here on purpose. Expects $core (Content::coreServices()). --}}
@php
    $skip = ['cases/manuel-house-3d'];
    $data = [];
    foreach ($core as $key => $s) {
        $slides = array_values(array_filter(\App\Support\Portfolio::reel($key, 10), fn ($i) => ! in_array($i['key'] ?? '', $skip, true)));
        $data[$key] = ['s' => $s, 'slides' => array_slice($slides, 0, 8)];
    }
@endphp
@push('head')
    <link rel="stylesheet" href="{{ asset_v('assets/css/svc-panels.css') }}">
@endpush
@push('scripts')
    <script src="{{ asset_v('assets/js/svc-panels.js') }}"></script>
@endpush

<section class="section svcp-sec" id="services" aria-labelledby="services-title">
    <div class="wrap wrap--wide">
        <div class="text-center mb-5">
            <p class="eyebrow eyebrow--center eyebrow--lg" data-reveal>Services</p>
            <h2 class="display-h display-h--md mb-0" id="services-title" data-split>One Brief. One Team. <em>Every Deliverable Connected.</em></h2>
        </div>
        <div class="opx" data-opx>
            @foreach ($data as $key => $d)
                @php $s = $d['s']; @endphp
                <article class="opx__p {{ $loop->first ? 'is-on' : '' }}" data-p tabindex="0" aria-label="{{ $s['title'] }}">
                    <div class="opx__media" data-fader="5200">
                        @foreach ($d['slides'] as $img)
                            @include('partials.svc-slide', ['img' => $img, 'group' => 'svc-' . $key, 'first' => $loop->first, 'sizes' => '(min-width: 992px) 62vw, 92vw'])
                        @endforeach
                    </div>
                    <span class="opx__shade" aria-hidden="true"></span>
                    <h3 class="opx__title">{{ $s['title'] }}</h3>
                    <div class="opx__nav">
                        <button type="button" data-prev aria-label="Previous image"><x-icon name="arrow-left" /></button>
                        <button type="button" data-next aria-label="Next image"><x-icon name="arrow-right" /></button>
                    </div>
                    <div class="opx__more">
                        <a class="btn-ay btn-ay--sm" href="{{ pu($s['route']) }}" aria-label="Explore more: {{ $s['title'] }}">Explore more <x-icon name="arrow-right" /></a>
                        <a class="btn-ghost btn-ghost--sm" href="{{ pu('contact', [], ['service' => $key]) }}" data-topic="{{ $s['title'] }}">Start a project</a>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

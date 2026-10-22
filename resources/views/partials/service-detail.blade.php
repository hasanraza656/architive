{{-- Detailed service page body (same layout as the exterior page). Props, all in $d:
     key, name, type, desc (schema), eyebrow, title, lead, heroItems[], image [key, alt], cta, topic, service (contact form value),
     schemaItems[], gallery (Portfolio category or null), galleryTitle, galleryLead,
     cardsEyebrow, cardsTitle, cardsLead, cards[ [icon,title,text] ], forTitle, forItems[ [who, text] ],
     sendTitle, send[], sendNote, stepsTitle, stepsLead, steps[ [n,icon,title,text] ],
     priceTitle, price[] (paragraphs, HTML ok), faqs[id => [q,a]], related (HTML), ctaTitle, note (optional, under the hero) --}}
@include('partials.service-schema', ['svcName' => $d['name'], 'svcType' => $d['type'], 'svcDesc' => $d['desc'], 'svcItems' => $d['schemaItems']])

@include('partials.svc-head', [
    'eyebrow' => $d['eyebrow'], 'title' => $d['title'], 'lead' => $d['lead'], 'items' => $d['heroItems'],
    'image' => $d['image'], 'cta' => $d['cta'], 'ctaUrl' => pu('contact', [], ['service' => $d['service']]), 'topic' => $d['topic'],
    'note' => $d['note'] ?? null,
])

@if (! empty($d['gallery']))
<section class="section" id="work" aria-labelledby="{{ $d['key'] }}-work">
    <div class="wrap">
        <div class="section-head">
            <div>
                <p class="eyebrow" data-reveal>Our work</p>
                <h2 class="display-h display-h--md mb-0" id="{{ $d['key'] }}-work" data-split>{!! $d['galleryTitle'] !!}</h2>
            </div>
            <p class="lead-p mb-0" data-reveal style="max-width: 44ch">{{ $d['galleryLead'] }}</p>
        </div>
        @include('partials.work-gallery', ['only' => $d['gallery']])
    </div>
</section>
@endif

<section class="section {{ empty($d['gallery']) ? '' : 'section--alt' }}" aria-labelledby="{{ $d['key'] }}-cards">
    <div class="wrap">
        <div class="section-head">
            <div>
                <p class="eyebrow" data-reveal>{{ $d['cardsEyebrow'] }}</p>
                <h2 class="display-h display-h--md mb-0" id="{{ $d['key'] }}-cards" data-split>{!! $d['cardsTitle'] !!}</h2>
            </div>
            <p class="lead-p mb-0" data-reveal style="max-width: 46ch">{{ $d['cardsLead'] }}</p>
        </div>
        <div class="row g-4">
            @foreach ($d['cards'] as $v)
                <div class="col-md-6 col-lg-4" data-reveal style="--d: {{ ($loop->index % 3) * .08 }}s">
                    <article class="feature-card h-100">
                        <span class="feature-card__ic"><x-icon :name="$v['icon']" /></span>
                        <h3 class="display-h display-h--sm">{{ $v['title'] }}</h3>
                        <p>{{ $v['text'] }}</p>
                    </article>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="section {{ empty($d['gallery']) ? 'section--alt' : '' }}" aria-labelledby="{{ $d['key'] }}-send">
    <div class="wrap">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-6" data-reveal>
                <p class="eyebrow">Who it is for</p>
                <h2 class="display-h display-h--sm" data-split>{!! $d['forTitle'] !!}</h2>
                @foreach ($d['forItems'] as [$who, $text])
                    <p><b>{{ $who }}</b> {{ $text }}</p>
                @endforeach
            </div>
            <div class="col-lg-6" data-reveal style="--d:.1s">
                <p class="eyebrow">What to send</p>
                <h2 class="display-h display-h--sm" id="{{ $d['key'] }}-send">{!! $d['sendTitle'] !!}</h2>
                <ul class="check-list">@foreach ($d['send'] as $s)<li><x-icon name="check" /> {{ $s }}</li>@endforeach</ul>
                <p class="note-line">{{ $d['sendNote'] }}</p>
            </div>
        </div>
    </div>
</section>

<section class="section {{ empty($d['gallery']) ? '' : 'section--alt' }}" aria-labelledby="{{ $d['key'] }}-steps">
    <div class="wrap">
        <div class="text-center mb-5">
            <p class="eyebrow eyebrow--center" data-reveal>How it works</p>
            <h2 class="display-h" id="{{ $d['key'] }}-steps" data-split>{!! $d['stepsTitle'] !!}</h2>
            <p class="lead-p mx-auto" data-reveal style="max-width: 60ch">{{ $d['stepsLead'] }}</p>
        </div>
        <ol class="vsteps" data-vsteps>
            @foreach ($d['steps'] as $s)
                <li class="vsteps__item" data-reveal="{{ $loop->odd ? 'left' : 'right' }}">
                    <span class="vsteps__node"><x-icon :name="$s['icon']" /></span>
                    <div class="vsteps__card">
                        <span class="vsteps__n">{{ $s['n'] }}</span>
                        <h3>{{ $s['title'] }}</h3>
                        <p>{{ $s['text'] }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</section>

<section class="section {{ empty($d['gallery']) ? 'section--alt' : '' }}" aria-labelledby="{{ $d['key'] }}-price">
    <div class="wrap">
        <div class="row g-4">
            <div class="col-lg-7" data-reveal>
                <article class="feature-card h-100">
                    <span class="feature-card__ic"><x-icon name="receipt" /></span>
                    <p class="eyebrow">Pricing</p>
                    <h2 class="display-h display-h--sm" id="{{ $d['key'] }}-price">{!! $d['priceTitle'] !!}</h2>
                    @foreach ($d['price'] as $p)<p @if ($loop->last) class="mb-0" @endif>{!! $p !!}</p>@endforeach
                </article>
            </div>
            <div class="col-lg-5" data-reveal style="--d:.12s">
                <article class="feature-card feature-card--dark h-100">
                    <span class="feature-card__ic"><x-icon name="lock" /></span>
                    <p class="eyebrow eyebrow--light">Confidentiality</p>
                    <h2 class="display-h display-h--light display-h--sm">Your Drawings <em>Stay Private</em></h2>
                    <p class="mb-0">We can sign an NDA before detailed files are shared. Project access is limited to the assigned team, and transfer and storage are agreed before production starts.</p>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="section {{ empty($d['gallery']) ? '' : 'section--alt' }}" aria-labelledby="{{ $d['key'] }}-faq">
    <div class="wrap wrap--narrow">
        <p class="eyebrow eyebrow--center" data-reveal>Questions</p>
        <h2 class="display-h text-center mb-5" id="{{ $d['key'] }}-faq" data-split>{{ $d['topic'] }} <em>Questions</em></h2>
        @include('partials.faq-list', ['items' => $d['faqs'], 'uid' => $d['key'] . 'faq'])
        <p class="note-line text-center mt-4">{!! $d['related'] !!}</p>
    </div>
</section>

@include('partials.cta-band', ['title' => $d['ctaTitle'], 'cta' => $d['cta'], 'ctaUrl' => pu('contact', [], ['service' => $d['service']])])

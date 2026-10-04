{{-- Accessible accordion. Props: $items (id => [q,a,cat]), $uid (unique prefix). Emits FAQPage JSON-LD for the visible items. --}}
@php $uid = $uid ?? 'faq'; @endphp
@push('head')
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => collect($items)->map(fn ($f) => [
            '@type' => 'Question', 'name' => $f['q'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
        ])->values()->all(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

<div class="faq" id="{{ $uid }}" data-faq>
    @foreach ($items as $id => $f)
        <div class="faq__item" data-cat="{{ $f['cat'] ?? '' }}" data-reveal style="--d: {{ min($loop->index, 6) * .05 }}s">
            <h3 class="faq__q">
                <button class="faq__btn" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $uid }}-a{{ $id }}"
                        aria-expanded="false" aria-controls="{{ $uid }}-a{{ $id }}">
                    <span class="faq__num">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="faq__text">{{ $f['q'] }}</span>
                    <span class="faq__icon"><x-icon name="chevron-down" /></span>
                </button>
            </h3>
            <div id="{{ $uid }}-a{{ $id }}" class="collapse" data-bs-parent="#{{ $uid }}">
                <div class="faq__a"><p>{{ $f['a'] }}</p></div>
            </div>
        </div>
    @endforeach
    <p class="faq__empty" hidden>No questions match your search. <a href="{{ pu('contact') }}">Ask us directly</a>.</p>
</div>

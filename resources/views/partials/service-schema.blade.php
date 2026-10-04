{{-- Service JSON-LD. Props: $svcName, $svcType, $svcDesc, $svcItems (list of offered deliverables) --}}
@php $canonical = rtrim(url()->current(), '/') . '/'; @endphp
@push('head')
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Service',
        '@id' => $canonical . '#service',
        'name' => $svcName,
        'serviceType' => $svcType,
        'description' => $svcDesc,
        'url' => $canonical,
        'provider' => ['@id' => url('/') . '/#organization'],
        'areaServed' => 'Worldwide',
        'audience' => ['@type' => 'Audience', 'audienceType' => 'Architecture firms, interior design studios, developers, contractors and homeowners'],
        'hasOfferCatalog' => [
            '@type' => 'OfferCatalog',
            'name' => $svcName,
            'itemListElement' => collect($svcItems)->map(fn ($i) => ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => $i]])->values()->all(),
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

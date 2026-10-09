@php
    $root   = url('/');
    $orgId  = $root . '/#organization';
    $siteId = $root . '/#website';
    $pageId = $canonical . '#webpage';

    $org = [
        '@type'        => ['Organization', 'ProfessionalService'],
        '@id'          => $orgId,
        'name'         => config('site.name'),
        'legalName'    => config('site.legal_name'),
        'url'          => $root . '/',
        'logo'         => ['@type' => 'ImageObject', 'url' => asset('assets/img/icon-512.png'), 'width' => 512, 'height' => 512],
        'image'        => asset('assets/img/og-default.jpg'),
        'email'        => config('site.email'),
        'telephone'    => config('site.phone'),
        'description'  => 'Architectural production studio providing architectural visualization, BIM and Revit, and CAD drafting for architecture firms, interior design studios, developers, contractors and homeowners worldwide.',
        'foundingDate' => (string) config('site.founded'),
        'founder'      => ['@type' => 'Person', 'name' => config('site.founder'), 'jobTitle' => config('site.founder_role')],
        'address'      => ['@type' => 'PostalAddress', 'addressLocality' => 'Newark', 'addressRegion' => 'Delaware', 'addressCountry' => 'US'],
        'areaServed'   => 'Worldwide',
        'knowsAbout'   => ['Architectural visualization', 'Architectural rendering', 'BIM modeling', 'Revit', 'Scan to BIM', 'CAD drafting', 'PDF to DWG conversion', 'Architectural production support'],
        'contactPoint' => [['@type' => 'ContactPoint', 'contactType' => 'sales', 'email' => config('site.email'), 'telephone' => config('site.phone'), 'availableLanguage' => ['English']]],
    ];
    if (! empty(config('site.social'))) {
        $org['sameAs'] = array_values(config('site.social'));
    }

    $webpageType = match (true) {
        is_page('about') => 'AboutPage',
        is_page('contact') => 'ContactPage',
        is_page('collaborations.index', 'services.index', 'sitemap.html') => 'CollectionPage',
        default => 'WebPage',
    };

    $graph = [
        $org,
        ['@type' => 'WebSite', '@id' => $siteId, 'url' => $root . '/', 'name' => config('site.name'), 'inLanguage' => 'en', 'publisher' => ['@id' => $orgId]],
        [
            '@type' => $webpageType, '@id' => $pageId, 'url' => $canonical, 'name' => $seoMeta['title'],
            'description' => $seoMeta['description'], 'inLanguage' => 'en',
            'isPartOf' => ['@id' => $siteId], 'about' => ['@id' => $orgId],
            'primaryImageOfPage' => ['@type' => 'ImageObject', 'url' => $ogImage],
        ],
    ];

    if (! empty($crumbs) && count($crumbs) > 1) {
        $items = [];
        foreach ($crumbs as $i => $c) {
            $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c['label'], 'item' => $c['url']];
        }
        $graph[2]['breadcrumb'] = ['@id' => $canonical . '#breadcrumb'];
        $graph[] = ['@type' => 'BreadcrumbList', '@id' => $canonical . '#breadcrumb', 'itemListElement' => $items];
    }
@endphp
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>

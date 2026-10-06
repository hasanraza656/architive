<?php

/*
|--------------------------------------------------------------------------
| Per-page SEO metadata, keyed by route name
|--------------------------------------------------------------------------
| title / description for the first ten pages come straight from the client's
| approved copy deck. `image` is a path under /public (defaults to og-default).
| `sitemap` => [priority, changefreq] feeds /sitemap.xml.
*/

return [
    'default_image' => 'assets/img/og-default.jpg',
    'title_suffix'  => ' | Architive',

    'pages' => [
        'home' => [
            'title'       => 'Architectural Visualization, BIM and CAD | Architive',
            'description' => 'Architectural visualization, BIM and Revit, and CAD drafting for architecture firms, interior designers, developers and homeowners worldwide.',
            'sitemap'     => [1.0, 'weekly'],
        ],
        'about' => [
            'title'       => 'Who We Are | About Architive, Architectural Production Studio',
            'description' => 'Meet Architive, an established architectural production studio providing visualization, BIM and Revit, and CAD drafting worldwide.',
            'image'       => 'assets/img/og/blueprint-tools.jpg',
            'sitemap'     => [0.8, 'monthly'],
        ],
        'services.index' => [
            'title'       => 'Architectural Visualization, BIM and CAD Services | Architive',
            'description' => 'Three connected services—architectural visualization, BIM and Revit, and CAD drafting—plus flexible production support for architecture and design firms.',
            'sitemap'     => [0.9, 'monthly'],
        ],
        'services.visualization' => [
            'title'       => 'Architectural Visualization and Rendering | Architive',
            'description' => 'Architectural visualization for design reviews, approvals and marketing, including interior and exterior renderings, 3D floor plans and renovation visuals.',
            'image'       => 'assets/img/og/viz-bandon.jpg',
            'sitemap'     => [0.9, 'monthly'],
        ],
        'services.bim' => [
            'title'       => 'BIM Modeling and Revit Services | Architive',
            'description' => 'BIM and Revit services for architects and project teams, including CAD to BIM, scan to BIM, Revit families, model updates and documentation.',
            'image'       => 'assets/img/og/bim-scan.jpg',
            'sitemap'     => [0.9, 'monthly'],
        ],
        'services.cad' => [
            'title'       => 'CAD Drafting and Permit Drawing Support | Architive',
            'description' => 'CAD drafting for architects, interior designers and contractors, including plans, elevations, sections, PDF to DWG, redlines and permit-support packages.',
            'image'       => 'assets/img/og/cad-permit.jpg',
            'sitemap'     => [0.9, 'monthly'],
        ],
        'services.outsourcing' => [
            'title'       => 'Architectural Production Support for Design Firms | Architive',
            'description' => 'Flexible CAD, Revit and visualization production support for architecture and interior design firms, delivered to your standards and schedule.',
            'image'       => 'assets/img/og/bim-steel-structure.jpg',
            'sitemap'     => [0.9, 'monthly'],
        ],
        'collaborations.index' => [
            'title'       => 'Architectural Production Collaborations | Architive',
            'description' => 'Selected Architive collaborations across visualization, CAD drafting and Revit production support for design and development teams.',
            'sitemap'     => [0.8, 'monthly'],
        ],
        'process' => [
            'title'       => 'How Architive Projects Work',
            'description' => 'Share your files, receive a clear scope and quotation, review progress at agreed stages and receive final CAD, Revit or visualization deliverables.',
            'image'       => 'assets/img/og/cad-plan-pen.jpg',
            'sitemap'     => [0.8, 'monthly'],
        ],
        'team' => [
            'title'       => 'The Architive Team | CAD, Revit and Visualization Specialists',
            'description' => 'Meet the studio structure behind Architive: CAD, Revit and visualization specialists working as one team with a named point of contact.',
            'image'       => 'assets/img/og/developer-tower-frame.jpg',
            'robots'      => 'noindex,follow',   // team page is parked until the client supplies team portraits
        ],
        'faqs' => [
            'title'       => 'Architive FAQs | Pricing, Revisions, NDAs and Files',
            'description' => 'Answers on office standards, small paid pilots, permit drawing support, pricing, revisions, NDAs and deliverable formats from Architive.',
            'sitemap'     => [0.7, 'monthly'],
        ],
        'contact' => [
            'title'       => 'Contact Architive | Start Your Project',
            'description' => 'Share your files, deadline and outcome. Architive reviews the information and recommends a practical scope during a free consultation.',
            'image'       => 'assets/img/og/contact-glass.jpg',
            'sitemap'     => [0.9, 'yearly'],
        ],
        'privacy' => [
            'title'       => 'Privacy Policy | Architive',
            'description' => 'How Architive collects, uses and protects the information you share when you contact us or browse this website.',
            'sitemap'     => [0.3, 'yearly'],
        ],
        'terms' => [
            'title'       => 'Terms of Service | Architive',
            'description' => 'The terms that apply to your use of the Architive website and to project enquiries.',
            'sitemap'     => [0.3, 'yearly'],
        ],
        'sitemap.html' => [
            'title'       => 'Site Map | Architive Pages and Services',
            'description' => 'A complete list of Architive pages: services, collaborations, process, FAQs and contact.',
            'sitemap'     => [0.2, 'monthly'],
        ],
    ],
];

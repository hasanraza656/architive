<?php

/*
|--------------------------------------------------------------------------
| Architive – global site settings
|--------------------------------------------------------------------------
| Single source of truth for brand facts, contact details, navigation and
| structured-data inputs. Edit here and every page/footer/schema updates.
*/

return [
    'name'        => 'Architive',
    'legal_name'  => 'Architive LLC',
    'tagline'     => 'Architectural production studio',
    'founded'     => 2017,
    'founder'     => 'Madiha Altaf',
    'founder_role' => 'Founder and Architectural Engineer',
    'email'       => 'info@architive.net',

    // Internal: where form notifications are delivered (ADMIN_EMAIL in .env)
    'admin_email' => env('ADMIN_EMAIL', env('MAIL_FROM_ADDRESS')),
    'locale'      => 'en_US',
    'theme_color' => '#FFD60A',

    'address' => [
        'corporate' => ['label' => 'US Corporate Entity', 'value' => 'Architive LLC · Newark, Delaware, USA'],
        'studio'    => ['label' => 'Production Studio',   'value' => 'Multan, Punjab, Pakistan'],
    ],

    // Social profiles: add real URLs here and they are emitted into schema sameAs + footer.
    'social' => [
        // 'linkedin' => 'https://www.linkedin.com/company/…',
    ],

    'nav' => [
        ['label' => 'Home',       'route' => 'home'],
        ['label' => 'Who We Are', 'route' => 'about'],
        ['label' => 'Services',   'route' => 'services.index', 'children' => [
            ['label' => 'Architectural Visualization', 'route' => 'services.visualization', 'icon' => 'eye',       'text' => 'Interiors, exteriors, 3D floor plans'],
            ['label' => 'BIM and Revit',               'route' => 'services.bim',           'icon' => 'box',       'text' => 'CAD to BIM, scan to BIM, families'],
            ['label' => 'CAD Drafting',                'route' => 'services.cad',           'icon' => 'file-text', 'text' => 'Plans, sections, PDF to DWG'],
        ], 'more' => [
            ['label' => 'Production support', 'route' => 'services.outsourcing', 'text' => 'How we extend your studio'],
            ['label' => 'View all services',  'route' => 'services.index',       'text' => ''],
        ]],
        ['label' => 'Projects', 'route' => 'collaborations.index'],
        ['label' => 'Process',  'route' => 'process'],
        ['label' => 'Contact',  'route' => 'contact'],
    ],

    'footer_links' => [
        ['label' => 'Home',            'route' => 'home'],
        ['label' => 'Who We Are',      'route' => 'about'],
        ['label' => 'Services',        'route' => 'services.index'],
        ['label' => 'Collaborations',  'route' => 'collaborations.index'],
        ['label' => 'Our Process',     'route' => 'process'],
        ['label' => 'FAQs',            'route' => 'faqs'],
    ],

    // Verified completed-work figures supplied by the client (shown once, in the "Who We Are" track record).
    // Reviews are intentionally NOT shown until the client confirms exact numbers.
    'track_record_updated' => 'October 2026',
    'track_record' => [
        ['key' => 'fiverr', 'label' => 'Fiverr',         'count' => 1200, 'suffix' => '+', 'display' => '1,200+', 'unit' => 'completed projects',
         'text' => 'Where Architive began in 2017, growing through repeat projects and long-term working relationships.'],
        ['key' => 'upwork', 'label' => 'Upwork',         'count' => 130,  'suffix' => '+', 'display' => '130+',   'unit' => 'completed projects',
         'text' => 'Completed projects for architecture and design clients, delivered with the same one-brief, one-team approach.'],
        ['key' => 'direct', 'label' => 'Direct clients', 'count' => null, 'suffix' => '',  'display' => '15–20',    'unit' => 'active direct clients today',
         'text' => 'Studios and teams working with Architive directly, outside the marketplaces.'],
    ],

    // Hourly option shown on the production-support pages (client decision: "starting from $16 per hour")
    'hourly_from' => 16,

    'standards' => [
        'AutoCAD Scaled DWG',
        '3ds Max & V-Ray',
        'Mutual NDA Protected',
    ],

    'software' => ['AutoCAD', 'Revit', '3ds Max', 'SketchUp', 'V-Ray', 'Corona', 'Blender'],
];

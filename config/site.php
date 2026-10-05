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
    'email'       => 'architive.net@gmail.com',

    // Internal: where form notifications are delivered (ADMIN_EMAIL in .env)
    'admin_email' => env('ADMIN_EMAIL', env('MAIL_FROM_ADDRESS')),
    'locale'      => 'en_US',
    'theme_color' => '#FFD60A',

    'address' => [
        'corporate' => ['label' => 'US Corporate Entity', 'value' => 'Architive LLC · Newark, Delaware, USA'],
        'studio'    => ['label' => 'Production Studio',   'value' => 'Multan, Punjab, Pakistan · 12+ Engineers'],
    ],

    // Social profiles: add real URLs here and they are emitted into schema sameAs + footer.
    'social' => [
        // 'linkedin' => 'https://www.linkedin.com/company/…',
    ],

    'nav' => [
        ['label' => 'Home',     'route' => 'home'],
        ['label' => 'About',    'route' => 'about'],
        ['label' => 'Services', 'route' => 'services.index', 'children' => [
            ['label' => 'Architectural Visualization', 'route' => 'services.visualization', 'icon' => 'eye',  'text' => 'Interiors, exteriors, 3D floor plans'],
            ['label' => 'BIM and Revit',               'route' => 'services.bim',           'icon' => 'box',  'text' => 'CAD to BIM, scan to BIM, families'],
            ['label' => 'CAD Drafting',                'route' => 'services.cad',           'icon' => 'file-text', 'text' => 'Plans, sections, PDF to DWG'],
            ['label' => 'Production Support',          'route' => 'services.outsourcing',   'icon' => 'users', 'text' => 'Flexible extension of your studio'],
        ]],
        ['label' => 'Projects', 'route' => 'collaborations.index'],
        ['label' => 'Process',  'route' => 'process'],
        ['label' => 'Contact',  'route' => 'contact'],
    ],

    'footer_links' => [
        ['label' => 'Home',               'route' => 'home'],
        ['label' => 'About Architive',    'route' => 'about'],
        ['label' => 'Services',           'route' => 'services.index'],
        ['label' => 'Collaborations',     'route' => 'collaborations.index'],
        ['label' => 'Our Process',        'route' => 'process'],
        ['label' => 'Team',               'route' => 'team'],
        ['label' => 'FAQs',               'route' => 'faqs'],
    ],

    'standards' => [
        'AutoCAD Scaled DWG',
        'Revit LOD 300-350',
        '3ds Max & V-Ray',
        'Mutual NDA Protected',
    ],

    'software' => ['AutoCAD', 'Revit', '3ds Max', 'SketchUp', 'V-Ray', 'Corona', 'Blender'],
];

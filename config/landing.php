<?php

/*
|--------------------------------------------------------------------------
| Google Ads landing pages (/lp/{slug}/)
|--------------------------------------------------------------------------
| One focused page per ad group: one offer (free 3-day trial), proof near the top, a short form, buyer questions.
| Not in the sitemap and noindex, so they never compete with the service pages in organic search.
| Ad final URL example: https://architive.net/lp/3d-rendering/?utm_source=google&utm_medium=cpc&utm_campaign={campaignid}&utm_term={keyword}
*/

return [
    'trial' => [
        'title' => 'Free 3-Day Trial',
        'text'  => 'Send one real task. Our team works on it free for up to 3 working days, so you can judge the quality, speed and communication before you pay anything. No card, no contract.',
        'steps' => [
            ['n' => '01', 'icon' => 'upload',  'title' => 'Send one real task',   'text' => 'Upload your plans, model or sketches and tell us what you need back. A link to your files works too.'],
            ['n' => '02', 'icon' => 'message', 'title' => 'We confirm the scope', 'text' => 'Within one business day we reply with what we will deliver in the trial and when, or ask what is missing.'],
            ['n' => '03', 'icon' => 'eye',     'title' => 'Judge the work',       'text' => 'Review real output from your own project. Continue only if you are happy, with a written quote first.'],
        ],
    ],

    'pages' => [
        '3d-rendering' => [
            'title'       => '3D Rendering Services for US Architects and Designers | Free 3-Day Trial',
            'description' => 'Photoreal interior and exterior renderings from your CAD, Revit or SketchUp files. Try us free for 3 days on a real task. US company, overnight turnaround.',
            'eyebrow'     => '3D rendering for architects, designers and developers',
            'h1'          => 'Renders Your Clients <em>Say Yes To.</em>',
            'lead'        => 'Photoreal interiors and exteriors built from your drawings, matched to your finishes, ready for client sign-off or marketing. Try us free on a real project first.',
            'bullets'     => ['Interiors, exteriors and aerials', 'From CAD, Revit, SketchUp or PDF', 'Feedback in the evening, progress by morning', 'Written quote before any paid work'],
            'image'       => ['key' => 'viz/bandon-dusk-lawn', 'alt' => 'Photoreal dusk rendering of a timber and stone residence among pines, by Architive'],
            'service'     => 'visualization', 'topic' => '3D Rendering',
            'formTitle'   => 'Start your free trial',
            'placeholder' => 'For example: two interior views of a kitchen and living room from the attached plans, modern warm style, needed for a client meeting on Friday.',
            'gallery'     => ['interiors', 'exteriors'], 'galleryTitle' => 'Recent <em>Renders</em>',
            'faqs' => [
                1 => ['q' => 'What does the free 3-day trial include?', 'a' => 'One real task from your project, worked on free for up to 3 working days. For most clients that is one interior or exterior view taken to a clear draft, so you can judge quality and communication. We confirm exactly what is included before we start.'],
                2 => ['q' => 'How much do renderings cost after the trial?', 'a' => 'It depends on the number of views, how much custom furniture and context is needed, the revision rounds and the deadline. You get a written quote that lists every view, review stage and delivery date before any paid work starts.'],
                3 => ['q' => 'How fast is the turnaround?', 'a' => 'A simple view usually takes one to three working days and a complex one longer. Because our studio works while US offices are closed, comments you send at the end of your day are often worked on overnight.'],
                4 => ['q' => 'What files do you need?', 'a' => 'Plans and elevations as DWG or PDF, or your Revit, SketchUp or Rhino model, plus finish references and the camera angle you want. If you are unsure, send what you have and we will tell you what is missing.'],
                5 => ['q' => 'Are my drawings kept confidential?', 'a' => 'Yes. We can sign an NDA before you share detailed files, and access is limited to the team working on your project.'],
                6 => ['q' => 'Who is Architive?', 'a' => 'Architive LLC is a US company registered in Delaware with its own production studio. We support architecture firms, interior designers and developers with visualization, BIM and CAD drafting.'],
            ],
        ],

        '3d-floor-plans' => [
            'title'       => '3D Floor Plan Rendering for Listings and Developers | Free 3-Day Trial',
            'description' => 'Turn 2D plans, PDFs or scans into furnished 3D floor plans buyers understand in seconds. One plan or hundreds in one style. Try us free for 3 days.',
            'eyebrow'     => '3D floor plans for agents, developers and designers',
            'h1'          => 'Floor Plans Buyers Understand <em>in Seconds.</em>',
            'lead'        => 'We turn your 2D plans, PDFs or scans into furnished 3D floor plans, top-down or isometric, in one consistent style whether you need one or one hundred. Try us free first.',
            'bullets'     => ['From DWG, PDF, scan or sketch', 'Top-down and isometric views', 'One style across every plan', 'More than 500 plans made for one UK client'],
            'image'       => ['key' => 'viz/floorplan-3d-top', 'alt' => 'Top-down 3D floor plan with herringbone flooring and furniture layout, rendered by Architive'],
            'service'     => 'visualization', 'topic' => '3D Floor Plan',
            'formTitle'   => 'Start your free trial',
            'placeholder' => 'For example: 3D floor plans for 4 apartment types from the attached PDFs, top-down view, light oak floors, for a sales brochure.',
            'gallery'     => ['floorplans'], 'galleryTitle' => '3D Floor Plans <em>We Have Made</em>',
            'faqs' => [
                1 => ['q' => 'What does the free 3-day trial include?', 'a' => 'One real task, worked on free for up to 3 working days. For floor plans that is usually your first plan, taken to a finished draft in the style you want, so you can check accuracy and style before ordering more.'],
                2 => ['q' => 'Can you work from a PDF, scan or hand sketch?', 'a' => 'Yes, if the walls are clear and the key dimensions are readable. Clean DWG files are fastest. Mark anything unusual, such as steps, half-height walls or sloped ceilings.'],
                3 => ['q' => 'How much does a 3D floor plan cost after the trial?', 'a' => 'It depends on the size and complexity of the plan, the furniture and detail, and how many plans you need. Ordering many in one agreed style lowers the cost per plan. You get a written quote before any paid work.'],
                4 => ['q' => 'Can all my plans look the same?', 'a' => 'Yes. We agree colours, furniture, labels and image size on the first plan and every plan after it follows the same style sheet.'],
                5 => ['q' => 'What files will I get?', 'a' => 'High-resolution JPG or PNG files sized for your listing, brochure or print. Tell us where they will be used and we will size them for it.'],
                6 => ['q' => 'Who is Architive?', 'a' => 'Architive LLC is a US company registered in Delaware with its own production studio. We have produced more than 500 3D floor plans for a UK client alone, all in one consistent style.'],
            ],
        ],

        'cad-drafting' => [
            'title'       => 'CAD Drafting Services for US Permit Sets | Free 3-Day Trial',
            'description' => 'Permit and construction drawing sets drafted from your sketches, markups or PDFs, to your standards. Samples from NY, California and Virginia. Try us free for 3 days.',
            'eyebrow'     => 'CAD drafting for architects, designers and builders',
            'h1'          => 'Drawing Sets Done <em>While You Sleep.</em>',
            'lead'        => 'Floor plans, elevations, sections and details drafted from your sketches, markups or PDFs, to your templates and layer standards. Try us free on a real set first.',
            'bullets'     => ['Permit and construction drawing sets', 'Your templates, title blocks and layers', 'PDF and scan to DWG conversion', 'Sample sets from NY, California and Virginia'],
            'image'       => ['key' => 'sets/ny/01-main-level-plan', 'alt' => 'Main level plan sheet from a New York residential addition permit set drafted by Architive'],
            'service'     => 'cad', 'topic' => 'CAD Drafting',
            'formTitle'   => 'Start your free trial',
            'placeholder' => 'For example: redraw the attached marked-up PDF floor plan and elevations in AutoCAD using our title block, for a permit submission next week.',
            'sets'        => 'cad',
            'faqs' => [
                1 => ['q' => 'What does the free 3-day trial include?', 'a' => 'One real drafting task, worked on free for up to 3 working days, such as a floor plan and elevations from your markups. You see how closely we follow your standards before committing.'],
                2 => ['q' => 'Do you stamp or seal drawings?', 'a' => 'No. We prepare the drawings. Where local rules require drawings to be signed or sealed, your locally licensed professional reviews and stamps them.'],
                3 => ['q' => 'Will you use our templates and standards?', 'a' => 'Yes. Send your title block, layer standards and a recent example set, and we match them.'],
                4 => ['q' => 'Which software do you use?', 'a' => 'AutoCAD for 2D drafting and Revit for BIM work. We deliver DWG, PDF or RVT, whichever your team needs.'],
                5 => ['q' => 'How fast is the turnaround?', 'a' => 'It depends on the size of the set. Because our studio works while US offices are closed, markups you send at the end of your day are often drafted by the next morning.'],
                6 => ['q' => 'Who is Architive?', 'a' => 'Architive LLC is a US company registered in Delaware with its own production studio, supporting architecture firms and builders with CAD drafting, BIM and visualization.'],
            ],
        ],
    ],
];

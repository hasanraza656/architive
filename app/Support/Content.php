<?php

namespace App\Support;

/**
 * Shared page content (copy deck from the client). Kept in one place so the
 * homepage, service pages, FAQ page, schema output and footer never drift apart.
 */
class Content
{
    public static function services(): array
    {
        return [
            'visualization' => [
                'route' => 'services.visualization',
                'icon'  => 'eye',
                'title' => 'Architectural Visualization',
                'short' => 'Photorealistic interiors, exteriors, 3D floor plans and presentation visuals that help clients, investors and homeowners understand the design before it is built.',
                'items' => ['Interior and exterior renderings', '3D floor plans and axonometric views', 'Renovation and option visuals', 'Animation and 360-degree views by scope'],
                'link'  => 'Explore visualization',
                'image' => 'assets/img/work/viz/bandon-dusk-lawn',
                'alt'   => 'Architectural exterior visualization of a timber-and-stone residence at dusk',
            ],
            'bim' => [
                'route' => 'services.bim',
                'icon'  => 'box',
                'title' => 'BIM and Revit',
                'short' => 'Structured Revit models and documentation built from CAD files, surveys and point clouds, with the model purpose, version, views and standards agreed before production.',
                'items' => ['Architectural Revit modeling', 'CAD to BIM and scan to BIM', 'Plans, sections, elevations and schedules', 'Revit families and model updates'],
                'link'  => 'Explore BIM and Revit',
                'image' => 'assets/img/work/scan/house-1-model',
                'alt'   => 'Revit existing-conditions model of a historic brick house built from a point cloud',
            ],
            'cad' => [
                'route' => 'services.cad',
                'icon'  => 'file-text',
                'title' => 'CAD Drafting and Permit Support',
                'short' => 'Scaled, editable AutoCAD drawings from sketches, PDFs, surveys and markups—prepared to your title block, layers and conventions.',
                'items' => ['Floor plans, elevations and sections', 'Permit-support and construction drawing packages', 'PDF, sketch and scan to DWG', 'As-built, renovation and redline updates'],
                'link'  => 'Explore CAD drafting',
                'note'  => 'Where local regulations require drawings to be signed or sealed, we prepare the package for review by your locally licensed professional.',
                'image' => 'assets/img/work/sets/ny/01-main-level-plan',
                'alt'   => 'Main level floor plan from a residential addition permit set',
            ],
            'outsourcing' => [
                'route' => 'services.outsourcing',
                'icon'  => 'users',
                'title' => 'Architectural Production Support',
                'short' => 'A flexible extension of your studio. Bring us one defined assignment, begin with a paid pilot or use Architive as recurring production support.',
                'items' => ['One point of contact', 'Your templates and standards', 'Agreed review stages', 'Flexible project-based or ongoing support'],
                'link'  => 'Explore production support',
                'image' => 'assets/img/work/cases/manuel-house-3d',
                'alt'   => 'Revit 3D view of a two-storey house produced for a residential development team',
            ],
        ];
    }

    /** The three core services (production support is an engagement model, shown separately). */
    public static function coreServices(): array
    {
        $all = self::services();
        $gal = [
            'visualization' => [
                ['viz/bandon-dusk-lawn', 'Timber-and-stone residence at dusk, an architectural exterior visualization', false],
                ['viz/kitchen-dining', 'Kitchen and dining interior visualization', false],
                ['viz/floorplan-3d-top', 'Top-down 3D floor plan with furniture layout', true],
                ['viz/bedroom', 'Bedroom interior visualization with timber flooring', false],
            ],
            'bim' => [
                ['scan/house-1-cloud', 'Point cloud of a historic brick house captured by a surveyor', false],
                ['scan/house-1-model', 'Revit existing-conditions model built from that point cloud', false],
                ['sets/pa/01-level-1-floor-plan', 'Level 1 floor plan sheet from a Revit existing-conditions set', true],
                ['cases/manuel-house-3d', 'Revit 3D view of a double-storey house', true],
            ],
            'cad' => [
                ['sets/ny/01-main-level-plan', 'Main level plan from a residential addition permit set', true],
                ['sets/caa/01-main-floor-plan', 'Floor plan with schedules from a residential addition permit set', true],
                ['sets/car/01-proposed-main-level-plan', 'Proposed main level plan from a residential remodel permit set', true],
                ['sets/ny/03-elevations-front-back', 'Front and back exterior elevations from a permit set', true],
            ],
        ];
        $out = [];
        foreach (['visualization', 'bim', 'cad'] as $k) {
            $svc = $all[$k];
            $svc['key'] = $k;
            $svc['gallery'] = array_map(fn ($g) => Portfolio::img($g[0]) + ['alt' => $g[1], 'sheet' => $g[2]], $gal[$k]);
            $out[$k] = $svc;
        }

        return $out;
    }

    public static function productionSupport(): array
    {
        return self::services()['outsourcing'];
    }

    /** Engagement options (production support is how clients work with us, not a fourth service). */
    public static function engagements(): array
    {
        return [
            ['key' => 'pilot',    'title' => 'Pilot',            'badge' => 'Free start', 'text' => 'Test communication, standards and output on one defined task. Free start.', 'best' => 'Testing communication, standards and output quality', 'scope' => 1, 'commit' => 1, 'ic' => 'target'],
            ['key' => 'project',  'title' => 'Defined project',  'text' => 'Fixed scope, milestones and deliverables.', 'best' => 'A clearly bounded set of drawings, models or visuals', 'scope' => 3, 'commit' => 2, 'ic' => 'file-text'],
            ['key' => 'hourly',   'title' => 'Hourly support',   'text' => 'Flexible hours whenever you need an extra pair of hands. Rates start from $16 per hour.', 'best' => 'Short tasks, overflow work and ad-hoc production help', 'scope' => 2, 'commit' => 1, 'ic' => 'clock', 'badge' => 'From $16 / hour'],
            ['key' => 'ongoing',  'title' => 'Ongoing support',  'text' => 'Recurring CAD, BIM or visualization capacity.', 'best' => 'Steady production capacity alongside your own team', 'scope' => 4, 'commit' => 4, 'ic' => 'refresh'],
            ['key' => 'priority', 'title' => 'Priority support', 'text' => 'Planned assistance during deadline peaks, subject to availability.', 'best' => 'Workload peaks you can see coming', 'scope' => 2, 'commit' => 3, 'ic' => 'zap'],
        ];
    }

    public static function process(): array
    {
        return [
            ['n' => '01', 'title' => 'Share the project', 'text' => 'Send the available plans, models, markups, references and required outcome.', 'icon' => 'upload'],
            ['n' => '02', 'title' => 'Define the scope', 'text' => 'We identify missing information and confirm deliverables, standards, schedule, price and revision structure.', 'icon' => 'ruler'],
            ['n' => '03', 'title' => 'Approve and begin', 'text' => 'Work starts after you approve the written scope and payment arrangement.', 'icon' => 'check'],
            ['n' => '04', 'title' => 'Review progress', 'text' => 'You receive updates at agreed checkpoints and send one consolidated set of comments per review round.', 'icon' => 'refresh'],
            ['n' => '05', 'title' => 'Approve and receive final files', 'text' => 'We complete the agreed revisions, run the final check and deliver the specified formats.', 'icon' => 'file'],
        ];
    }

    public static function audiences(): array
    {
        return [
            ['icon' => 'building', 'title' => 'Architecture Firms', 'text' => 'CAD drafting, Revit production and visualization capacity for live projects, deadlines and workload peaks.'],
            ['icon' => 'layers', 'title' => 'Interior Design Studios', 'text' => 'Floor plans, elevations, working drawings and client-ready visuals that carry an approved concept into clear project information.'],
            ['icon' => 'ruler', 'title' => 'Developers and Contractors', 'text' => 'Coordinated drawings, models and presentation visuals for approvals, procurement, investor communication and pre-construction marketing.'],
            ['icon' => 'home', 'title' => 'Homeowners', 'text' => 'Drawings and realistic visuals for renovations, additions and new homes, so key decisions are clearer before construction begins.'],
        ];
    }

    /** Core FAQs. `cat` powers the filter chips on the FAQ page. */
    public static function faqs(): array
    {
        return [
            1 => ['cat' => 'General & Locations',    'q' => 'Can Architive follow our office standards?', 'a' => 'Yes. Share your templates, title blocks, sample sheets, layer conventions, Revit structure and naming standards. We confirm what applies before production begins.'],
            2 => ['cat' => 'General & Locations',    'q' => 'Can we begin with a small assignment?',       'a' => 'Yes. A defined paid pilot is often the best way to test communication, standards and output quality before a larger engagement.'],
            3 => ['cat' => 'Services Scope',         'q' => 'Do you provide architectural design?',        'a' => "Architive's core role is architectural production support. We turn approved designs, project information and client direction into drawings, models and visuals. Any design responsibility is agreed explicitly in writing."],
            4 => ['cat' => 'Services Scope',         'q' => 'Do you provide permit drawings?',             'a' => 'We prepare permit-support drawing packages using the information and local requirements provided. Where law requires a local license, signature or seal, the client appoints the appropriate professional.'],
            5 => ['cat' => 'Pricing & NDAs',         'q' => 'How are projects priced?',                    'a' => 'Pricing depends on the source information, deliverables, level of detail, schedule and revision structure. You receive a written quotation before work begins. Hourly support is also available, with rates starting from $16 per hour.'],
            6 => ['cat' => 'Workflow & Standards',   'q' => 'How are revisions handled?',                  'a' => 'The quotation states the included review stages and revision rounds. Corrections to missed agreed instructions are completed at no charge; new scope is quoted before proceeding.'],
            7 => ['cat' => 'Pricing & NDAs',         'q' => 'Can you sign an NDA?',                        'a' => 'Yes. We can review and sign an NDA before detailed project information is shared.'],
            8 => ['cat' => 'Workflow & Standards',   'q' => 'What files can you deliver?',                 'a' => 'Typical formats include DWG and PDF for CAD, RVT and PDF for BIM, and JPG, PNG or video for visualization. The exact deliverables are listed in the quotation.'],
        ];
    }

    public static function faqCategories(): array
    {
        return ['General & Locations', 'Workflow & Standards', 'Services Scope', 'Pricing & NDAs'];
    }

    /** Pick FAQs by id, preserving order. */
    public static function faqsById(array $ids): array
    {
        $all = self::faqs();
        $out = [];
        foreach ($ids as $id) {
            if (isset($all[$id])) {
                $out[$id] = $all[$id];
            }
        }
        return $out;
    }

    public static function collaborations(): array
    {
        return [
            'bonderud-design-visualization' => [
                'key'      => 'visualization',
                'tag'      => 'Visualization',
                'num'      => '001',
                'name'     => 'Bonderud Design',
                'title'    => 'Visualization Support for Bonderud Design',
                'place'    => 'California, USA',
                'meta'     => 'California, USA · Visualization support',
                'client'   => 'Interior design studio, San Francisco, California',
                'situation' => 'The studio needed dependable visualization support after its long-term rendering partner became unavailable.',
                'role'     => 'Translate approved kitchen drawings, appliance information, material direction and site photographs into client-ready interior visuals.',
                'deliverables' => ['Grayscale model reviews', 'Material and colour options', 'Realistic final views', 'Close-up presentation images'],
                'note'     => "Architive supported the interior designer's client presentation. This was visualization support, not CAD production or authorship of the design.",
                'service_route' => 'services.visualization',
                'service_label' => 'Architectural visualization',
                'description' => 'How Architive supported San Francisco interior design studio Bonderud Design with grayscale reviews, material options and realistic kitchen visuals.',
            ],
            'fifa-2026-circulation-plan-drafting' => [
                'key'      => 'cad',
                'tag'      => 'CAD',
                'num'      => '002',
                'name'     => 'FIFA 2026 Project (through VESTI Events)',
                'title'    => 'Circulation-Plan Drafting for a FIFA 2026 Project',
                'place'    => 'North America',
                'meta'     => 'North America · 2D circulation-plan drafting',
                'client'   => 'Work completed through VESTI Events',
                'situation' => 'The event-planning team required clear 2D circulation drawings to communicate movement, access and operational planning.',
                'role'     => 'Produce accurate CAD circulation plans from the approved information and project markups.',
                'deliverables' => ['2D CAD circulation plans'],
                'note'     => 'Architive supported VESTI Events and was not contracted directly by FIFA.',
                'service_route' => 'services.cad',
                'service_label' => 'CAD drafting',
                'description' => 'How Architive drafted accurate 2D circulation plans for a FIFA 2026 project, working with the event-planning team at VESTI Events.',
            ],
            'manuel-development-revit-support' => [
                'key'      => 'bim',
                'tag'      => 'BIM',
                'num'      => '003',
                'name'     => 'Manuel Development',
                'title'    => 'Revit Production Support for Manuel Development',
                'place'    => 'New Brunswick, Canada',
                'meta'     => 'New Brunswick, Canada · Revit production support',
                'client'   => 'Residential development company',
                'situation' => 'The development team required structured Revit production support for residential plan information.',
                'role'     => 'Develop and update architectural Revit content in line with the agreed drawings, model requirements and internal review comments.',
                'deliverables' => ['Revit model updates', 'Agreed architectural views or documentation'],
                'note'     => null,
                'service_route' => 'services.bim',
                'service_label' => 'BIM and Revit',
                'description' => 'How Architive provided structured architectural Revit production support for residential development company Manuel Development.',
            ],
        ];
    }

    /** Real client reviews from Fiverr and Upwork (screenshots in public/assets/reviews), shown as an accessible slider. */
    public static function testimonials(): array
    {
        return [
            ['initial' => 'D', 'name' => 'designbyapa',    'source' => 'Fiverr · United States', 'stars' => 5,
             'quote' => 'I am in awe of Madiha. I had sent her so many small pictures of my blueprints which were from 20 years ago. She was able to figure out everything and delivered a really impressive floor plan. I will definitely refer her to other friends who are planning to sell their houses.'],
            ['initial' => 'F', 'name' => 'flose1007',      'source' => 'Fiverr · Germany', 'stars' => 5,
             'quote' => 'Great work with Madiha. Kept me updated and fulfilled my wishes. Results are great.'],
            ['initial' => 'M', 'name' => 'markwint284',    'source' => 'Fiverr · United Kingdom', 'stars' => 5,
             'quote' => 'Excellent delivery as always.'],
            ['initial' => 'J', 'name' => 'jackcronin878',  'source' => 'Fiverr · United States', 'stars' => 5,
             'quote' => 'Repeat customer, just as satisfied as the first time!'],
            ['initial' => 'J', 'name' => 'jurgentabaku',   'source' => 'Fiverr · United States', 'stars' => 5,
             'quote' => 'Second time working with Madiha and I continue to be impressed by her work and attention to detail. It has been a very collaborative process and I look forward to working with her again in the future. Would definitely recommend.'],
            ['initial' => 'T', 'name' => 'teunrietdijk',   'source' => 'Fiverr · Netherlands', 'stars' => 5,
             'quote' => "Once again absolutely perfect. Delivering the 3D floor plan and the 3D renders with such precision and creativity is truly impressive. You always capture exactly what's needed and elevate it beyond expectations. Every detail—structure, angles, lighting, flow—lands perfectly."],
            ['initial' => 'U', 'name' => 'Upwork client',  'source' => 'Upwork · 3D Designs for Digital Events', 'stars' => 5,
             'quote' => 'Always good to know I can rely on Madiha when I have a new event. Thank you.'],
            ['initial' => 'U', 'name' => 'Upwork client',  'source' => 'Upwork · Redo Plans in PDF and CAD', 'stars' => 5,
             'quote' => 'Madiha does wonderful work, on time and with great communication!'],
            ['initial' => 'U', 'name' => 'Upwork client',  'source' => 'Upwork · Draftsman/Designer, Commercial Test-Fits', 'stars' => 5,
             'quote' => 'Madiha did an excellent job creating our preliminary industrial layout drawings. She understood the project requirements quickly, communicated clearly throughout the process, and delivered accurate, well-organized plans on time. Highly recommended for any drafting or layout projects!'],
        ];
    }
}

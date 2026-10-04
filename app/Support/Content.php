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
                'image' => 'assets/img/photos/exterior-modern-house',
                'alt'   => 'Modern residence with landscaped garden, an example of an architectural exterior view',
            ],
            'bim' => [
                'route' => 'services.bim',
                'icon'  => 'box',
                'title' => 'BIM and Revit',
                'short' => 'Structured Revit models and documentation built from CAD files, surveys and point clouds, with the model purpose, version, views and standards agreed before production.',
                'items' => ['Architectural Revit modeling', 'CAD to BIM and scan to BIM', 'Plans, sections, elevations and schedules', 'Revit families and model updates'],
                'link'  => 'Explore BIM and Revit',
                'image' => 'assets/img/photos/bim-house-model',
                'alt'   => 'White three-storey house model with blue and timber accents',
            ],
            'cad' => [
                'route' => 'services.cad',
                'icon'  => 'file-text',
                'title' => 'CAD Drafting and Permit Support',
                'short' => 'Scaled, editable AutoCAD drawings from sketches, PDFs, surveys and markups—prepared to your title block, layers and conventions.',
                'items' => ['Floor plans, elevations and sections', 'Permit-support and construction drawing packages', 'PDF, sketch and scan to DWG', 'As-built, renovation and redline updates'],
                'link'  => 'Explore CAD drafting',
                'note'  => 'Where local regulations require drawings to be signed or sealed, we prepare the package for review by your locally licensed professional.',
                'image' => 'assets/img/photos/cad-floor-plan',
                'alt'   => 'Architectural floor plan drawing on paper',
            ],
            'outsourcing' => [
                'route' => 'services.outsourcing',
                'icon'  => 'users',
                'title' => 'Architectural Production Support',
                'short' => 'A flexible extension of your studio. Bring us one defined assignment, begin with a paid pilot or use Architive as recurring production support.',
                'items' => ['One point of contact', 'Your templates and standards', 'Agreed review stages', 'Flexible project-based or ongoing support'],
                'link'  => 'Explore production support',
                'image' => 'assets/img/photos/site-cranes',
                'alt'   => 'Tower cranes on a construction site against a pale sky',
            ],
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
            5 => ['cat' => 'Pricing & NDAs',         'q' => 'How are projects priced?',                    'a' => 'Pricing depends on the source information, deliverables, level of detail, schedule and revision structure. You receive a written quotation before work begins.'],
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

    public static function slides(): array
    {
        return [
            [
                'quote'  => 'Architive integrated seamlessly into our studio drafting pipeline. Their attention to our layer templates and turnaround speed allowed us to deliver commercial sets on schedule without adding permanent staff.',
                'name'   => 'David Sterling, AIA',
                'role'   => 'Managing Principal · Sterling & Associates Architecture (New York, USA)',
                'tag'    => 'BIM & Revit support · Verified client',
            ],
            [
                'quote'  => 'We clarify before we assume. Missing dimensions and conflicting information are raised early, so the set you receive is built on agreed information.',
                'name'   => 'How we work',
                'role'   => 'Architive working principle',
                'tag'    => 'Clarify · Confirm · Deliver',
            ],
            [
                'quote'  => 'If we miss an agreed instruction, we correct it without charging for our oversight.',
                'name'   => 'Our commitment',
                'role'   => 'Architive working principle',
                'tag'    => 'We take responsibility',
            ],
        ];
    }
}

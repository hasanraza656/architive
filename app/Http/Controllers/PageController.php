<?php

namespace App\Http\Controllers;

use App\Support\Content;

class PageController extends Controller
{
    public function home()          { return view('pages.home'); }
    public function about()         { return view('pages.about'); }
    public function services()      { return view('pages.services'); }
    public function visualization() { return view('pages.visualization'); }
    public function bim()           { return view('pages.bim'); }
    public function cad()           { return view('pages.cad'); }
    /** Production support is no longer a separate page; the old URL permanently redirects to Services. */
    public function outsourcing()   { return redirect()->away(pu('services.index'), 301); }
    public function process()       { return view('pages.process'); }
    public function team()          { return view('pages.team'); }
    public function faqs()          { return view('pages.faqs'); }
    public function contact()       { return view('pages.contact'); }
    public function privacy()       { return view('pages.privacy'); }
    public function terms()         { return view('pages.terms'); }
    public function sitemap()       { return view('pages.sitemap'); }

    public function collaborations()
    {
        return view('pages.collaborations');
    }

    public function collaboration(string $slug)
    {
        $all = Content::collaborations();
        abort_unless(isset($all[$slug]), 404);

        return view('pages.collaboration', [
            'slug' => $slug,
            'c'    => $all[$slug],
            'seo'  => [
                'title'       => $all[$slug]['title'] . ' | Architive',
                'description' => $all[$slug]['description'],
                'image'       => ['bonderud-design-visualization' => 'assets/img/og/case-bonderud.jpg', 'fifa-2026-circulation-plan-drafting' => 'assets/img/og/case-fifa.jpg', 'manuel-development-revit-support' => 'assets/img/og/case-manuel.jpg'][$slug] ?? config('seo.default_image'),
            ],
        ]);
    }
}

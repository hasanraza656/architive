<?php

namespace App\Http\Controllers;

use App\Support\Content;

class PageController extends Controller
{
    public function home()          { return view('pages.home'); }
    public function about()         { return view('pages.about'); }
    public function services()      { return view('pages.services'); }
    public function visualization() { return view('pages.visualization'); }
    public function exterior()      { return view('pages.exterior'); }
    public function interior()      { return view('pages.interior'); }
    public function floorplans()    { return view('pages.floorplans'); }
    public function rayon()         { return view('pages.rayon'); }
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

    /** Google Ads landing page: one offer, proof, short form. Content in config/landing.php. */
    public function landing(string $slug)
    {
        $p = config("landing.pages.{$slug}") ?? abort(404);

        return view('pages.landing', ['slug' => $slug, 'p' => $p, 'trial' => config('landing.trial'), 'lp' => true,
            'seo' => ['title' => $p['title'], 'description' => $p['description'], 'robots' => 'noindex,follow']]);
    }

    /** Where ad landing page forms finish (?ref = request number). */
    public function thanks()
    {
        $ref = preg_match('/^[A-Z0-9\-]{3,30}$/', (string) request('ref')) ? request('ref') : null;

        return view('pages.thanks', ['ref' => $ref, 'lp' => true,
            'seo' => ['title' => 'Thank You | Architive', 'description' => 'Your enquiry is in.', 'robots' => 'noindex,nofollow']]);
    }

    public function collaborations()
    {
        return view('pages.collaborations');
    }

    public function collaboration(string $slug)
    {
        // a client asked not to be named: the old address now forwards to the collaborations list
        if ($slug === 'bonderud-design-visualization') {
            return redirect()->away(abs_pu('collaborations.index'), 301);
        }

        $all = Content::collaborations();
        abort_unless(isset($all[$slug]), 404);

        return view('pages.collaboration', [
            'slug' => $slug,
            'c'    => $all[$slug],
            'seo'  => [
                'title'       => $all[$slug]['title'] . ' | Architive',
                'description' => $all[$slug]['description'],
                'image'       => ['uk-3d-floor-plans-smart-heating-cooling' => 'assets/img/og/case-uk-floorplans.jpg', 'texas-ongoing-visualization-support' => 'assets/img/og/case-texas.jpg', 'fifa-2026-circulation-plan-drafting' => 'assets/img/og/case-fifa.jpg', 'manuel-development-revit-support' => 'assets/img/og/case-manuel.jpg'][$slug] ?? config('seo.default_image'),
            ],
        ]);
    }
}

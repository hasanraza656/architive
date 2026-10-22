<?php

/*
|--------------------------------------------------------------------------
| Old site addresses -> current pages (permanent, one hop)
|--------------------------------------------------------------------------
| The site used to run on WordPress. Google and other sites still know these
| addresses, so each one answers 301 to the closest current page instead of 404.
| Keys have no leading or trailing slash; Laravel matches the address with or
| without a trailing slash. Add a line here for any further old address.
*/

return [
    // Company pages
    'about'       => '/about-us/',
    'who-we-are'  => '/about-us/',
    'contact-us'  => '/contact/',
    'contacts'    => '/contact/',
    'my-account'  => '/contact/',
    'our-services' => '/services/',

    // Service pages
    '3d-floor-plans'              => '/3d-floor-plan-rendering-services/',
    '3d-interior-design'          => '/interior-rendering-services/',
    '3d-rendering-interior-design' => '/interior-rendering-services/',
    'interior-design'             => '/interior-rendering-services/',
    'exterior-design'             => '/exterior-rendering-services/',
    // Old portfolio pages still shown in Google (Search Console, Jul-Oct 2026: 279 and 102+ impressions)
    'exterior-visualizations'     => '/exterior-rendering-services/',
    'interior-visualizations'     => '/interior-rendering-services/',
    // Old WordPress pages still in Google's index (Search Console, 22 Oct 2026)
    'interior-design-visualization'        => '/interior-rendering-services/',
    'sketchup-modeling-rendering'          => '/architectural-visualization-rendering/',
    'renovation-remodeling-visualizations' => '/architectural-visualization-rendering/',
    'projects'                             => '/collaborations/',
    'team-details'                         => '/team/',
    'home'                                 => '/',
    'home-designs'                => '/architectural-visualization-rendering/',
    '2d-floor-plans'              => '/cad-drafting-services/',

    // Old blog categories
    'category/architectural-render'        => '/blog/category/architectural-visualization/',
    'category/architectural-visualization' => '/blog/category/architectural-visualization/',
    'category/interior-design'             => '/blog/category/architectural-visualization/',

    // Old articles (closest current article, or the category when none fits)
    'what-constitutes-a-perfect-architectural-render' => '/blog/how-to-brief-a-rendering-team/',
    'why-do-clients-prefer-architectural-visualization' => '/blog/architectural-visualization-cost-guide/',
    'why-3d-rendering-is-crucial-for-interior-design-projects' => '/blog/category/architectural-visualization/',
    'why-should-we-all-get-excited-about-the-future-of-architectural-rendering' => '/blog/category/architectural-visualization/',
    'experience-your-interior-design-ideas-like-never-before-with-architives-walk-throughs' => '/blog/category/architectural-visualization/',
    'revamp-your-interior-designs-with-architive-3d-interior-design-artists' => '/blog/category/architectural-visualization/',
];

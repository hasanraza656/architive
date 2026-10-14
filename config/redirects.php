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
    '3d-floor-plans'              => '/architectural-visualization-rendering/',
    '3d-interior-design'          => '/architectural-visualization-rendering/',
    '3d-rendering-interior-design' => '/architectural-visualization-rendering/',
    'interior-design'             => '/architectural-visualization-rendering/',
    'exterior-design'             => '/architectural-visualization-rendering/',
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

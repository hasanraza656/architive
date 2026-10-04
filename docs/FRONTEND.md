# Architive – Frontend handover

Laravel 10 · Blade · Bootstrap 5.3 · jQuery 3.7 · self-hosted fonts/libs (no CDN, no build step).
Run locally: `php artisan serve` → http://127.0.0.1:8000

## URL structure (one permanent, lowercase, trailing-slash URL per page)

| URL | Route name | View |
|---|---|---|
| `/` | `home` | `pages/home` |
| `/about-us/` · `/team/` | `about` · `team` | `pages/about` · `pages/team` |
| `/services/` | `services.index` | `pages/services` |
| `/architectural-visualization-rendering/` | `services.visualization` | `pages/visualization` |
| `/bim-revit-scan-to-bim/` | `services.bim` | `pages/bim` |
| `/cad-drafting-services/` | `services.cad` | `pages/cad` |
| `/architectural-outsourcing/` | `services.outsourcing` | `pages/outsourcing` |
| `/collaborations/` · `/collaborations/{slug}/` | `collaborations.index` · `.show` | `pages/collaborations` · `pages/collaboration` |
| `/how-it-works/` · `/faqs/` · `/contact/` | `process` · `faqs` · `contact` | `pages/*` |
| `/privacy-policy/` · `/terms-of-service/` · `/sitemap/` | `privacy` · `terms` · `sitemap.html` | `pages/*` |
| `/sitemap.xml` · `/robots.txt` | `sitemap.xml` · `robots` | generated (SeoController) |

* Slash-less URLs 301 to the slashed URL (`EnsureTrailingSlash` middleware; the default Laravel slash-stripping rule was removed from `public/.htaccess`).
* Always generate links with `pu('route.name')` / `abs_pu()` (in `app/helpers.php`) so the trailing slash is kept.

## Where to edit things

| What | Where |
|---|---|
| Brand facts, email, addresses, nav, footer, software list, social profiles | `config/site.php` |
| Page `<title>`, meta description, OG image, sitemap priority | `config/seo.php` (keyed by route name) |
| Services, process steps, audiences, FAQs, collaborations, quotes | `app/Support/Content.php` |
| Page copy | `resources/views/pages/*.blade.php` |
| Shared blocks (hero, CTA, FAQ list, schema, chat, header/footer) | `resources/views/partials/*`, `components/*` |
| Design tokens (colours, fonts, radii), header, footer, motion primitives | `public/assets/css/base.css` |
| Home + shared sections | `public/assets/css/pages.css` |
| Interactive widgets + inner pages | `public/assets/css/widgets.css` |
| Core JS (theme, reveal, counters, transitions, chat, FAQ, slider) | `public/assets/js/app.js` |
| Page widgets (compare slider, CAD layers, time zones, picker, form) | `public/assets/js/interactive.js` |

Colours (from the client dummy): off-white `#FBFBF9`, ink `#141414`, signal yellow `#FFD60A`. Fonts: Playfair Display (headings), Plus Jakarta Sans (body), JetBrains Mono (labels). Dark mode via the header toggle (`data-theme`).

## SEO built in

Unique title/description/canonical per page · Open Graph + Twitter cards (per-page 1200×630 crops in `public/assets/img/og/`) · JSON-LD graph (Organization/ProfessionalService, WebSite, WebPage types, BreadcrumbList, Service on the 4 service pages, FAQPage wherever FAQs are visible) · dynamic `sitemap.xml` + `robots.txt` · semantic landmarks, one `<h1>` per page, skip link, descriptive alt text, explicit image sizes, lazy-loading, preloaded hero + fonts, self-hosted WOFF2 fonts, immutable asset caching + gzip in `.htaccess`, `noindex` on the 404 page.

## Contact form (front-end phase)

`POST /contact/send` validates server-side (+ honeypot, throttle 6/min, CSRF) and returns JSON; it **only logs** the enquiry (`storage/logs/laravel.log`). Backend phase: persist + send notification/auto-reply mail in `ContactController@store`.

## Content to verify before launch (from the client copy deck)

* "1,500+ projects", "1,000+ client reviews", "12+ specialists" – marked *[verify before publishing]* in the copy.
* The David Sterling, AIA quote (from the client dummy) – needs written permission / confirmation. The two other slides are Architive's own working principles, not testimonials.
* Collaboration names (Bonderud Design, VESTI Events / FIFA 2026, Manuel Development) – permission for names/logos; FIFA wording kept as "through VESTI Events".
* Pakistan studio / Delaware company wording; privacy policy and terms are drafts and need legal review.
* Team page uses the studio structure + founder monogram: add real portraits/bios when available (copy deck asks to avoid stock people imagery).
* Add real social profile URLs in `config/site.php` (`social`) to emit `sameAs`.

## Image credits (Pexels licence – free to use, attribution not required)

Home hero image: client dummy PDF. Other photography (illustrative only – never presented as Architive project work):

| File | Photographer | Source |
|---|---|---|
| exterior-modern-house.webp | Max Vakhtbovych | https://www.pexels.com/photo/yard-of-contemporary-house-with-trees-outside-fence-7031581/ |
| interior-kitchen-render.webp | Max Vakhtbovych | https://www.pexels.com/photo/view-of-a-modern-kitchen-interior-7587864/ |
| bim-house-model.webp | Mahmoud Ramadan | https://www.pexels.com/photo/modern-architectural-model-with-blue-accents-36444550/ |
| bim-steel-structure.webp | Laura Cleffmann | https://www.pexels.com/photo/structural-steel-frame-against-clear-sky-31197870/ |
| bim-grid-facade.webp | Jan van der Wolf | https://www.pexels.com/photo/lines-and-squares-9259485/ |
| cad-drafting-desk.webp | Czapp Árpád | https://www.pexels.com/photo/an-architect-working-at-a-drafting-table-17077374/ |
| cad-floor-plan.webp | Ivan S | https://www.pexels.com/photo/a-photo-of-a-floor-plan-4458205/ |
| cad-plan-pen.webp | Anete Lusina | https://www.pexels.com/photo/architecture-plan-on-wooden-surface-4792483/ |
| blueprint-tools.webp | Thirdman | https://www.pexels.com/photo/kraft-paper-jar-on-scattered-white-papers-5583253/ |
| renovation-interior.webp | Monica Silvestre | https://www.pexels.com/photo/renovations-in-a-house-4756489/ |
| renovation-room.webp | Valentin Ivantsov | https://www.pexels.com/photo/modern-interior-kitchen-under-renovation-36035073/ |
| site-cranes.webp | Mike van Schoonderwalt | https://www.pexels.com/photo/yellow-tower-cranes-5504388/ |
| glass-towers.webp | Masood Aslami | https://www.pexels.com/photo/low-angle-shot-of-glass-architecture-in-perspective-8552464/ |
| city-towers.webp | Evgenia Kirpichnikova | https://www.pexels.com/photo/white-and-green-building-with-glass-panels-under-blue-sky-5955892/ |
| developer-tower-frame.webp | Rodomir Chapygin | https://www.pexels.com/photo/skyscrapers-under-construction-16878646/ |
| contact-glass.webp | Héctor Berganza | https://www.pexels.com/photo/modern-architecture-in-urban-skyline-33279640/ |
| exterior-residence.webp | edithub pro | https://www.pexels.com/photo/modern-house-building-18273285/ |

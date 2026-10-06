# CLAUDE.md – Architive project memory

Read this first in every new session. It holds everything learned so far about this project.
(No secrets belong here. Never commit `.env`, API keys or tokens.)

## 1. What this is
Marketing website for **Architive** – an architectural production studio (architectural visualization, BIM & Revit, CAD drafting + "production support" engagement model). Audience: architecture firms, interior studios, developers/contractors; homeowners secondary.
Stack: **Laravel 10 · PHP 8.1 · Blade · Bootstrap 5.3 · jQuery 3.7**. No build step (no Vite/npm needed at runtime).
Repo: https://github.com/hasanraza656/architive (branch `main`). Local path: `F:\bhai log\Architive designs\code`.

**Status:** Frontend phase is **complete and QA'd** (mobile, dark mode, interactions, SEO audit all passing). **Phase 3 (client feedback, Oct 2026) is implemented:** overlay header on hero pages, enquiry pop-up, new home flow, hourly option, real track-record figures, Team page parked. Contact-form email + reCAPTCHA v2 + Tawk.to chat are live.
**2026-10-05 update:** client's second data batch (visualization renders, permit sets, Revit/BIM sets, case-study boards + video) is integrated as a portfolio system – see §12. Local commit only unless the user asks to push (assets include redacted client sheets; confirm the GitHub repo is private first).
**Next phases (not started):** contact-form backend (store + email), content updates after client confirms items in §9, deployment.

## 2. The user
- Name on GitHub: **hasanraza656** (new account; old one `hasanraza65`). Windows 10, VS Code.
- Prefers **simple, easy wording**, step-by-step guidance for non-technical things (e.g. Windows Credential Manager).
- Wants: "perfect", highly responsive, attractive, animated, fast, 100% SEO – done without stopping or asking for permission on obvious choices.
- Git on this PC once had the old account's login stored in Windows Credential Manager (`git:https://github.com`); that caused a 403 push. User cleared it and signed in as hasanraza656; pushes now work. Commit author may still show the old account unless they changed `git config user.name/user.email`.
- They once supplied a Pexels API key to download stock photos. **Never store it in the repo/memory**; ask again if more photos are needed.

## 3. Client source material (outside repo)
`F:\bhai log\Architive designs\client data\`
- `Architive_High_End_Website_Copy.docx` – approved copy, per-page SEO titles/descriptions, URL structure, FAQs, implementation checklist.
- `Architive x - Keyword Research Website.docx` – keywords per page (use naturally, never stuff).
- `Website dummy v2.pdf` – 5-page screenshot of the approved design (hero image extracted from it → `public/assets/img/hero*.webp`).

## 4. Design system (match the client dummy exactly)
- Colours: bg `#FBFBF9`, alt `#F4F4F0`, ink `#141414`, signal yellow **`#FFD60A`**, dark sections `#141414`. Dark mode via `data-theme` on `<html>` (toggle in header, saved in localStorage `architive-theme`).
- Fonts (self-hosted WOFF2 in `public/assets/fonts`): **Playfair Display** (headings, italic emphasis `<em>`), **Plus Jakarta Sans** (body), **JetBrains Mono** (labels, uppercase, wide tracking).
- Look: rounded cards, mono uppercase labels with a short yellow line (`.eyebrow`), yellow pill buttons (`.btn-ay`), yellow italic emphasis on dark sections.
- Tokens live in `public/assets/css/base.css` (`:root` / `[data-theme="dark"]`).

## 4b. Phase 3 structure (client feedback, Oct 2026)
- **Home order:** hero ("One brief. One team.", button opens form) → software ticker → 3 core services (cards with auto-fading galleries of real work, "Explore more" + "Start a project") → production-support **horizontal strip** (`partials/support-strip`) → collaborations → "Who We Are and What We Do" (story + audiences + track record) → FAQ → CTA band.
- **Enquiry pop-up:** `partials/enquiry-modal` (not rendered on /contact/). `app.js` intercepts every link to `/contact/` (except nav/footer/breadcrumb/legal/`data-no-modal`) and opens it; `?service=` / `?audience=` pre-fill, `data-topic="…"` on the link is sent as "Situation selected" in the admin email. `window.architiveEnquiry.open({service,audience,topic})`. Without JS the links still go to /contact/. reCAPTCHA is lazy-loaded (`architiveCaptchaLoad`) when the pop-up opens or the contact page loads.
- **Overlay header:** pass `['overlay' => true]` as 2nd arg of `@extends('layouts.app', …)` on pages that start with a hero → body class `has-overlay`, header is fixed + transparent until scrolled (CSS in `home.css`). Contact/legal/sitemap pages keep the normal sticky header.
- **Track record** (Fiverr 1,200+, Upwork 130+, direct clients 4–5) lives in `config/site.php` (`track_record`) and is shown ONCE (home "Who We Are"). Review counts are intentionally not shown until the client confirms them. Don't re-add 1,500+/1,000+.
- **Hourly option:** "from $16 per hour" (`config('site.hourly_from')`, `Content::engagements()`), shown in the production-support strip, outsourcing page tabs, schema and FAQ 5.
- **Team page** is parked: still routed but `noindex,follow`, not in nav/footer/sitemap. Re-enable when the client supplies real portraits.
- Services page picker ("Where are you right now?") opens the pop-up with the service pre-selected.
- Gotcha: jQuery events don't expose `e.defaultPrevented` → use `e.isDefaultPrevented()` (the page-transition handler relies on this).

## 5. URL structure (one permanent lowercase **trailing-slash** URL per page)
`/` · `/about-us/` · `/team/` · `/services/` · `/architectural-visualization-rendering/` · `/bim-revit-scan-to-bim/` · `/cad-drafting-services/` · `/architectural-outsourcing/` · `/collaborations/` · `/collaborations/{slug}/` (bonderud-design-visualization, fifa-2026-circulation-plan-drafting, manuel-development-revit-support) · `/how-it-works/` · `/faqs/` · `/contact/` · `/privacy-policy/` · `/terms-of-service/` · `/sitemap/` · `/sitemap.xml` · `/robots.txt`.
Route names: `home, about, team, services.index, services.visualization, services.bim, services.cad, services.outsourcing, collaborations.index, collaborations.show, process, faqs, contact, contact.send (POST /contact/send), privacy, terms, sitemap.html, sitemap.xml, robots`.

## 6. Code map
| Need | File |
|---|---|
| Brand facts, email, addresses, nav, footer links, software list, social (`sameAs`) | `config/site.php` |
| Per-page title/description/OG image/sitemap priority (keyed by route name) | `config/seo.php` |
| Services, process steps, audiences, 8 FAQs, collaborations, quote slides | `app/Support/Content.php` |
| Page copy | `resources/views/pages/*.blade.php` |
| Shared blocks (hero, CTA band, FAQ list+schema, process steps, schema, Tawk loader (`partials/tawk`), header, footer, breadcrumbs, diagrams) | `resources/views/partials/*`, `resources/views/components/*` (`x-icon`, `x-logo`, `x-compare`) |
| Layout / `<head>` / SEO tags | `resources/views/layouts/app.blade.php` |
| SEO meta + breadcrumbs resolver (view composer) | `app/Providers/AppServiceProvider.php` |
| URL helpers `pu()`, `abs_pu()`, `asset_v()`, `is_page()` | `app/helpers.php` (autoloaded via composer.json) |
| Trailing-slash 301 + security headers | `app/Http/Middleware/EnsureTrailingSlash.php`, `SecurityHeaders.php` |
| Contact form: validate + honeypot + throttle + **reCAPTCHA v2** + **admin email notification** | `app/Http/Controllers/ContactController.php`, `app/Rules/Recaptcha.php`, `app/Mail/ContactEnquiry.php`, `resources/views/emails/contact-enquiry*.blade.php`, tests in `tests/Feature/ContactFormTest.php` |
| Sitemap/robots | `app/Http/Controllers/SeoController.php`, `resources/views/seo/sitemap.blade.php` |
| CSS | `public/assets/css/base.css` (tokens, header, footer, motion), `pages.css` (home + shared sections), `widgets.css` (interactive widgets + inner pages) |
| JS | `public/assets/js/app.js` (theme, reveal/split text, counters, page-transition curtain, parallax, FAQ filter, quotes, Tawk show/hide helper), `interactive.js` (unify diagram, compare slider, CAD layers, time-zone widget, picker, filters, vertical timeline, contact form AJAX) |
| Vendor libs | `public/assets/vendor/` (bootstrap, jquery – self-hosted) |
| Portfolio data (viz gallery, permit/BIM sets, scan pairs, case media, home highlights) | `app/Support/Portfolio.php` + generated `resources/data/work-manifest.json` |
| Portfolio partials | `partials/set-card`, `partials/work-gallery`, `partials/collab-card` (real image when available) |
| Lightbox / gallery filter / before-after pair switcher | `public/assets/js/lightbox.js` (+ `[data-lightbox]`, `[data-gallery]`, `[data-pairs]` markup hooks) |
| Portfolio styles | `public/assets/css/portfolio.css` (lightbox, work-grid, set-card, video, mosaic) |
| Asset build script (PDF → redacted WebP, crops, manifest) | `tools/build-work-assets.mjs` |
| Handover doc | `docs/FRONTEND.md` |

Images: `public/assets/img/` (hero from client PDF, `og/` 1200×630 share crops, `photos/` Pexels webp at 1600w + `-800.webp`). Pexels photos are **illustrative only** and labelled so; credits in `docs/FRONTEND.md`.

## 7. Gotchas learned (important!)
1. **Always use `pu('route.name')`** for links. Laravel's `route()`/`url()` strip the trailing slash. Canonical = `rtrim(url()->current(),'/').'/'`.
2. **Route names contain dots** → `config('seo.pages.services.bim')` silently fails (treated as nesting). Use `config('seo.pages')['services.bim']`. (Was a real bug; fixed.)
3. `public/.htaccess` has Laravel's "strip trailing slash" rule **removed on purpose**; `EnsureTrailingSlash` middleware does the 301 (works with `php artisan serve` too).
4. `config/app.php` `asset_url` is `env('ASSET_URL')` (null) – Laravel's default `'/'` makes `asset()` relative and breaks absolute og:image URLs.
5. Blade `@extends` children render **before** the layout: variables like `$canonical` aren't available inside page sections/partials pushed to `@stack('head')`; compute locally (see `service-schema` partial). `@push` inside sections works.
6. Page-specific SEO overrides: pass `['seo' => [...]]` as the 2nd arg of `@extends` (or from the controller).
7. Bootstrap `g-5` rows overflow on phones with our 18px page gutter → capped in `base.css` (`.row.g-5` on <768px) and `html{overflow-x:clip}`. Don't use `overflow-x:hidden` on html/body (breaks sticky header).
8. Reveal animations only hide content when the `js` class is present (set by inline head script) → content is visible without JS. Respect `prefers-reduced-motion` (CSS blocks present).
9. Page-transition curtain uses `sessionStorage('ay-nav')` + `html.is-leaving/is-entering`.
10. Git Bash path conversion: when passing `/` args to Windows programs set `MSYS_NO_PATHCONV=1`. Big multi-line heredocs with quotes sometimes break the Bash tool – prefer the Write tool for files.
12. Bash tool quirk: heredocs/`node -e` strings containing apostrophes or `$` can break the whole command (nothing runs). Write files with the Write tool (or a script file) instead; for appending CSS create a new file rather than `cat >>`.
15. **Live chat = Tawk.to** (replaced the old custom "Ask Architive" widget on 2026-10-06). `resources/views/partials/tawk.blade.php` loads `embed.tawk.to/<TAWK_PROPERTY_ID>/<TAWK_WIDGET_ID>` (public IDs in `config/services.php`, overridable in `.env`; `TAWK_ENABLED=false` switches it off) only after page load / first interaction. `app.js` exposes `window.architiveTawk(show)` which hides the bubble while the lightbox or mobile menu is open. The back-to-top button lives bottom-LEFT so it never collides with Tawk's bubble/pop-ups. Tawk answers `403` to HeadlessChrome user agents, so automated tests must set a normal Chrome user agent. Chat appearance, greeting and 'quick reply' buttons are configured in the Tawk dashboard, not in this repo. Privacy policy has a Tawk section.
14. Env vars for forms: `ADMIN_EMAIL` (notification recipient, read via `config('site.admin_email')`), `RECAPTCHA_SITE_KEY`, `RECAPTCHA_SECRET_KEY` (via `config('services.recaptcha.*')`), plus the normal `MAIL_*` SMTP settings. reCAPTCHA keys must allow the live domain (and localhost/127.0.0.1 for local testing) in the Google admin console. Rule fails CLOSED in production if the secret is missing; in local/testing it is skipped when unset. Real CAPTCHA can't be ticked by automated tests, so the original Puppeteer 'valid submit shows success' check no longer applies; use `php artisan test` (Http/Mail are faked).
13. Anchors used by the lightbox must not be intercepted by the page-transition curtain: `app.js` skips `a[data-lightbox]` and media/file extensions.
11. Environment: **Node 16.15** only → use older packages (sharp 0.32.x, puppeteer-core 19). Python is not installed. Chrome at `C:\Program Files (x86)\Google\Chrome\Application\chrome.exe` (used headless for screenshots).

## 8. How QA was done (re-create in scratch dir, not in repo)
- `puppeteer-core` + Chrome: full-page screenshots (scroll through page first so reveal animations fire), mobile 390px, dark mode, console-error capture.
- Interaction script covered: theme toggle, FAQ search/filters/accordion, diagram, quotes, page transition, mobile menu, BIM tabs + compare, CAD layers, engagement tabs, time-zone widget, picker, collaboration filter, contact form (validation + AJAX success), 404.
- SEO audit script: one H1/page, title 20–70 chars, description 70–175, canonical = URL, JSON-LD parses, no duplicate IDs/titles/descriptions, images have alt + dimensions, crawl all internal links (all 200).
Run server: `php artisan serve --host=127.0.0.1 --port=8000`.

## 9. Open items – verify before launch (client's own checklist)
- Stats: the old unverified 1,500+ projects / 1,000+ reviews were **removed**; the site now shows the client's figures (Fiverr 1,200+, Upwork 130+, direct 4–5). **Ask the client for review counts** and a "last updated" date to maintain. "12+ Engineers" still appears in the footer address (from the dummy) – confirm.
- Hourly rate: site says "from $16 per hour" per client instruction (their Upwork/agency rate is $26) – confirm they want that public.
- **David Sterling, AIA** testimonial is from the dummy – needs permission/confirmation. Other 2 quote slides are Architive's own principles (not testimonials).
- Permission to name Bonderud Design, VESTI Events/FIFA (keep wording "through VESTI Events", not contracted by FIFA), Manuel Development; confirm real project images/outcomes.
- Confirm Pakistan studio (Multan, Punjab) and Delaware (Newark) company wording.
- Privacy Policy and Terms are **drafts** → legal review.
- Team page is **parked** (noindex, unlinked) until real portraits/bios exist (copy deck says avoid stock people); about uses a founder monogram "MA".
- Service-card galleries (home + services page) use real work picked by us (`Content::coreServices()`); client may want to choose different pieces.
- Tawk chat avatar / "We are here" greeting is configured in the Tawk dashboard, not in code.
- Add real social URLs to `config/site.php` (`social`) to emit `sameAs`.
- No phone number or analytics exist yet (privacy policy says no analytics – update it if added).
- Confirm the client allows publishing the redacted permit/BIM sample sheets and the case-study visuals/video (clients: Bonderud Design, VESTI Events/FIFA 2026, Manuel Development and the homeowners/contractors behind the permit and scan sets). Repo should stay private until confirmed.
- Ask for the official logo (current header/footer mark is a placeholder) and a real team page content.
- Portal/payment/security claims must NOT be published until implemented and tested (per copy deck).

## 12. Portfolio system (client's second data batch, 2026-10-05)
Source (outside repo): `client data\Architive_CAD_and_Permit_Samples\CAD_and_Permit_Samples\` (4 permit-set PDFs) and `client data\architive website update visuals\architive website update visuals\` (`Service  1 Architectural visualisation` renders, `Revit and Bim` PDFs + 2 scan-to-BIM JPGs, `Case studies` boards/PDF/video). The `.zip`/`.rar` files next to them are duplicates (one zip is an empty stub).
How it is used:
- **Visualization page** `#work`: 17 renders in a filterable masonry gallery + lightbox; compare slider uses the studio's own brick-building render.
- **CAD page** `#samples`: 4 permit sets (NY, CA addition, CA remodel, VA sunroom) as set cards opening a sheet viewer.
- **BIM page**: `#convert` scan-to-BIM before/after slider with 2 real views (+ a static facade pair) and `#samples` with 3 existing-conditions sets (VT, PA, AZ). The CAD→BIM slider is still an illustrative SVG (labelled).
- **Collaboration pages/cards**: real boards for Bonderud (kitchen plan→render) and FIFA/VESTI (three-level circulation plan); Manuel Development gets the 3D house, the 23-second Revit video (`preload="none"`, loads on play) and 6 drawing sheets.
- **Home** `Selected work` mosaic; **services index** rows and OG share images use real work.
**Privacy rule (important):** sheets contain owner names, real addresses, a contractor's phone number and the Manuel Development logo. The build script crops/whites-out title blocks and blanks any line matching a name/address list; cover sheets and site plans are never published; captions use state names only. A text re-scan of all published sheets found no identifying details. Any NEW sheet must be re-checked visually (and the PRIVATE regex in the script extended) before it is committed.
**Regenerating assets:** `ASSET_DEPS=<folder with node_modules/{mupdf,sharp}> node --experimental-wasm-eh tools/build-work-assets.mjs` (run from repo root). Output: `public/assets/img/work/**` (+`-800` thumbs) and `resources/data/work-manifest.json`; the video lives in `public/assets/video/`.
**Logo (updated 2026-10-05):** the client's real logo (user-supplied PNG, 580x100, transparent, dark-gray lettering + yellow) is in `public/assets/img/logo.png`; `logo-light.png` is a recolored light-lettered copy (gray -> near-white, yellow kept) used on the dark footer and in dark mode. `resources/views/components/logo.blade.php` renders both (CSS toggles by `[data-theme]`; `$light` forces the light one). Favicon/app icons (`favicon-32.png`, `favicon.ico`, `icon-192/512.png`, `apple-touch-icon.png`) are generated from the logo's mark. It is a raster file: if the client sends an SVG/vector version, swap it in for sharper results on retina screens. The logo image is shifted down 9% (`.brand__img` transform) so the wordmark lines up with the nav text.
**Header:** always visible (sticky) with a slightly smaller "compact" state on scroll; the old hide-on-scroll behaviour was removed. `body`/`html` use `overflow-x: clip` (NOT hidden) because `hidden` creates a scroll container and silently breaks `position: sticky`.
**Still stock:** only hero backgrounds on some pages (BIM, CAD, process, team, faqs, contact, about, collaborations, outsourcing) – credits in `docs/FRONTEND.md`.

## 10. Likely next tasks
1. Contact form: admin notification + reCAPTCHA are DONE. Still optional: store enquiries in a database table, auto-reply to the visitor, admin dashboard.
2. Deployment: web server rewrite to `public/`, set `APP_URL` (drives canonical/sitemap/OG), `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, ensure `.htaccess` gzip/caching works, submit `sitemap.xml` to Search Console.
3. Analytics/consent banner if wanted (then update privacy policy).
4. Replace placeholders per §9; add real portfolio imagery when the client provides it.

## 11. Git
- Remote `origin` = https://github.com/hasanraza656/architive.git, branch `main`.
- `.env` is git-ignored (only `.env.example` is tracked). `vendor/`, logs, node_modules ignored → after cloning run `composer install`, copy `.env.example` → `.env`, `php artisan key:generate`.
- Commit messages end with the attribution line required by the session (Co-Authored-By …) when created by Claude.

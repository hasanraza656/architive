# CLAUDE.md – Architive project memory

Read this first in every new session. It holds everything learned so far about this project.
(No secrets belong here. Never commit `.env`, API keys or tokens.)

## 1. What this is
Marketing website for **Architive** – an architectural production studio (architectural visualization, BIM & Revit, CAD drafting + "production support" engagement model). Audience: architecture firms, interior studios, developers/contractors; homeowners secondary.
Stack: **Laravel 10 · PHP 8.1 · Blade · Bootstrap 5.3 · jQuery 3.7**. No build step (no Vite/npm needed at runtime).
Repo: https://github.com/hasanraza656/architive (branch `main`). Local path: `F:\bhai log\Architive designs\code`.

**Status:** Frontend phase is **complete and QA'd** (18 pages, mobile, dark mode, interactions, SEO audit all passing).
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
| Shared blocks (hero, CTA band, FAQ list+schema, process steps, schema, chat, header, footer, breadcrumbs, diagrams) | `resources/views/partials/*`, `resources/views/components/*` (`x-icon`, `x-logo`, `x-compare`) |
| Layout / `<head>` / SEO tags | `resources/views/layouts/app.blade.php` |
| SEO meta + breadcrumbs resolver (view composer) | `app/Providers/AppServiceProvider.php` |
| URL helpers `pu()`, `abs_pu()`, `asset_v()`, `is_page()` | `app/helpers.php` (autoloaded via composer.json) |
| Trailing-slash 301 + security headers | `app/Http/Middleware/EnsureTrailingSlash.php`, `SecurityHeaders.php` |
| Contact form (validate + honeypot + throttle; **only logs**) | `app/Http/Controllers/ContactController.php` |
| Sitemap/robots | `app/Http/Controllers/SeoController.php`, `resources/views/seo/sitemap.blade.php` |
| CSS | `public/assets/css/base.css` (tokens, header, footer, chat, motion), `pages.css` (home + shared sections), `widgets.css` (interactive widgets + inner pages) |
| JS | `public/assets/js/app.js` (theme, reveal/split text, counters, page-transition curtain, parallax, chat, FAQ filter, quotes), `interactive.js` (unify diagram, compare slider, CAD layers, time-zone widget, picker, filters, vertical timeline, contact form AJAX) |
| Vendor libs | `public/assets/vendor/` (bootstrap, jquery – self-hosted) |
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
11. Environment: **Node 16.15** only → use older packages (sharp 0.32.x, puppeteer-core 19). Python is not installed. Chrome at `C:\Program Files (x86)\Google\Chrome\Application\chrome.exe` (used headless for screenshots).

## 8. How QA was done (re-create in scratch dir, not in repo)
- `puppeteer-core` + Chrome: full-page screenshots (scroll through page first so reveal animations fire), mobile 390px, dark mode, console-error capture.
- Interaction script covered: theme toggle, FAQ search/filters/accordion, chat, diagram, quotes, page transition, mobile menu, BIM tabs + compare, CAD layers, engagement tabs, time-zone widget, picker, collaboration filter, contact form (validation + AJAX success), 404.
- SEO audit script: one H1/page, title 20–70 chars, description 70–175, canonical = URL, JSON-LD parses, no duplicate IDs/titles/descriptions, images have alt + dimensions, crawl all internal links (all 200).
Run server: `php artisan serve --host=127.0.0.1 --port=8000`.

## 9. Open items – verify before launch (client's own checklist)
- Stats flagged *[verify before publishing]*: **1,500+ projects, 1,000+ reviews**; "12+ specialists" comes from the dummy.
- **David Sterling, AIA** testimonial is from the dummy – needs permission/confirmation. Other 2 quote slides are Architive's own principles (not testimonials).
- Permission to name Bonderud Design, VESTI Events/FIFA (keep wording "through VESTI Events", not contracted by FIFA), Manuel Development; confirm real project images/outcomes.
- Confirm Pakistan studio (Multan, Punjab) and Delaware (Newark) company wording.
- Privacy Policy and Terms are **drafts** → legal review.
- Team page has no real portraits/bios (copy deck says avoid stock people) → add real ones; team/about use a founder monogram "MA".
- Add real social URLs to `config/site.php` (`social`) to emit `sameAs`.
- No phone number or analytics exist yet (privacy policy says no analytics – update it if added).
- Portal/payment/security claims must NOT be published until implemented and tested (per copy deck).

## 10. Likely next tasks
1. Contact form backend: persist (migration/model), notification + auto-reply mail (`ContactController@store` has the TODO), spam protection beyond honeypot.
2. Deployment: web server rewrite to `public/`, set `APP_URL` (drives canonical/sitemap/OG), `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, ensure `.htaccess` gzip/caching works, submit `sitemap.xml` to Search Console.
3. Analytics/consent banner if wanted (then update privacy policy).
4. Replace placeholders per §9; add real portfolio imagery when the client provides it.

## 11. Git
- Remote `origin` = https://github.com/hasanraza656/architive.git, branch `main`.
- `.env` is git-ignored (only `.env.example` is tracked). `vendor/`, logs, node_modules ignored → after cloning run `composer install`, copy `.env.example` → `.env`, `php artisan key:generate`.
- Commit messages end with the attribution line required by the session (Co-Authored-By …) when created by Claude.

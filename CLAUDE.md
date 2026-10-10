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

## 4c. Round 4 client changes (2026-10-08)
- Nav = Home, Who We Are, Services (dropdown with the 3 services only), **Collaborations** (was Projects), Process, Contact. Production support is no longer a page: `/architectural-outsourcing/` 301-redirects to `/services/`, removed from nav, footer, sitemap(s), about, services page. Content/view files kept but unused.
- Hero: kicker "Architectural Production Studio", lead "Coordinated team for Architectural Visualization, BIM and Revit, and CAD Drafting.", note "No overhead, no hiring, no delays." The old "free consultation or small paid pilot" line is gone everywhere; wording is now "free start" (no "pilot").
- Home strip (`partials/support-strip`) is simplified: Free start / Defined project / Hourly from $16 + "Contact us" (opens enquiry form). Ongoing/priority options removed from it.
- **World map** (`partials/world-map`, config `site.map`): land + USA/Canada/Australia shapes are inlined SVG (Natural Earth 110m, projection NaturalEarth1 1000x500, generated with d3-geo + world-atlas). Pins are % positions. To highlight more countries regenerate the shapes (add ISO id to the generator, add `$paths`/`$pins` entries and a `regions` row in config).

## 4d. Round 5 client changes (2026-10-08)
- **Home order:** hero (flat bottom edge, no rounded corners) → software ticker → "Flexible ways to start" strip (Free start + Hourly from $16 + Contact us; spotlight/shimmer effects) → **track band** (Fiverr 1,200+, Upwork 130+; "direct clients" removed) → 3 **big stacked service cards** (`partials/service-big`: image gallery, title, "Explore more" + "Start a project" only) → collaborations → short "Who we are" → world map → reviews → logo marquee → FAQ.
- Service order everywhere: **Architectural Visualization → BIM and Revit → CAD Drafting**.
- `/services/` = compact heading (`partials/svc-head`) + the same 3 big cards + map. No hero screen, no picker.
- Service pages (`visualization`, `bim`, `cad`) are compact: `svc-head` (H1, short text, deliverable chips, "Start a project" form button) → auto-running **sample reel** (`partials/sample-reel`, CSS marquee, pauses on hover, lightbox on click) → samples only (viz gallery / scan-to-BIM slider + Revit sets / permit sets) → CTA band. They use the normal sticky header (no `overlay`). Removed: buyer-problem, deliverables, process, FAQ, audience, scope, layers widget sections.
- About page trimmed to heading + founder story + CTA.
- **Page loading logo:** the curtain no longer blocks navigation; `app.js` adds `is-leaving` (logo curtain) only if a page takes more than 1.2 s to open.

## 4e. Round 6 client changes (2026-10-08)
- Home/services page services = `partials/service-show`: title + "Explore more" + "Start a project", then a large auto-running strip (`partials/sample-reel` with `big`) of that service's clearest samples from `Portfolio::reel($key)` (round-robin across categories/sets). Tiles keep their own proportions (no cropping). `service-big` was removed.
- Service pages: `svc-head` takes `image` (Portfolio key) and shows it on the left of the description; viz gallery tabs are now Interiors / Exteriors / 3D floor plans / Booth design / Self-storage containers (`Portfolio::vizCategories()`).
- Loader: `.curtain` is now a small loading screen (logo + window cycling `public/assets/img/loader/l1-4.webp` + progress bar). `app.js` shows it only if the first load takes >450 ms (`is-loading`, min ~1 s) or a clicked page is slow >500 ms (`is-leaving`). Disabled for reduced motion.
- Copy: services heading "One Brief. One Team. Every Deliverable Connected."; map heading "Supporting Teams Globally."; logo strip caption "Brands represented in projects supported through our collaborators"; "Figures supplied..." line removed; form option "Ongoing production support"; Multan removed (address = Pakistan).
- Pending from client: founder photo, AI FAQ question + answer, USA/Pakistan phone numbers (Waqas).

## 4f. Home services = 3-column carousel cards (round 7, testing)
- `partials/service-cards` (included from `home.blade.php`): 3 cards, each with an autoplay carousel (1 image per slide, arrows, dots, progress bar, swipe, keyboard, lightbox; JS `[data-carousel]` in `interactive.js`, CSS "Home services" block at the end of `home.css`). Photos use contain + blurred backdrop so nothing is cropped. The previous sliding-strip version (`partials/service-show`) is commented out in `home.blade.php` and still used by `/services/`.

## 4g. Home services = expanding panels (FINAL, client choice, round 8)
- `partials/service-panels` (+ `svc-slide`, `public/assets/css/svc-panels.css`, `public/assets/js/svc-panels.js`, pushed from the partial): three panels, hover/tap/arrow keys open one; each fades through that service's images from `Portfolio::reel()` (blurred backdrop, never cropped); on phones it is an accordion. The Revit house render (`cases/manuel-house-3d`) is excluded here on purpose (client request). Other 10 design options were deleted. `service-show` strips remain only on `/services/`; old `.svcard` CSS in home.css is unused.

## 4h. Admin + customer portal (built 2026-10-09/10)
**URLs:** admin `/admin` (login `/admin/login`), customers `/account` (login `/account/login`, e-mailed 6-digit code, no password). Linked from the site header (user icon), mobile menu and footer. Everything under `/admin`, `/account`, `/portal`, `/webhooks` is `noindex` + robots-disallowed.
**Create / reset an admin (never keep credentials in files):** `php artisan admin:create someone@example.com --password="..."`. Admin can change the password in Admin > Settings.
**Structure:**
- `routes/portal.php` (loaded in RouteServiceProvider), `config/portal.php` (limits, OTP, chat timings, uploads), `config/countries.php` (dial codes).
- Models `User` (role admin|customer; customers have no password), `Order` (= invoice, route key = number like ARC-1001), `OrderItem`, `OrderEvent` (timeline), `OrderMessage`, `OrderDelivery`, `OrderFile`, `OrderReadState`, `LoginCode`. Money is stored in cents (`money()` helper, `App\Support\Money`).
- `App\Enums\OrderStatus`: draft -> pending (invoice sent) -> active (paid) -> delivered -> completed, cancelled any time while open. **All status changes go through `Services/Orders/OrderWorkflow`** (rules + timeline + e-mails).
- Services: `OrderService` (items/totals/numbering), `FileStorage`, `StripeCheckout` (Checkout Session, return-page confirmation + webhook), `OtpService`, `ChatService` + `ChatNotifier`, `Notifications/PortalMailer` (mail failures never break a page).
- Controllers: `Admin\*`, `Customer\*`, `Portal\ChatController|FileController` (shared, policy-checked), `Webhooks\StripeWebhookController`. Policy `OrderPolicy`, middleware `role:admin|customer`.
- Views `resources/views/portal/{layouts,auth,admin,customer,shared,components}`; shared order page = `portal/shared/workspace` (+ role-specific `_head-actions`, `_banners`). E-mails `resources/views/emails/portal/*` + `App\Mail\Portal\*`. Assets `public/assets/css/portal.css`, `js/portal.js` (shell, tabs, countdown, phone picker, OTP boxes), `portal-order-form.js` (invoice builder), `portal-chat.js` (polling chat; swap `poll()` for websockets later, JSON shape = `ChatService::feed()`).
**Payments:** `.env` STRIPE_KEY, STRIPE_SECRET, STRIPE_WEBHOOK_SECRET. Customer pays via Stripe-hosted Checkout (single line = invoice total); the return page asks Stripe directly, the webhook (`/webhooks/stripe`, event `checkout.session.completed`) is the safety net. Both are idempotent. Refunds are done manually in Stripe.
**Chat e-mails (quiet by design):** none while the person has the order page open (<2 min); otherwise at most one e-mail per person per order every 30 min; unread leftovers are bundled by `php artisan portal:chat-digest` (scheduled every 5 min; production needs the cron `* * * * * php artisan schedule:run`). Admin-side mails go to `ADMIN_EMAIL`.
**Gotchas:** (1) MySQL/MariaDB silently adds ON UPDATE CURRENT_TIMESTAMP to a NOT NULL `timestamp`: use `dateTime` (see login_codes.expires_at); DB session is pinned to UTC in config/database.php. (2) Tests run on in-memory SQLite (phpunit.xml) so they never touch the real DB. (3) Blade: avoid `@if (...): text @else.@endif` inline, use `{{ cond ? a : b }}`. (4) Do not use `data-title` as a JS hook (lightbox uses it). (5) Uploads are limited by php.ini `upload_max_filesize`/`post_max_size` as well as `portal.uploads.max_kb`. (6) `php -S` + `MAIL_MAILER=log` is handy for local E2E runs (artisan serve ignores env overrides).

## 4i. Order requests (leads -> orders), PDF invoices (2026-10-10)
- **A website form submission is a ticket.** `Services/Orders/RequestService` finds/creates the customer account instantly (admin e-mails get no ticket), creates an `Order` with status `request` (`source` website|portal, plus service/audience/company/brief/requested_deadline), first chat message, attached files, 'requested' event. Request is stored BEFORE e-mails so a mail failure never loses a lead. JSON reply `{ok,message,request,portal_url}`; the success panel shows the number + "Follow my request" (`/account/login?email=`).
- Customers also create requests in the portal (`customer.requests.create/store`); newcomers who sign in with a new e-mail get an account after the code is confirmed, then a short `welcome` step (`profile.complete` middleware, `users.profile_completed_at`).
- **Offer**: admin edits the request and "sends" it -> `OrderWorkflow::send()` posts a chat message `kind='offer'` (card drawn from `ChatService::feed()['offer']`, live status, Pay button) and sets status `pending`. Paying uses the normal Stripe flow -> `active`. Lead orders hide deliveries/invoice tabs until an offer exists; request invoices 404 before that. Offer messages never trigger chat e-mails.
- **PDF invoice**: dompdf (`composer require dompdf/dompdf`, pure PHP) via `Services/Orders/InvoicePdf` + `resources/views/pdf/invoice.blade.php`; attached to both payment-received e-mails; download route `portal.orders.invoice` for admin + customer. WhatsApp/phone from `config('site.phone_display')` on every invoice/e-mail footer.
- Website forms (contact page + pop-up): file attachments (max 5, 10 MB each, blocked executable extensions), "I need help with" card grid (`.opt-grid`), footer has a "Customer Portal" link. reCAPTCHA `g-recaptcha-response` is only optional in local/testing when no secret key is set.
- **Admin can delete anything** (2026-10-10): any order/request (`OrderController::destroy`, `Order::deleting` also wipes `storage/app/orders/{id}`) and any customer with all their orders (`CustomerController::destroy`). Buttons open confirm dialogs on the order / customer pages. Paid orders are NOT refunded by deleting.
- **Admin Inbox** `/admin/inbox` (`Admin\InboxController`, `Services/Chat/Inbox`, `portal/admin/inbox.blade.php`, `portal-inbox.css/js`): every text message across orders, filters (customer, order/title, dates, words, customers/team/all, read/unread), quick view dialog with quick reply, mark read/unread/all. Unread = customer message newer than the highest read marker of ANY admin in that order (same as dashboard); markers are per conversation, so reading a message reads the earlier ones. Row link = `/admin/orders/{n}?m=<messageId>#chat`: `portal-chat.js` opens the chat tab and scrolls/highlights that message (`.msg--focus`). Sidebar shows the unread count.
- **Header contact (round 9)**: number pill beside the logo (full pill >=1600px, plain text 1400-1599, icon only 1280-1399, hidden below 1280; always in the mobile menu) and a green WhatsApp button right of "Free consultation" (`header-phone`/`header-wa` in `partials/header`, CSS end of `base.css`). The floating WhatsApp bubble was removed. Number = `config('site.phone')`. Hero note reads "No overheads, no hiring, no delays." Every map location pulses (glow), small Gulf/Europe dots use a tighter ring (`map-pulse-sm`).
- Sessions are database-based (`SESSION_DRIVER=database`, migration `create_sessions_table`).
- Server deploy: `composer install --no-dev`, `php artisan migrate --force`, cron `schedule:run`, check php.ini upload limits.

## 4j. Blog module + public uploads (2026-10-11)
- **All user uploads live in `public/uploads`** (disk `uploads` in `config/filesystems.php`; no `storage:link` ever). `uploads/orders/*` = chat/delivery files, blocked from direct web access by an auto-created `.htaccess` (`FileStorage::guardPrivateFolder`) and served through the authorised portal route; `uploads/blog/*`, `uploads/avatars/*` are public. `public/uploads/*` is git-ignored except `.htaccess`/`.gitkeep`. Old files: `php artisan uploads:move-from-storage`.
- **Blog** (WordPress-style): admin `/admin/blog/posts` (+ categories at `/admin/blog/categories`), public `/blog/`, `/blog/{slug}/`, `/blog/category/{slug}/`, `/blog/tag/{slug}/`, `/blog/feed.xml`. Tables `blog_posts`, `blog_categories`, `blog_tags`, `blog_post_tag`; users gained `avatar`, `job_title`, `bio` (admin edits them in Settings, shown in the article author box + JSON-LD).
- Code: models `BlogPost|BlogCategory|BlogTag`; `Services/Blog/PostService` (save, unique slug, tags, publish/schedule), `ContentProcessor` (whitelist sanitizer on save; heading anchors, TOC, lazy images, safe external links on render), `ImageStore` (GD: WebP, 1600px + `-800` thumb, EXIF rotate; avatars 400px square); controllers `Admin\BlogPostController|BlogCategoryController|BlogMediaController`, public `BlogController`; views `portal/admin/blog/*`, `pages/blog/*`, `partials/blog-card`, `seo/blog-feed`; assets `portal-blog.css/js` (editor: Quill 1.3.7 self-hosted in `assets/vendor/quill`, live Google preview + plain-language SEO checklist), `blog.css/js` (reading progress, TOC highlight, share/copy link).
- Status rules: `published` + `published_at` in the past = live; future date = scheduled (goes live by itself, no cron); drafts/scheduled are 404 for visitors and previewable by signed-in admins (`noindex`). Quill 1.x cannot edit tables/figures: do not put them in articles. Slugs `category|tag|feed|page|author|search` are reserved.
- SEO: per-post title/description/canonical/robots/og (`article`), BlogPosting + BreadcrumbList JSON-LD, sitemap lists live posts + categories (not tags, not `noindex` posts), thin tag pages (<3 posts) and `?q=` search are `noindex`. Visible breadcrumbs are passed from the controller (the layout composer runs too late for in-section partials).
- Starter content: `php artisan db:seed --class=BlogSeeder` (8 articles in `database/seeders/data/blog/*.php`, images + Pexels credits in `public/assets/img/blog/`). Live-server SQL for phpMyAdmin: `database/sql/blog-module.sql` (tables + user columns + articles + migrations row).
- Blog link is in the footer only (header nav is full at 1440px). Gotcha: never name a Blade loop variable `$c` on pages using the layout composer (it reads `$c` as the collaboration array).
- Gotcha: `php artisan tinker file.php` stays interactive and hangs; use `tinker --execute="require 'file.php';"`.

## 4k. Collaboration changes (2026-10-11)
- Bonderud Design asked not to be named: its page/assets/OG image are gone and `/collaborations/bonderud-design-visualization/` 301-redirects to `/collaborations/`. Do NOT re-add the name anywhere public.
- Case 001 is now the anonymous UK client: "500+ 3D Floor Plans for Smart Heating and Cooling Projects Across the UK" (slug `uk-3d-floor-plans-smart-heating-cooling`, 4 floor plans). Case 004 "Ongoing Visualization Support for a Texas Firm" (slug `texas-ongoing-visualization-support`, 5 interior renders; the games-room render with real jersey names was intentionally left out). Card text and meta description for 004 were derived from the client's copy (she supplied none) - confirm with the client.
- New-style case data (`overview`, `role_items`/`role_text`, `result`, `card`, `location/service/status`) renders a different layout in `pages/collaboration` + `pages/collaborations`; old-style (client/situation/role/deliverables) still works for FIFA and Manuel. Images are in `public/assets/img/work/cases/` with entries added by hand to `resources/data/work-manifest.json` (not produced by `tools/build-work-assets.mjs`).

## 5. URL structure (one permanent lowercase **trailing-slash** URL per page)
`/` · `/about-us/` · `/team/` · `/services/` · `/architectural-visualization-rendering/` · `/bim-revit-scan-to-bim/` · `/cad-drafting-services/` · `/architectural-outsourcing/` · `/collaborations/` · `/collaborations/{slug}/` (uk-3d-floor-plans-smart-heating-cooling, fifa-2026-circulation-plan-drafting, manuel-development-revit-support, texas-ongoing-visualization-support) · `/how-it-works/` · `/faqs/` · `/contact/` · `/privacy-policy/` · `/terms-of-service/` · `/sitemap/` · `/sitemap.xml` · `/robots.txt`.
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
- Permission to name VESTI Events/FIFA (keep wording "through VESTI Events", not contracted by FIFA), Manuel Development; confirm real project images/outcomes.
- Confirm Pakistan studio (Multan, Punjab) and Delaware (Newark) company wording.
- Privacy Policy and Terms are **drafts** → legal review.
- Team page is **parked** (noindex, unlinked) until real portraits/bios exist (copy deck says avoid stock people); about uses a founder monogram "MA".
- Service-card galleries (home + services page) use real work picked by us (`Content::coreServices()`); client may want to choose different pieces.
- Tawk chat avatar / "We are here" greeting is configured in the Tawk dashboard, not in code.
- Add real social URLs to `config/site.php` (`social`) to emit `sameAs`.
- No phone number or analytics exist yet (privacy policy says no analytics – update it if added).
- Confirm the client allows publishing the redacted permit/BIM sample sheets and the case-study visuals/video (clients: VESTI Events/FIFA 2026, Manuel Development and the homeowners/contractors behind the permit and scan sets). Repo should stay private until confirmed.
- Ask for the official logo (current header/footer mark is a placeholder) and a real team page content.
- Portal/payment/security claims must NOT be published until implemented and tested (per copy deck).

## 12. Portfolio system (client's second data batch, 2026-10-05)
Source (outside repo): `client data\Architive_CAD_and_Permit_Samples\CAD_and_Permit_Samples\` (4 permit-set PDFs) and `client data\architive website update visuals\architive website update visuals\` (`Service  1 Architectural visualisation` renders, `Revit and Bim` PDFs + 2 scan-to-BIM JPGs, `Case studies` boards/PDF/video). The `.zip`/`.rar` files next to them are duplicates (one zip is an empty stub).
How it is used:
- **Visualization page** `#work`: 17 renders in a filterable masonry gallery + lightbox; compare slider uses the studio's own brick-building render.
- **CAD page** `#samples`: 4 permit sets (NY, CA addition, CA remodel, VA sunroom) as set cards opening a sheet viewer.
- **BIM page**: `#convert` scan-to-BIM before/after slider with 2 real views (+ a static facade pair) and `#samples` with 3 existing-conditions sets (VT, PA, AZ). The CAD→BIM slider is still an illustrative SVG (labelled).
- **Collaboration pages/cards**: real boards for FIFA/VESTI (three-level circulation plan); Manuel Development gets the 3D house, the 23-second Revit video (`preload="none"`, loads on play) and 6 drawing sheets.
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

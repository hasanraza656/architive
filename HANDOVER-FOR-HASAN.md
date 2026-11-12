# Handover for Hasan (from Zain, DevMarDe), 28 Oct 2026

Hi Hasan, these are the open server tasks for architive.net. The Google Ads test starts on **31 Oct**, so task 1 is urgent. Please do them in order, then tell Zain. When everything is done, delete this file and the "0. Open handover" section at the top of CLAUDE.md, and commit.

## 1. Deploy the latest main (URGENT, before 31 Oct)

Google Analytics is already working, thank you. But the latest code is not on the server yet: https://architive.net/lp/3d-rendering/ and https://architive.net/thank-you/ still show "page not found".

1. On the server, pull `main` (latest commit `a792363`, PR #8 "count live chat, ignore WhatsApp share links", or newer; it includes PR #7). The contact tracking lives in `partials/analytics.blade.php` and `partials/tawk.blade.php`; nothing extra to configure.
2. Run:
   ```
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```
   If production normally uses `config:cache` / `route:cache`, run them again after the clear.
3. No database migrations are needed.
4. Check these open (status 200):
   - https://architive.net/lp/3d-rendering/
   - https://architive.net/lp/3d-floor-plans/
   - https://architive.net/lp/cad-drafting/
   - https://architive.net/thank-you/
   - https://architive.net/assets/js/attribution.js

## 2. Google Ads values (Hasan sets these, copy as is)

Add to the server `.env`, then run `php artisan config:clear` and `php artisan view:clear`:
```
GOOGLE_ADS_ID=AW-11156317089
GOOGLE_ADS_LEAD_LABEL=lCeWCKKdpbYdEKHH38cp
GOOGLE_ADS_CONTACT_LABEL=LCPSCPvPrLYdEKHH38cp
```
`GA4_ENABLED=true` is already set. Keep it.

## 3. Redirect the two old WordPress subdomains

`new.architive.net` and `architivedesign.architive.net` still show the old WordPress site (duplicate content for Google), and their SSL certificates expired in June and July 2026.

1. cPanel > SSL/TLS Status > tick both subdomains > Run AutoSSL.
2. cPanel > File Manager > each subdomain's document root (cPanel > Domains shows the folder) > rename the existing `.htaccess` to `.htaccess-wordpress-old` > create a new `.htaccess` with exactly:
   ```
   RewriteEngine On
   RewriteRule ^(.*)$ https://architive.net/$1 [R=301,L]
   ```
3. Keep the WordPress files for now as a backup.
4. Check: https://new.architive.net/about-us/ should land on https://architive.net/about-us/

## 4. Make this GitHub repo private

The repo is still **public**, and it holds client drawing sheets. On GitHub: Settings > General > Danger Zone > Change visibility > Private. Only you can do this, because you own the repo.

Thank you! Questions: hello@devmarde.com

## 13 Nov: LP proof band (branch `lp-proof-band`, commit 2fab831)
- Adds a proof band under the hero on both landing pages: Fiverr 1,200+ and Upwork 130+ completed projects (from `config/site.php` track_record), links to the Upwork profile, Fiverr page and Trustpilot page. No rating or review count shown (client has not confirmed figures).
- `analytics.blade.php`: links with `data-proof` fire a new GA4 event `proof_click` (param `platform`) plus the existing `outbound_click`. Not an Ads conversion.
- Please merge, deploy, then in GA4 mark nothing new as a key event.
- Speed (separate): host is HTTP/1.1 only, TTFB about 1.2s, no CDN, session driver is database. Please enable HTTP/2 and 3, put Cloudflare free in front, and set SESSION_DRIVER=file or cookie.
- Known: `DraftCaseStudiesTest` already fails on main (unrelated).

## 13 Nov (later): lead quality and LP anchors (same branch lp-proof-band)
- `app/Support/Attribution.php`: the lead email now has a "Google click ID" line (gclid, gbraid or wbraid). This is what we need to report qualified and won leads back to Google Ads (offline conversion import). Test: tests/Unit/AttributionClickIdTest.php.
- LP pages get a pricing explainer ("How a floor plan / render is priced", wording taken from the service pages, no numbers) and a "Prefer to talk first?" card with Call and WhatsApp buttons (both fire contact_click).
- Enhanced conversions for leads is switched on in Google Ads (Google tag method). The site already sends the hashed email via gtag user_data before generate_lead.
- Please check: the Tawk chat greeting bubble covers the first form fields on phones. Suggest turning off the auto-greeting on /lp/ pages (Tawk dashboard, Widget Appearance, Chat greeting) or delaying it.

## 13 Nov: merged to main (b04edfb)
lp-proof-band is merged into main and tests pass (166 ok, 1 old failure: DraftCaseStudiesTest, also fails without these changes). To deploy: pull main, then `php artisan config:clear` and `php artisan view:clear`. No new .env values. Note: code.zip sits in the repo root (came with 13348b7), delete it if it is not meant to be there.

# Handover for Hasan (from Zain, DevMarDe), 28 Oct 2026

Hi Hasan, these are the open server tasks for architive.net. The Google Ads test starts on **31 Oct**, so task 1 is urgent. Please do them in order, then tell Zain. When everything is done, delete this file and the "0. Open handover" section at the top of CLAUDE.md, and commit.

## 1. Deploy the latest main (URGENT, before 31 Oct)

Google Analytics is already working, thank you. But the latest code is not on the server yet: https://architive.net/lp/3d-rendering/ and https://architive.net/thank-you/ still show "page not found".

1. On the server, pull `main` (latest commit `467c250` "Ads ready ... (#7)" or newer).
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

## 2. Google Ads values (when Zain sends them)

Add to the server `.env`, then run `php artisan config:clear`:
```
GOOGLE_ADS_ID=AW-...
GOOGLE_ADS_LEAD_LABEL=...
GOOGLE_ADS_CONTACT_LABEL=...
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

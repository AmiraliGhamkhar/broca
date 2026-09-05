# Deploying Broca on shared hosting / cPanel

**Confirmed target (2026-09-05):** Mizbanfa shared cPanel · domain `brocamed.ir`
· SSH available · Git Version Control available · MySQL · **PHP must be set to
8.4** in MultiPHP Manager.

> **PHP 8.4 is a hard requirement, not a preference.** `shetabit/payment` v7
> (the ZarinPal driver) and the whole Symfony 8.1 component set require
> `>=8.4.1`. On 8.3 the app does not boot. Set it for **both** the web handler
> (MultiPHP Manager) and the CLI — cPanel cron/SSH often defaults to an older
> EA-PHP. Pin the absolute binary in cron entries:
> `/opt/cpanel/ea-php84/root/usr/bin/php`.

Deployment is automated by **`.cpanel.yml`** in the repository root: push to
the branch, then hit *Update from Remote* → *Deploy HEAD Commit* in cPanel's
Git Version Control. It installs Composer deps, builds assets **only if Node
exists** (falling back to the committed `public/build`), migrates, and rebuilds
caches behind `artisan down`/`up`.

It assumes:

- PHP CLI is available on the host
- you can create a MySQL database from cPanel
- HTTPS is enabled for the domain
- the app is served from Laravel's `public/` directory, either directly or via the host's Laravel/document-root support

If your host does **not** allow CLI commands, queues, or cron jobs, ask the host first; Broca depends on those.

## 1. cPanel prerequisites

Before uploading code, confirm these are available in cPanel:

- PHP 8.4+ with common Laravel extensions
- MySQL database + database user
- SSL certificate for the final domain
- Cron Jobs
- SSH / Terminal or at least a way to run `php artisan`
- enough disk for `storage/`, logs, and backups

Recommended PHP extensions include: `bcmath`, `ctype`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `zip`, `curl`, `intl`, `gd`.

## 2. Upload the project

You can deploy with Git Version Control in cPanel, SSH+git, or by uploading an archive.

Recommended layout:

- app code: `/home/CPANEL_USER/broca`
- web root: domain or subdomain document root pointed to `/home/CPANEL_USER/broca/public`

Important: the public web root must expose **Laravel `public/`**, not the repository root.

If cPanel cannot point the domain directly to `public/`, use one of these approaches:

1. create the domain/subdomain with document root set to `broca/public`, or
2. use the host's Laravel application feature, if provided.

Avoid copy-pasting the entire Laravel app into `public_html` unless the host gives no cleaner option.

## 3. Create `.env`

Copy `.env.example` to `.env` and fill the real values.

Minimum production-oriented example:

```env
APP_NAME=Broca
APP_ENV=production
APP_DEBUG=false
APP_URL=https://brocamed.ir
APP_FORCE_HTTPS=true

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=broca
DB_USERNAME=broca_user
DB_PASSWORD=strong-password

CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=mail.brocamed.ir
MAIL_PORT=587
MAIL_USERNAME=no-reply@brocamed.ir
MAIL_PASSWORD=mail-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@brocamed.ir
MAIL_FROM_NAME="Broca"

ZARINPAL_MERCHANT_ID=real-merchant-id
ZARINPAL_SANDBOX=false
ZARINPAL_CALLBACK_URL="https://brocamed.ir/payments/zarinpal/callback"

TELEGRAM_BOT_ENABLED=true
TELEGRAM_BOT_TOKEN=123456:telegram-token
TELEGRAM_WEBHOOK_SECRET=use-a-long-random-secret
TELEGRAM_ADMIN_IDS=11111111,22222222

BROCA_EXTERNAL_VIDEO_ORIGINS=https://cdn.example.com,https://videos.example.org
```

Notes:

- `APP_URL` must be the final HTTPS URL.
- `TELEGRAM_WEBHOOK_SECRET` should be a long random string.
- `TELEGRAM_ADMIN_IDS` should contain only the small trusted admin set.
- `BROCA_EXTERNAL_VIDEO_ORIGINS` is required when externally hosted videos are embedded.

## 4. Install dependencies and build

From SSH / Terminal inside the project directory:

```bash
cd /home/CPANEL_USER/broca
composer install --no-dev --optimize-autoloader
npm ci --ignore-scripts
npm run build
php artisan key:generate   # first deployment only; do not rotate APP_KEY on live updates
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If Node is not available on the host, build assets locally (`npm ci && npm run build`) and upload the generated `public/build` output with the release.

If this is **not** a fresh install, skip `db:seed` unless you intentionally want seed data.

## 5. File permissions

Make sure the web/PHP user can write to:

- `storage/`
- `bootstrap/cache/`

Typical fix:

```bash
chmod -R 775 storage bootstrap/cache
```

Exact ownership commands depend on the host and are often not available on shared hosting.

## 6. Cron jobs in cPanel

Create the scheduler cron job in **cPanel → Cron Jobs**:

```bash
* * * * * cd /home/CPANEL_USER/broca && php artisan schedule:run >> /dev/null 2>&1
```

This is required for scheduled maintenance and backups.

## 7. Queue worker on shared hosting

Broca uses queued work for some tasks. On shared hosting, one common fallback is a cron-driven worker.

Preferred if your host supports a persistent worker or process manager:

```bash
cd /home/CPANEL_USER/broca && php artisan queue:work --tries=3 --backoff=60 --timeout=60
```

If your host does **not** support persistent workers, use a cron-based fallback such as:

```bash
* * * * * cd /home/CPANEL_USER/broca && php artisan queue:work --stop-when-empty --tries=3 --backoff=60 --timeout=60 >> /dev/null 2>&1
```

Persistent workers are better, but this fallback is common on cPanel.

## 8. Telegram bot setup

### 8.1 Create the bot

In Telegram, talk to **BotFather**:

- create a bot
- copy the bot token
- optionally set bot name, description, and commands

Place the token in:

```env
TELEGRAM_BOT_TOKEN=...
```

### 8.2 Add allowed admins

Two supported methods exist.

#### Method A: environment allowlist

Put Telegram user IDs in `.env`:

```env
TELEGRAM_ADMIN_IDS=11111111,22222222
```

This is the quickest way for a small admin team.

#### Method B: database allowlist

After deployment, add admins via Artisan:

```bash
php artisan broca:telegram-admin 11111111 --first-name="Admin"
php artisan broca:telegram-admin 22222222 --first-name="Editor"
```

This persists the allowlist in `telegram_admins`.

### 8.3 Webhook URL

The webhook endpoint is:

```text
https://YOUR-DOMAIN/telegram/webhook
```

Example:

```text
https://brocamed.ir/telegram/webhook
```

This endpoint is already designed to be CSRF-exempt.

### 8.4 Register the webhook

Run:

```bash
php artisan broca:telegram-set-webhook
```

That command uses:

- `APP_URL`
- `TELEGRAM_BOT_TOKEN`
- `TELEGRAM_WEBHOOK_SECRET`

Make sure all three are correct first.

### 8.5 Verify the webhook

After registration:

1. send `/start` to the bot from an allowed admin account
2. confirm a Telegram row/session is created when starting a workflow
3. confirm inline buttons appear
4. test a safe path like listing plans before trying uploads

If webhook calls fail, check:

- the domain is public and reachable from the internet
- HTTPS is valid
- `APP_URL` matches the real host
- the web server points to Laravel `public/`
- `storage/logs/laravel.log`

## 9. Media handling notes

### Cover images

Course/blog cover uploads are stored via Laravel's public disk. After deploy, `php artisan storage:link` must exist and work.

### Video uploads

The Telegram bot supports:

- direct Telegram file upload
- URL/reference mode

On shared hosting, large direct video uploads may hit host limits or Telegram file-size constraints. When that happens, prefer the URL/reference mode and host the final video on an external CDN/provider.

### Note/booklet uploads

The bot supports:

- direct Telegram document upload
- URL mode for remote file import

Check PHP/web-server upload limits if admins will upload large PDFs directly.

## 10. Safer publication flow

For blog posts and medical-learning content, direct publishing from raw form submission is intentionally blocked.

Expected workflow:

1. create/update item as `draft` or `in_review`
2. use the admin panel or Telegram inline buttons to move it through review
3. publish only through the explicit transition/confirmation action

This reduces accidental publication.

## 11. Database backups from Telegram

The bot can trigger database backup generation and send the dump file back through Telegram.

Also keep normal server-side backup discipline:

- keep the scheduled backup cron working
- copy backup files off-host regularly
- test a restore periodically

Shared hosting is not a backup strategy by itself.

## 12. Post-deploy checklist

Run through this once after go-live:

- [ ] Site loads over HTTPS
- [ ] Admin login works
- [ ] `php artisan migrate --force` completed successfully
- [ ] `php artisan storage:link` exists
- [ ] Scheduler cron added in cPanel
- [ ] Queue worker or queue cron working
- [ ] Telegram webhook registered
- [ ] Telegram bot responds only to approved admins
- [ ] Inline Telegram menus open correctly
- [ ] Blog CRUD works in web admin
- [ ] One video/note upload flow tested
- [ ] Backup command works and returns a file
- [ ] Payment callback URL matches production domain

## 13. Public-page runtime and SEO verification plan

After the code is live, verify these pages in a real browser, not only with curl:

### Homepage `/`

- page title is not empty and matches the product positioning
- meta description exists in page source
- canonical points to the production home URL
- FAQ JSON-LD is present in page source
- primary CTA buttons lead to register/catalog correctly

### Catalog `/catalog`

- default catalog page is indexable: `robots` should be `index, follow`
- canonical should point to `/catalog`
- subject chips work and open the correct subject page
- filtered/search result pages should expose `noindex, follow`
- item cards show subject, level, author/reviewer and counts cleanly

### Subject page `/subjects/{slug}`

- title and meta description reflect the selected subject
- canonical points to that exact subject URL
- breadcrumb and item-list JSON-LD are present
- course cards open the right public course pages

### Plans `/plans`

- title/meta/canonical are correct in page source
- FAQ, breadcrumb and item-list JSON-LD are present
- CTA behavior matches real state:
  - guest sees register CTA
  - subscribed user sees subscription-status CTA
  - paid plan with disabled checkout shows non-clickable warning path
- Rial/Toman display is consistent with admin pricing

### Technical spot checks

Run these from SSH if available:

```bash
curl -I https://brocamed.ir/
curl -I https://brocamed.ir/catalog
curl -I https://brocamed.ir/plans
curl -I https://brocamed.ir/robots.txt
curl -I https://brocamed.ir/sitemap.xml
```

And inspect page source manually for:

- `<link rel="canonical">`
- `<meta name="description">`
- `<meta property="og:title">`
- `<meta property="og:description">`
- JSON-LD blocks

If any of those point to localhost, the wrong domain, or HTTP instead of HTTPS, fix `APP_URL`, clear caches, and re-test:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 14. Useful maintenance commands

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan schedule:run
php artisan queue:restart
php artisan broca:telegram-set-webhook
php artisan broca:telegram-admin 11111111 --first-name="Admin"
```

## 15. Common cPanel failure points

### Blank page or 500 error

Usually one of these:

- wrong document root
- bad permissions on `storage/` or `bootstrap/cache/`
- missing PHP extension
- stale cached config after env changes

Try:

```bash
php artisan optimize:clear
php artisan config:cache
```

Then inspect `storage/logs/laravel.log`.

### Telegram bot not responding

Usually one of these:

- webhook not registered
- invalid SSL certificate
- wrong `APP_URL`
- wrong `TELEGRAM_WEBHOOK_SECRET`
- sender not in the allowlist

### Uploaded files not visible

Usually:

- `php artisan storage:link` missing
- public disk/symlink blocked by host policy
- file permissions incorrect

### Queued jobs not processing

Usually:

- no worker
- cron fallback missing
- database queue tables not migrated

## 16. Recommended deployment order for updates

For future releases on cPanel:

```bash
git pull
composer install --no-dev --optimize-autoloader
npm ci --ignore-scripts && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

If you changed Telegram env values, re-run:

```bash
php artisan broca:telegram-set-webhook
```

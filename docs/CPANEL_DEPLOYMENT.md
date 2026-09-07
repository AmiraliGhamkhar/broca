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
exists** (falling back to the committed `public/build`), migrates, syncs the
canonical plan lineup (`broca:sync-plans`), and rebuilds caches behind
`artisan down`/`up`.

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

# Auth policy (see docs/RUNBOOK.md §11): min length for register + reset.
# Leave the breach check off unless api.pwnedpasswords.com is reachable.
BROCA_PASSWORD_MIN=8
BROCA_PASSWORD_LEAK_CHECK=false

# Signup verification. Transactional mail and SMS are delivered INLINE on
# purpose: with `database`, a missing or dead queue worker means the
# verification mail never leaves the server and every new signup is dead on
# arrival. Only set it to `database` once you have watched the worker drain.
BROCA_NOTIFICATIONS_QUEUE=sync
BROCA_PHONE_VERIFICATION=true
BROCA_PHONE_CODE_TTL=10
BROCA_PHONE_RESEND_COOLDOWN=60

# SMS panel. `log` writes the code to storage/logs/laravel.log and sends
# nothing — safe for the first deploy. Switching to a real panel is an env
# change (generic HTTP driver), not a code change: see
# docs/SMS_AND_VERIFICATION.md §3.
SMS_ENABLED=true
SMS_DRIVER=log
SMS_FROM=
SMS_HTTP_URL=
SMS_HTTP_METHOD=POST
SMS_HTTP_ENCODE=json
SMS_HTTP_HEADERS={}
SMS_HTTP_BODY={}
SMS_HTTP_SUCCESS_STATUS=200,201,202
SMS_HTTP_TIMEOUT=15

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
- `MAIL_FROM_ADDRESS` must be a real mailbox on the sending domain. It is no longer the
  only recovery path — registration also sends a one-time code to the mobile number, and
  either one activates the account — but it is still the path most students use (see
  `docs/RUNBOOK.md` §11 and `docs/SMS_AND_VERIFICATION.md`).

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

Broca uses queued work for some tasks — most importantly the Telegram-triggered
database backup. On shared hosting, one common fallback is a cron-driven worker.

**What no longer depends on it:** verification mail, verification SMS and
password-reset mail are delivered inline (`BROCA_NOTIFICATIONS_QUEUE=sync`, the
default) because they are the only messages a user must receive during the
request that creates the account, and a missing worker used to leave every new
account permanently unverified. Keep `sync` unless you have monitored the
worker draining; if you do switch to `database`, the cron below becomes
launch-critical rather than merely desirable.

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
- [ ] `php artisan broca:sync-plans` run — **/plans shows three cards**
      (migrations do not create plan rows; without this the page shows only the
      free tier)
- [ ] `php artisan broca:ops:health` exits 0
- [ ] `php artisan broca:sms:test 09…` succeeds once a panel is configured
- [ ] A real signup receives **both** the email link and the SMS code
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

- **three cards are rendered** (رایگان / یک‌ماهه ۲۷۰ تومان / سه‌ماهه ۶۰۰ تومان).
  If only one shows, the `plans` table is empty — run
  `php artisan broca:sync-plans` (the deploy hook does it; the manual form
  exists for hosts deployed before that hook was added).
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

# Product data + delivery
php artisan broca:sync-plans               # canonical lineup (free / 1m / 3m)
php artisan broca:ops:health               # exits non-zero when anything is off
php artisan broca:sms:test 09123456789

# Account recovery ("nobody can sign in")
php artisan broca:user:diagnose user@example.com     # read-only
php artisan broca:user:repair user@example.com --activate --verify-email
php artisan broca:identifiers:normalize --dry-run
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

Note that signup verification no longer fails this way — see §7.

### Signup reaches nobody (no email, no SMS)

Check in this order:

1. `php artisan broca:ops:health` — it names the mail driver, the notification
   queue, the pending-job count, the SMS driver and whether its URL is set.
2. `MAIL_MAILER` is `log`/`array` → nothing will ever leave the server.
3. Failed jobs piling up → `php artisan queue:retry all` then drain the queue.
4. `SMS_DRIVER` is `log` → codes are written to `storage/logs/laravel.log`,
   not sent.
5. Still nothing → `php artisan broca:user:diagnose user@example.com` (the
   account may be unverifiable for a different reason, e.g. a legacy password
   hash).

### /plans shows one card instead of three

The `plans` table is empty or incomplete: `php artisan broca:sync-plans`.
The deploy hook runs it on every release; older deployments need it once by
hand. See `docs/RUNBOOK.md` §12.

### The admin cannot sign in with the right password

`php artisan broca:user:diagnose {email}` then
`php artisan broca:user:repair {email} --activate --verify-email`
(or `--promote`). All four causes and their cures are in `docs/RUNBOOK.md` §13.

## 16. Recommended deployment order for updates

For future releases on cPanel:

```bash
git pull
composer install --no-dev --optimize-autoloader
npm ci --ignore-scripts && npm run build
php artisan migrate --force
php artisan broca:sync-plans
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

If you changed Telegram env values, re-run:

```bash
php artisan broca:telegram-set-webhook
```

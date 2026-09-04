# Production runbook — Broca

Every step required to take this application from the repository to a
hardened, observable, recoverable production deployment on shared/cPanel
hosting (MySQL) or a VPS. **Do not open checkout until this list is done.**

## 1. Environment checklist (`.env`)

```
APP_ENV=production
APP_DEBUG=false                      # stack traces must never leak
APP_URL=https://broca.example        # signed URLs + gateway callbacks depend on this
APP_FORCE_HTTPS=true                 # global HTTP→HTTPS redirect
SESSION_SECURE_COOKIE=true           # session cookie only over TLS
TRUSTED_PROXIES=*                    # ONLY behind Cloudflare/reverse proxy with TLS at the proxy
BROCA_CHECKOUT_ENABLED=true          # kill switch during payment incidents

ZARINPAL_MERCHANT_ID=<real merchant> # requires e-namad (اینماد) verification
ZARINPAL_SANDBOX=false               # NEVER ship sandbox in production
ZARINPAL_CALLBACK_URL="https://broca.example/payments/zarinpal/callback"

MAIL_MAILER=smtp                     # + real SMTP credentials
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
```

Verify after deploy: `php artisan about`, `php artisan config:cache` is safe
(all env reads happen at boot), `curl -I https://…/health`.

## 2. Cron entries (cPanel → Cron Jobs)

```
* * * * * cd /home/USER/broca && php artisan schedule:run >> /dev/null 2>&1
```

This drives: hourly `broca:expire-subscriptions`, daily `broca:reconcile-payments`
(03:30), daily `broca:backup-database` (02:00). **Without the cron, invoices
never expire and payments never reconcile.**

## 3. Queue worker

Verification and password-reset emails are queued. One worker minimum:

```
php artisan queue:work --tries=3 --backoff=60 --timeout=60
```

- cPanel: run via a supervisor-like cron restart every 5 minutes
  (`queue:restart` check) or use the host's process manager if offered.
- VPS: systemd unit or supervisor with `autostart=true`, `user=www-data`.
- Monitor `failed_jobs` — a growing table means users are not getting email.

## 4. TLS & proxies

1. Issue a certificate (Let's Encrypt via cPanel).
2. `APP_FORCE_HTTPS=true` handles redirects; add HSTS only after confirming
   every subdomain also serves HTTPS.
3. Behind Cloudflare: set `TRUSTED_PROXIES=*` (rate limiting and audit IPs
   depend on the real client IP). Restrict Cloudflare → origin to TLS only.
   **Without this, all visitors share one rate-limit bucket.**

## 5. Backups (the financial ledger!)

Daily `broca:backup-database` writes `storage/app/backups/broca-*.sql.gz`.
That is only the local half — same-disk backups die with the host:

1. Sync off-box nightly: `rclone`/`rsync` cron to object storage or another
   server, e.g. `0 4 * * * rclone copy storage/app/backups remote:broca-backups`.
2. Monthly: download a backup and actually restore it into a scratch DB
   (`gunzip < file.sql.gz | mysql broca_verify`). An untested backup is a hope,
   not a backup.
3. Target RPO: 24h (one day of invoices at risk). For better, run the backup
   command more frequently via cron.

## 6. Monitoring & alerting

- **Error tracking:** `composer require sentry/sentry-laravel` (deferred until
  composer is available on the host) — until then, tail
  `storage/logs/laravel.log` and alert on `production.ERROR`.
- **Uptime:** external probe on `/health` (expects 200; 503 = DB down). The
  endpoint is throttled to 60/min, enough for 10-second probes.
- **Payment health:** run `php artisan broca:reconcile-payments` output into
  monitoring; any "healed" line means a user paid without getting access —
  contact them proactively. Alert on more than 0 healed per day.
- **Disk:** backups + logs grow; alert at 80%.

## 7. Deployment

```
git pull
composer install --no-dev --optimize-autoloader
npm ci --ignore-scripts && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart
```

For shared hosting / cPanel specifics, including document-root setup and
Telegram webhook registration, see `docs/CPANEL_DEPLOYMENT.md`.

Rollback: `git checkout <previous-tag> && composer install && php artisan migrate:rollback`
(migrations are reversible; verify with `php artisan migrate:status`).

## 8. Public runtime verification gates

These are launch gates for the current trust-first public redesign and should
be checked on the live production domain after every deploy that touches public
views, metadata, plans, or content discovery.

### Browser checks

- `/` homepage:
  - title, meta description and canonical are correct
  - FAQ accordions render correctly
  - primary CTAs go to register / catalog as expected
- `/catalog`:
  - default page is indexable (`robots=index, follow`)
  - search/filter state works without broken layout
  - filtered states show `noindex, follow`
- `/subjects/{slug}`:
  - subject title/description match the visible subject
  - course cards and CTAs open the correct public course pages
- `/plans`:
  - guest / authenticated / subscribed states each show the correct CTA path
  - paid-plan checkout buttons only appear when checkout is enabled
- one `/courses/{slug}` page and one `/blog/{slug}` page:
  - author/reviewer metadata renders
  - structured data exists in page source

### Source checks

Inspect page source or devtools and verify:

- canonical URL uses the production HTTPS host
- `meta description` is present and non-empty
- Open Graph tags are present
- JSON-LD is present where expected (course, blog, FAQ, item list, breadcrumb)
- no page leaks `localhost`, preview domains, or staging URLs

### CLI spot checks

```bash
curl -I https://example.com/
curl -I https://example.com/catalog
curl -I https://example.com/plans
curl -I https://example.com/robots.txt
curl -I https://example.com/sitemap.xml
```

If metadata/canonical values are wrong after deployment, re-clear and rebuild caches:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 9. Incident procedures

- **Payment incident (users paying without access):** set
  `BROCA_CHECKOUT_ENABLED=false`, run `broca:reconcile-payments`, check
  ZarinPal panel, contact affected users, then re-enable.
- **Suspected admin compromise:** suspend the account in DB (`status`),
  rotate its password, reset `totp_secret`/`recovery_codes`, review
  `/admin/activity`.
- **Data restore:** stop queue worker → restore dump → `php artisan cache:clear`
  → restart worker.

## 10. Pre-launch gates (blocking)

- [ ] Real legal copy approved for terms / privacy / medical disclaimer
      (current versions are placeholders — unacceptable for a paid medical
      education product).
- [ ] ZarinPal production merchant activated (requires اینماد).
- [ ] Real plan prices set in `/admin/plans` (seeder values are placeholders).
- [ ] A real video asset in `public/videos` wired through the admin
      `manifest_reference` field; placeholder provider replaced before scale.
      (Placeholders are provisioned automatically by `php artisan db:seed` /
      `php artisan broca:provision-media` from `database/placeholder-media/`.)
- [ ] CI green on MySQL (`.github/workflows/ci.yml`).
- [ ] `composer audit` passes with zero known-vulnerable dependencies
      (run locally; the CI step could not be added from this sandbox
      because the GitHub App lacks the `workflows` permission).
- [ ] One full sandbox payment → callback → subscription → expiry cycle.
- [ ] Backup restored successfully at least once.

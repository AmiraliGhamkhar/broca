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
BROCA_PASSWORD_MIN=8                 # shared floor for /register and /reset-password
BROCA_PASSWORD_LEAK_CHECK=false      # needs outbound api.pwnedpasswords.com — see §11

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
- Open Graph tags are present, including `og:image` (1200×630)
- JSON-LD is present where expected (course, blog, FAQ, item list, breadcrumb)
- public pages with a Markdown twin carry
  `<link rel="alternate" type="text/markdown" href="…">` (catalog, plans,
  subjects, courses, blog, legal — not auth/admin/learner)
- no page leaks `localhost`, preview domains, or staging URLs

### CLI spot checks

```bash
curl -I https://brocamed.ir/
curl -I https://brocamed.ir/catalog
curl -I https://brocamed.ir/plans
curl -I https://brocamed.ir/robots.txt
curl -I https://brocamed.ir/sitemap.xml
```

LLM/answer-engine surface (round 4, 2026-09-05):

```bash
# robots: retrieval agents + Content-Signal must be present
curl -s https://brocamed.ir/robots.txt | grep -E 'OAI-SearchBot|Content-Signal'

# Markdown twins: text/markdown, clean content, same data as the HTML page
curl -sI https://brocamed.ir/catalog.md | grep -i content-type
curl -s https://brocamed.ir/catalog.md | head -20

# curated agent index
curl -s https://brocamed.ir/llms.txt | head -20

# content negotiation: explicit markdown preference flips the representation
curl -s -H 'Accept: text/markdown' https://brocamed.ir/catalog | head -5
# …while browsers and plain curl keep getting HTML:
curl -s -H 'Accept: */*' https://brocamed.ir/catalog | head -5
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
- [ ] Plan prices confirmed and seeded: رایگان / ۱ ماهه ۲۷۰ تومان / ۳ ماهه
      ۶۰۰ تومان (client-confirmed 2026-09-07, `DatabaseSeeder::seedPlans()`).
      `/admin/plans` can edit them later; verify the live `/plans` card amounts
      and `/plans.md` agree.
- [ ] §11 auth smoke run completed on the real domain: signup → verification
      mail → login → logout, plus one full password reset. A signup that cannot
      be logged into is a launch blocker, not a bug.
- [ ] A real video asset in `public/videos` wired through the admin
      `manifest_reference` field; placeholder provider replaced before scale.
      (Placeholders are provisioned automatically by `php artisan db:seed` /
      `php artisan broca:provision-media` from `database/placeholder-media/`.)
- [ ] AI-crawler visibility policy confirmed by the client: current state is
      **allow-all on public pages** (retrieval + training agents), declared in
      `robots.txt` via explicit `Allow` blocks +
      `Content-Signal: search=yes, ai-input=yes, ai-train=yes`. To opt out of
      training only, flip the three training agents (GPTBot, ClaudeBot,
      Google-Extended) to `Disallow` in `SeoController::robots()` and set
      `ai-train=no` in the Content-Signal line.
- [ ] CI green on MySQL (`.github/workflows/ci.yml`).
- [ ] `composer audit` passes with zero known-vulnerable dependencies
      (run locally; the CI step could not be added from this sandbox
      because the GitHub App lacks the `workflows` permission).
- [ ] One full sandbox payment → callback → subscription → expiry cycle.
- [ ] Backup restored successfully at least once.

## 11. Auth & account operations (register / login / reset)

The signup funnel is register → verification mail → login → checkout. Only
email is verified today (no SMS OTP), so **mail + queue delivery is the one
dependency that can break every new account.**

### What the code enforces

| Concern | Behaviour |
|---|---|
| Credentials | `email` **or** Iranian mobile (`09…`, `+98…`, spaces/dashes/Persian digits accepted) + password |
| Normalization | email lowercased + trimmed, phone canonicalized — before validation, uniqueness *and* lookup, on both register and login |
| Password policy | ≥ `BROCA_PASSWORD_MIN` (floor 8), letters + upper + lower + digit; length capped at bcrypt's 72-byte read limit so an over-long password can never be silently truncated |
| Breach check | `uncompromised()` only when `BROCA_PASSWORD_LEAK_CHECK=true` — it calls api.pwnedpasswords.com *during* the POST, so leave it off unless that host is reachable |
| Mass assignment | `is_admin` / `status` are not fillable; payloads trying to set them are ignored |
| Suspension | login rejected (extra `status` credential) + live session rows deleted when an admin suspends + `active` middleware on every authenticated route |
| Password reset | invalidates all of the user's session rows and rotates the remember token |
| 2FA (admin) | TOTP 30 s with ±1 step drift, 5 tries/min/admin, single-use recovery codes (bcrypt), the pass flag regenerates the session, and an accepted code cannot be replayed inside its window |

### Rate limits (all Persian-facing)

| Limiter | Budget | Key |
|---|---|---|
| `registration` | 6/min | IP |
| `login` | 10/min per IP **+** 5/min per identifier·IP | as stated |
| `password-reset` | 6/min | email (IP when email is absent or invalid) |
| `verification-resend` | 3/min | user id |
| `admin-2fa` | 5/min | admin user id |

`TRUSTED_PROXIES` is not cosmetic here: without it behind Cloudflare, every
visitor lands in **one** bucket, and a single user's typo storm locks the
site's login form for everyone.

### 10-minute verification after deploy

```bash
# 1. queue is actually draining (queued mail depends on it)
php artisan tinker --execute 'echo DB::table("jobs")->count();'   # expect 0

# 2. the mail transport is really talking SMTP
php artisan tinker --execute 'echo config("mail.default");'       # expect smtp

# 3. the app answers over the final HTTPS origin (signed URLs depend on APP_URL)
curl -sI https://broca.example/login | head -1                    # expect 200
```

Then, in the browser:

1. `/register` with a real inbox → lands on the verification notice → the mail
   arrives → the link returns to `/dashboard`.
2. `/login` with the **phone** typed as `+۹۸ ۹۱۲ …` → works (same canonical
   form as the stored value).
3. Sign out → "فراموشی گذرواژه" → reset link → new password → the *old* session
   must be logged out and only the new password accepted.
4. Six wrong `/login` tries for one account → the fifth already answers
   «تعداد تلاش‌ها زیاد است…».
5. Suspend an account in `/admin/users` → its live tab loses access on the next
   request, and login with the correct password stays refused.

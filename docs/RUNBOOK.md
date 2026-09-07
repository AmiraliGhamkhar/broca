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
php artisan broca:sync-plans          # the canonical lineup — see §12
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart
```

For shared hosting / cPanel specifics, including document-root setup and
Telegram webhook registration, see `docs/CPANEL_DEPLOYMENT.md`.

Rollback: `git checkout <previous-tag> && composer install && php artisan migrate:rollback`
(migrations are reversible; verify with `php artisan migrate:status`).

### Why `npm run build` wipes `public/build` first

`public/build` is committed (a Node-less cPanel host cannot build), so CI fails a
pull request when `npm run build` changes it. That gate recently fired on a PR
that touched no frontend source, and the cause is worth remembering: **Tailwind's
source detection scans the whole repository, including its own previous compiled
stylesheet in `public/build`.** A rebuild performed on top of the committed
artifacts therefore finds utility-shaped text in that file and emits it again —
the build that bit us re-created a one-word filter utility (present in every
stylesheet since `main`, which is why every rebuild changed the content hash with
nobody editing anything), and each later build inherited it.

The fix is in the `build` script: `node -e "require('fs').rmSync('public/build',
{recursive:true,force:true})" && vite build`. Deleting the directory whose files
are about to be regenerated cannot lose anything, and it makes the build
idempotent: from a polluted tree the first run restores exactly the committed
artifacts, and `git status --porcelain public/build` prints nothing afterwards.

Measured, and **does not** work — do not re-try these:

- `@source not "public/build"` in `resources/css/app.css` (also the `**` glob
  form): the scanner still reads the old output.
- `build.emptyOutDir: true`, in the config and as `vite build --emptyOutDir`:
  the directory is emptied after Tailwind has already scanned it.
- Running the wipe from `vite.config.js` at import time: also too late, because
  `@tailwindcss/vite` creates its scanner during config loading, before the body
  of that file executes. It has to happen in the shell command.
- Replacing auto-detection with `@import "tailwindcss" source(none)` plus
  explicit `@source` globs: built fine but dropped 470 real utilities.

One consequence to keep in mind: because every file in the repo is a source, a
bare utility class name written in a markdown note or a code comment becomes a
"used" class and lands in the CSS. Describe classes in prose in `docs/` and
`DECISIONS.md` rather than quoting a selector.

### Test-suite note: rate limiters are cache state, not table state

`tests/TestCase.php` clears the cache store in `setUp()`. Every limiter (named
limiters, the login counter, the two 2FA budgets) lives in the cache, while
`RefreshDatabase` only truncates tables — so on a persistent cache store a test
that posts to `/register` or `/login` a few times is answered with a 429 instead
of the redirect it asserts, and the failure looks like a bug in the auth code
rather than in the harness. A test that *wants* throttling builds the state up
inside its own body.

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

The signup funnel is register → **verification** → login → checkout, and
verification now has two independent paths: the emailed link **and** an SMS
one-time code to the registered mobile number. **Either one activates the
account**, so mail delivery is no longer a single point of failure — but it is
still the path most students will use, so it stays the first thing to check.

Detail and panel contract: `docs/SMS_AND_VERIFICATION.md`.

### What the code enforces

| Concern | Behaviour |
|---|---|
| Credentials | `email` **or** Iranian mobile (`09…`, `+98…`, spaces/dashes/Persian digits accepted) + password |
| Normalization | email lowercased + trimmed, phone canonicalized — before validation, uniqueness *and* lookup, on both register and login |
| Password policy | ≥ `BROCA_PASSWORD_MIN` (floor 8), letters + upper + lower + digit; length capped at bcrypt's 72-byte read limit so an over-long password can never be silently truncated |
| Persian error copy | Every rule that can reject a password has its own message in `RegisterUserRequest::messages()`. The `Password` rule's failures arrive keyed by rule name (`validation.password.mixed`), which `attributes()` cannot translate - add a message there whenever a rule is added, or users get English keys in a Persian form |
| Breach check | `uncompromised()` only when `BROCA_PASSWORD_LEAK_CHECK=true` — it calls api.pwnedpasswords.com *during* the POST, so leave it off unless that host is reachable |
| Mass assignment | `is_admin` / `status` are not fillable; payloads trying to set them are ignored |
| Garbage input | array payloads (`name[]=x`), over-long fields and **invalid UTF-8** all come back as validation errors — the null-returning `/u` regex calls in `PhoneNormalizer` are guarded, so a pasted mojibake byte cannot 500 the register or login form |
| Verification | emailed link **or** SMS code; `verified.contact` (`App\Http\Middleware\EnsureVerifiedContact`) accepts either and guards `/dashboard`, `/checkout`, playback and `/admin` |
| Mobile code | 6 digits, bcrypt-hashed at rest, 10-minute TTL, 5 attempts per code, cleared on use/expiry/lockout, 60 s resend cooldown — the TTL and the attempt budget are what make a table dump unprofitable |
| Delivery | transactional mail and SMS go out **inline** (`BROCA_NOTIFICATIONS_QUEUE=sync` by default). A failed send is `report()`ed and costs the user a resend, never a 500; when neither channel can leave the building the app logs `critical` with the account id |
| Lookup | `App\Support\UserLookup` matches the canonical identifier first (indexed), then the historical spellings a row may still hold (`Admin@Example.com `, `+98912…`, Persian digits). Shared with the operator commands so the form and the CLI can never disagree |
| Suspension | login rejected **and reported as such** (an inactive account no longer masquerades as a wrong password) + live session rows deleted when an admin suspends + `active` middleware on every authenticated route |
| Password reset | invalidates all of the user's session rows and rotates the remember token |
| 2FA (admin) | TOTP 30 s with ±1 step drift, 5 tries/min/admin, single-use recovery codes (bcrypt), the pass flag regenerates the session, and an accepted code cannot be replayed inside its window |

### Rate limits (all Persian-facing)

| Limiter | Budget | Key |
|---|---|---|
| `registration` | 6/min | IP |
| `login` | 10/min per IP **+** 5/min per identifier·IP | as stated |
| `password-reset` | 6/min | email (IP when email is absent or invalid) |
| `verification-resend` | 3/min | user id — **shared** by "resend the email link" and "resend the SMS code" |
| `phone-verify` | 10/min | user id; the outer bound on code entry. The per-code attempt budget (5) is enforced in `PhoneVerificationService`, this stops an attacker burning *code after code*, each of which is a paid text message |
| `admin-2fa-verify` | 5/min | admin user id — **shared** by challenge, recover, enable and disable (one attacker with four forms is one attacker). The middleware counts every request to those routes; the sign-in challenge additionally keeps its own counter, keyed differently, only to word the "wait N seconds" message and to be cleared by a successful attempt |
| `admin-2fa-codes` | 10/min | admin user id; recovery-code regeneration verifies no secret, so it is not part of the guessing budget |

`TRUSTED_PROXIES` is not cosmetic here: without it behind Cloudflare, every
visitor lands in **one** bucket, and a single user's typo storm locks the
site's login form for everyone.

### 10-minute verification after deploy

```bash
# 0. one screen that covers all of it: exits non-zero when anything is wrong
php artisan broca:ops:health

# 1. queue is actually draining (backups and any non-sync queue depend on it)
php artisan tinker --execute 'echo DB::table("jobs")->count();'   # expect 0

# 2. the mail transport is really talking SMTP (not `log`, not `array`)
php artisan tinker --execute 'echo config("mail.default");'       # expect smtp

# 3. transactional messages are not waiting on that queue
php artisan tinker --execute 'echo config("broca.notifications.queue");'  # sync unless a worker is monitored

# 4. the SMS path reaches the panel
php artisan broca:sms:test 09123456789

# 5. the app answers over the final HTTPS origin (signed URLs depend on APP_URL)
curl -sI https://broca.example/login | head -1                    # expect 200
```

Then, in the browser:

1. `/register` with a real inbox and a real handset → lands on the
   verification notice → **both** the mail and the SMS arrive → either one
   returns to `/dashboard`. Repeat once with the mail path unavailable
   (`MAIL_MAILER=log`) to prove the code alone is enough.
2. `/login` with the **phone** typed as `+۹۸ ۹۱۲ …` → works (same canonical
   form as the stored value).
3. Sign out → "فراموشی گذرواژه" → reset link → new password → the *old* session
   must be logged out and only the new password accepted.
4. Six wrong `/login` tries for one account → the fifth already answers
   «تعداد تلاش‌ها زیاد است…».
5. Suspend an account in `/admin/users` → its live tab loses access on the next
   request, and login with the correct password stays refused.

---

## 12. The plan lineup (why /plans can lose cards)

`/plans` renders whatever `plans` rows are active, and **migrations do not
create them** — the deploy pipeline runs `artisan migrate`, never `artisan
db:seed`. On a host where the seeder was never run by hand the table stays
empty and the page shows the controller's synthetic free tier and nothing else:
one card where the client expects three.

The canonical lineup (رایگان / یک‌ماهه ۲۷۰ تومان / سه‌ماهه ۶۰۰ تومان) lives in
`App\Support\PlanCatalog`, and one idempotent command reconciles it with the
database:

```bash
php artisan broca:sync-plans            # create what is missing; never touch an edited price
php artisan broca:sync-plans --reset    # also restore canonical names/prices/ordering
php artisan broca:sync-plans --dry-run
```

It is wired into the cPanel deploy hook, and the Telegram bot offers the same
repair under «🩺 عملیات و سلامت → 💳 وضعیت پلن‌ها» (confirmed before it writes).
The admin panel and `broca:ops:health` both flag an incomplete lineup.

Adding or removing a *product tier* is still a code change (`PlanCatalog`);
editing a price is a database change (Admin › Plans) and survives every sync.

---

## 13. "Nobody can sign in" — the four causes and their cures

```bash
php artisan broca:user:diagnose {email|mobile|id}     # read-only, names the fix
php artisan broca:user:repair   {email|mobile|id} …   # applies it
```

| Symptom | Cause | Cure |
|---|---|---|
| «…یا گذرواژه درست نیست» with a correct password | the stored identifier is not canonical (`Admin@Example.com `, `+98912…`) so the lookup cannot see it | `--normalize` (login already tolerates it; this makes the data canonical) |
| Same message, account is real | `status` is not `active` | `--activate` |
| Same message, always | the stored `password` is not bcrypt/argon (an md5/sha1/plaintext import) — **no** password will ever match | `--password="new"` (validated against the app policy) |
| Login works, `/dashboard` bounces to the verification page | no verified contact channel | `--verify-email` (or `--verify-phone`), or the matching button in the bot |
| Reached `/admin` and nothing else works | the last active admin was suspended or demoted | `--activate --promote`; all three surfaces now refuse to create this state |

`broca:identifiers:normalize` rewrites every stored email and phone into the
canonical spelling in one pass (`--dry-run` first), which is the cure for a
whole imported table.

The bot's user card shows the same four facts (status, verified email, verified
mobile, whether login can succeed) and can clear the verification ones in a
tap — the fastest route when the only tool at hand is a phone.

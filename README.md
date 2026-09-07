# Broca (بروکا)

Persian (RTL) subscription-based medical-education platform: video courses, downloadable notes, spaced-repetition flashcards, and untimed quizzes behind a freemium paywall with Iranian gateway payments (ZarinPal via `shetabit/payment`).

## Stack

- **PHP 8.4+ / Laravel 13** — Blade + Tailwind CSS v4 + Alpine.js (plain HTML/CSS/JS to the browser; no SPA, no parallel JSON API)
- **MySQL 8** by default (shared/cPanel-hosting friendly); PostgreSQL works if you control the host — see `DECISIONS.md`
- **shetabit/payment v7** — ZarinPal driver (Zibal available as a second driver)
- **Vite** for assets; Vazirmatn self-hosted (`public/fonts/vazirmatn`, OFL)
- **PHPUnit** for tests

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate

# MySQL: create a database and set DB_* in .env
# (or use DB_CONNECTION=sqlite && touch database/database.sqlite)
php artisan migrate --seed

npm install --ignore-scripts
npm run build

php artisan serve        # http://127.0.0.1:8000
```

The seeder creates an admin (`admin@broca.test` / `password`), three plans (free / 1-month / 3-month with **placeholder prices**), and one published course exercising the freemium limits.

## Payments (sandbox)

Set in `.env`:

```
ZARINPAL_MERCHANT_ID=<sandbox merchant id>
ZARINPAL_SANDBOX=true
ZARINPAL_CALLBACK_URL="${APP_URL}/payments/zarinpal/callback"
```

Money is stored in **Rial as integers** — never floats. The payment flow: invoice → gateway redirect → server-side verification (amount-bound) → idempotent subscription activation (row-locked, replay-safe).

## Signup verification (email **and** SMS)

Registration sends **two** independent proofs of contact, and **either one**
activates the account:

| Channel | Delivered | Accepted at |
|---|---|---|
| Email | signed link (`VerifyEmailNotification`) | `/email/verify/{id}/{hash}` |
| Mobile | 6-digit one-time code by SMS (`PhoneVerificationNotification`) | `POST /phone/verify` |

`App\Http\Middleware\EnsureVerifiedContact` (alias `verified.contact`) guards
`/dashboard`, `/checkout`, playback and `/admin`, and passes when **either**
`email_verified_at` **or** `phone_verified_at` is set.

Two properties this buys, both learned the hard way:

- **Mail is not a single point of failure.** The funnel used to have one path
  in; a queue worker that never ran, a blocked SMTP port or a spam folder was
  enough to leave a new account permanently unusable.
- **Transactional messages are delivered inline** (`BROCA_NOTIFICATIONS_QUEUE=sync`,
  default). They are the only messages a user must receive *during* the request
  that creates the account, so they must not wait on a worker that shared
  hosting may not be running. Backups and media jobs still use
  `QUEUE_CONNECTION`.

SMS is **driver-based** and ships with a `log` default — nothing is sent until a
panel is configured. Going live is an env change (generic HTTP driver with a
body template), not a code change. Full detail, including the panel contract and
the failure modes: **`docs/SMS_AND_VERIFICATION.md`**.

```bash
php artisan broca:ops:health        # mail driver, queue backlog, SMS, plans, admins
php artisan broca:sms:test 09123456789
```

## Tests

```bash
composer test          # php artisan test
vendor/bin/pint --test # code style
```

The money paths are covered first-class: freemium gating (`ContentAccessTest`) and payment callback/idempotency (`PaymentTest`), plus quiz grading (`QuizTest`) and the SM-2 scheduler (`SrsServiceTest`).

## Telegram admin bot

This repo now includes a webhook-driven **Telegram admin bot** for Persian operators.

### What it can do

- Manage **subscription plans**
- Create, edit, show/hide, and safely delete **subjects**
- Create, edit, review, publish, archive, and delete **blog posts and courses**
- Upload, replace, publish, designate as free, or remove **videos and notes/booklets**
- Create, edit, review, publish, archive, and delete **flashcard decks/cards and quizzes/questions**
- Upload, replace, or remove **course/blog cover images**
- Upload, replace, or reset the website **logo and landing-page hero image**, including its accessible alt text
- Search and manage **users**, account status, and administrator roles with confirmation and a last-admin safeguard
- Show **dashboard totals and recent admin activity**
- Trigger a **database backup** and send the dump file back in Telegram
- **Repair and inspect the install**: health report (mail driver, queue
  backlog, SMS, plan lineup, admin access), restore the canonical plan lineup,
  see/drain the queue, send a test SMS, and verify or re-activate a user
  account — with a last-admin guard so the site can never be locked out from a
  phone

The UX is primarily an **inline-button wizard**: admins navigate, pick items, confirm deletes, and approve publication with inline buttons; they only type when actual field values/content are needed.

### Environment variables

Set these in `.env`:

```env
TELEGRAM_BOT_ENABLED=true
TELEGRAM_BOT_TOKEN=123456:telegram-token
TELEGRAM_WEBHOOK_SECRET=some-random-secret
TELEGRAM_ADMIN_IDS=11111111,22222222
BROCA_EXTERNAL_VIDEO_ORIGINS=https://cdn.example.com,https://videos.example.org
```

### Webhook setup

Once your `APP_URL` points to the public site, register the webhook:

```bash
php artisan broca:telegram-set-webhook
```

You can also permanently allow extra admins in the database:

```bash
php artisan broca:telegram-admin 11111111 --first-name="Admin"
```

Telegram will call `POST /telegram/webhook` with the secret-token header. The route is CSRF-exempt on purpose.

For shared-hosting deployment and cPanel-specific webhook steps, see `docs/CPANEL_DEPLOYMENT.md`.

### First use in Telegram

Normally admins just send `/start` or `/help` once and continue from the inline menus.

Fallback slash commands still exist for direct access:

- `/plans`, `/plan_new`, `/plan_edit {id}`
- `/blogs`, `/blog_new`, `/blog_edit {id}`
- `/subjects`, `/subject_new`, `/subject_edit {id}`, `/courses`, `/course_new`, `/course_edit {id}`
- `/videos`, `/video_new`, `/video_edit {id}`
- `/notes`, `/note_new`, `/note_edit {id}`
- `/decks`, `/deck_new`, `/deck_edit {id}`
- `/card_new {deck_id}`, `/card_edit {id}`
- `/quizzes`, `/quiz_new`, `/quiz_edit {id}`
- `/question_new {quiz_id}`, `/question_edit {id}`
- `/set_course_cover {id}`, `/set_blog_cover {id}`
- `/users`, `/user_find {name|email|phone|id}`, `/user_manage {id}`
- `/user_activate {id}`, `/user_verify {id}` — clear the two things that most
  often block a correct password (inactive account, no verified contact)
- `/appearance`, `/dashboard`, `/activity`, `/backup_db`, `/cancel`
- `/health` — the same report as `php artisan broca:ops:health`
- `/plans_restore` — recreate the canonical plan lineup (free / 1-month / 3-month)
- `/jobs` — queue status (plus inline buttons to drain it or retry failures)
- `/sms_test {phone}` — send one real message through the configured driver

The bot sends a fully Persian RTL form template; fill it in and send it back. English field names remain accepted for backward compatibility. For video/note uploads, attach the file directly or choose the Persian URL/reference mode shown in the form. Direct publish from the form is blocked for medical content; move items through the review/publish buttons instead.

## Docs

- `SPEC.md` — original product/technical specification
- `ANALYSIS.md` — line-by-line audit trail (rounds 1–9) with fix status
- `DECISIONS.md` — running log of decisions and open questions
- `docs/PROJECT_STATUS.md` — what's done / in-progress / pending
- `docs/SMS_AND_VERIFICATION.md` — the two-channel signup, the SMS panel
  contract, and what to do when "nothing arrived"
- `docs/RUNNING_LOCALLY.md` — setup details
- `docs/RUNBOOK.md` — production ops, cron, verification gates
- `docs/CPANEL_DEPLOYMENT.md` — shared hosting / cPanel deployment + Telegram webhook checklist

## SEO & LLM visibility

- Dynamic `robots.txt` (retrieval + training AI agents, `Content-Signal` usage
  policy) and `sitemap.xml` — no static file (a static one would shadow the
  routes).
- Every public page has a clean **Markdown twin** at the same path with a
  `.md` suffix (`/catalog.md`, `/courses/{slug}.md`, …) built from the same
  database rows as the HTML, plus a curated `/llms.txt` index. Clients sending
  `Accept: text/markdown` get the twin via content negotiation (`Vary:
  Accept`); browsers and plain `curl` are never affected.
- Structured data: Organization, WebSite + SearchAction, Course (with `url`),
  ItemList of Course entities, BreadcrumbList (visible + JSON-LD), BlogPosting,
  FAQPage; og:image, canonical, and noindex on all gated/error states.

## Security posture

- CSRF everywhere (web middleware), mass-assignment locked down (`is_admin`/`status` are **not** fillable)
- Auth + password-reset routes rate-limited; login has per-identifier+IP throttling
- Mobile codes are bcrypt-hashed, expire in 10 minutes, allow 5 attempts per
  code and are cleared on use; resends are rate-limited per user because a text
  message costs money
- No operator path can remove the last active administrator (web panel, bot and
  `broca:user:repair` all refuse), so the site cannot be locked out of `/admin`
- Passwords checked against known breaches (HaveIBeenPwned, fail-open) on register + reset; a reset kills **every** stored session of the user
- Suspended users are logged out mid-session by the `active` middleware
- Entitlement enforced server-side by `ContentPolicy` + `EntitlementService` — never in JS alone
- Video playback via short-lived signed URLs that re-check entitlement
- Telegram bot media downloads validate HTTP success + size and refuse private/loopback hosts (SSRF guard)
- Strict CSP, HSTS, secure cookies, nosniff on every response

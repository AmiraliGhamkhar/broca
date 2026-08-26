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

## Tests

```bash
composer test          # php artisan test
vendor/bin/pint --test # code style
```

The money paths are covered first-class: freemium gating (`ContentAccessTest`) and payment callback/idempotency (`PaymentTest`), plus quiz grading (`QuizTest`) and the SM-2 scheduler (`SrsServiceTest`).

## Docs

- `SPEC.md` — original product/technical specification
- `DECISIONS.md` — running log of decisions and open questions
- `docs/PROJECT_STATUS.md` — what's done / in-progress / pending
- `docs/RUNNING_LOCALLY.md` — setup details

## Security posture

- CSRF everywhere (web middleware), mass-assignment locked down (`is_admin`/`status` are **not** fillable)
- Auth + password-reset routes rate-limited; login has per-identifier+IP throttling
- Suspended users are logged out mid-session by the `active` middleware
- Entitlement enforced server-side by `ContentPolicy` + `EntitlementService` — never in JS alone
- Video playback via short-lived signed URLs that re-check entitlement

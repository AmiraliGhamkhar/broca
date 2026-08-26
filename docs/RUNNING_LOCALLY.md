# Running Broca locally

## Prerequisites

- PHP **8.4+** (required by shetabit/payment v7) + Composer
- Node ≥ 18 (npm)
- MySQL 8 (or SQLite for a quick start)

## Steps

1. Install PHP 8.4 (`herd install php@8.4` or your package manager).
2. `composer install` to pull PHP deps.
3. Copy `.env.example` → `.env`; set `DB_*` for MySQL, or use `DB_CONNECTION=sqlite` and `touch database/database.sqlite`.
4. `php artisan key:generate`.
5. `php artisan migrate --seed` — seeds an admin (`admin@broca.test` / `password`), plans, and a sample published course.
6. `npm ci && npm run dev` (or `npm run build` for production assets).
7. `php artisan serve` → http://127.0.0.1:8000.
8. Run tests with `composer test` (alias for `php artisan test`).

## Payment sandbox

Set in `.env` (note the exact names — they match `config/payment.php`):

```
ZARINPAL_MERCHANT_ID=<sandbox merchant id>
ZARINPAL_SANDBOX=true
ZARINPAL_CALLBACK_URL="${APP_URL}/payments/zarinpal/callback"
```

Amounts are integers in Rial. `ZARINPAL_SANDBOX=true` switches the driver to
ZarinPal's sandbox endpoints.

## Common issues

- PHP version mismatch → `php -v` must be ≥ 8.4 (composer will refuse otherwise).
- Missing SQLite file → `touch database/database.sqlite`.
- Asset compile errors → `npm ci` again; ensure Node ≥ 18.
- Fonts 404 → the Vazirmatn woff2 files live in `public/fonts/vazirmatn/` (OFL-licensed).
- Video placeholder 404 → drop any small MP4 at `public/videos/sample.mp4`; the signed playback chain streams it after entitlement checks.

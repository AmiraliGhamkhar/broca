# Running Broca locally

## Prerequisites
- PHP 8.4 + Composer
- Node ≥ 18 (npm)
- SQLite or MySQL/PostgreSQL if preferred

## Steps
1. Install PHP 8.4 (`herd install php@8.4`).
2. `php composer.phar install` to pull PHP deps.
3. Copy `.env.example` → `.env`; set `DB_CONNECTION=sqlite` and `PAYMENT_ZARINPAL_MODE=sandbox`.
4. `touch database/database.sqlite && php artisan migrate --seed`.
5. `npm ci && npm run dev`.
6. `php artisan serve` → http://127.0.0.1:8000.
7. Run tests with `php artisan test`.

## Payment sandbox
Set `PAYMENT_ZARINPAL_MERCHANT_ID` to your sandbox merchant ID.
Callback URL is read from `.env` (`PAYMENT_ZARINPAL_CALLBACK_URL`).

## Common issues
- PHP version mismatch → ensure `php -v` is 8.4.
- Missing SQLite file → `touch database/database.sqlite`.
- Asset compile errors → run `npm ci` again, ensure Node ≥ 18.

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
5. `php artisan migrate --seed` — seeds an admin (`admin@broca.test` / `password`), plans, a sample published course, and a blog post.
6. `php artisan storage:link` — needed for public cover images uploaded through the Telegram bot.
7. `npm ci && npm run dev` (or `npm run build` for production assets).
8. `php artisan serve` → http://127.0.0.1:8000.
9. Run tests with `composer test` (alias for `php artisan test`).

## Signup verification and SMS (local defaults)

Out of the box nothing leaves your machine:

- **Mail** — set `MAIL_MAILER=log` and read the verification link in
  `storage/logs/laravel.log`, or point `MAIL_*` at Mailpit/Mailhog.
- **SMS** — `SMS_DRIVER=log` (the default), so the one-time code is written to
  the same log file instead of being texted. The rest of the flow is real:
  register, open `/email/verify`, type the code from the log.

Transactional mail and SMS are delivered **inline**
(`BROCA_NOTIFICATIONS_QUEUE=sync`) so none of this depends on a queue worker.

```env
BROCA_NOTIFICATIONS_QUEUE=sync
BROCA_PHONE_VERIFICATION=true
SMS_DRIVER=log
```

To exercise a real panel, switch to the generic HTTP driver and check it with
`php artisan broca:sms:test 09123456789` — full contract in
`docs/SMS_AND_VERIFICATION.md`.

## Telegram admin bot

Set these in `.env` if you want to exercise the webhook locally or on a staging host:

```env
TELEGRAM_BOT_ENABLED=true
TELEGRAM_BOT_TOKEN=123456:telegram-token
TELEGRAM_WEBHOOK_SECRET=some-random-secret
TELEGRAM_ADMIN_IDS=11111111,22222222
BROCA_EXTERNAL_VIDEO_ORIGINS=https://cdn.example.com
```

Once `APP_URL` is publicly reachable, register the webhook with:

```bash
php artisan broca:telegram-set-webhook
php artisan broca:telegram-admin 11111111 --first-name="Admin"
```

The bot now uses an inline-button wizard UI for navigation/confirmation; admins still type actual field values into the sent form templates.

For shared hosting deployment and cPanel webhook setup, see `docs/CPANEL_DEPLOYMENT.md`.

## Payment sandbox

Set in `.env` (note the exact names — they match `config/payment.php`):

```
ZARINPAL_MERCHANT_ID=<sandbox merchant id>
ZARINPAL_SANDBOX=true
ZARINPAL_CALLBACK_URL="${APP_URL}/payments/zarinpal/callback"
```

Amounts are integers in Rial. `ZARINPAL_SANDBOX=true` switches the driver to
ZarinPal's sandbox endpoints.

## Plans on a fresh database

`php artisan migrate --seed` creates the three-tier lineup. If you migrate
without seeding, reconcile it from code:

```bash
php artisan broca:sync-plans     # free / 1-month / 3-month, idempotent
```

## Common issues

- PHP version mismatch → `php -v` must be ≥ 8.4 (composer will refuse otherwise).
- Missing SQLite file → `touch database/database.sqlite`.
- Asset compile errors → `npm ci` again; ensure Node ≥ 18.
- Fonts 404 → the Vazirmatn woff2 files live in `public/fonts/vazirmatn/` (OFL-licensed).
- Media 404s → run `php artisan broca:provision-media` (or re-seed); it copies the
  committed placeholders from `database/placeholder-media/` into
  `public/videos/` (playback) and `storage/app/private/notes/` (note downloads).
  Swap the files in `database/placeholder-media/` for the real assets and
  re-run the command — nothing in the code changes.

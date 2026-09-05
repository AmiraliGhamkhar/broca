<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled operations
|--------------------------------------------------------------------------
|
| On shared/cPanel hosting add a single cron entry running every minute:
|   * * * * * cd /home/USER/broca && php artisan schedule:run >> /dev/null 2>&1
| (see docs/RUNBOOK.md — without it, subscriptions and invoices never expire).
*/

/*
 * QUEUE DRAIN — launch-critical.
 *
 * Email verification and password-reset notifications implement ShouldQueue,
 * and QUEUE_CONNECTION defaults to `database`. Nothing consumed that queue:
 * the docs described a cron-driven worker but no schedule entry existed, so
 * on a stock cPanel install those emails sat in `jobs` forever.
 *
 * Blast radius: `verified` middleware gates /dashboard and /checkout, so a
 * new user could register and then never receive the verification link —
 * every signup locked out, and no purchase possible.
 *
 * --stop-when-empty keeps this cron-safe (the process exits instead of
 * becoming a daemon, which shared hosting forbids). withoutOverlapping stops
 * a slow SMTP round from stacking workers on the every-minute tick;
 * runInBackground keeps it off the scheduler's critical path.
 */
Schedule::command('queue:work --stop-when-empty --tries=3 --backoff=60 --timeout=55 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground();

Schedule::command('broca:expire-subscriptions')->hourly();

// Financial safety net: heal invoices paid at the gateway but unresolved
// locally; repair paid invoices missing their subscription row.
Schedule::command('broca:reconcile-payments')->dailyAt('03:30');

// Local backup half; the off-box copy is a host-level cron (RUNBOOK).
Schedule::command('broca:backup-database')->dailyAt('02:00');

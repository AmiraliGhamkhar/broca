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

Schedule::command('broca:expire-subscriptions')->hourly();

// Financial safety net: heal invoices paid at the gateway but unresolved
// locally; repair paid invoices missing their subscription row.
Schedule::command('broca:reconcile-payments')->dailyAt('03:30');

// Local backup half; the off-box copy is a host-level cron (RUNBOOK).
Schedule::command('broca:backup-database')->dailyAt('02:00');

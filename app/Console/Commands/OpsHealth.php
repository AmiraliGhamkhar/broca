<?php

namespace App\Console\Commands;

use App\Services\OpsHealthReport;
use Illuminate\Console\Command;

/**
 * `php artisan broca:ops:health`
 *
 * The first command to run when "users say nothing arrives" or "the prices
 * page looks wrong". It prints the same report the Telegram bot's health
 * button sends (App\Services\OpsHealthReport), so an operator without SSH can
 * still get it from their phone.
 *
 * Exits non-zero when a warning fired, so it can be wired into a cron or a
 * deploy check without parsing Persian text.
 */
class OpsHealth extends Command
{
    protected $signature = 'broca:ops:health {--quiet-report : Exit code only, no report body}';

    protected $description = 'Report delivery, queue, plan-lineup and admin-access health for this install';

    public function handle(OpsHealthReport $report): int
    {
        $data = $report->collect();

        if (! (bool) $this->option('quiet-report')) {
            $this->line($report->render());
        }

        return $data['warnings'] === [] ? self::SUCCESS : self::FAILURE;
    }
}

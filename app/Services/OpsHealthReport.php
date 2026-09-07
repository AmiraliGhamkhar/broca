<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\TelegramAdmin;
use App\Models\User;
use App\Support\PlanCatalog;
use Illuminate\Support\Facades\DB;

/**
 * One page of "is this install actually able to do its job".
 *
 * Shared by `php artisan broca:ops:health` and the Telegram bot's health
 * button: the operator reading it on a phone and the operator reading it over
 * SSH must see the same numbers, so the two callers render one array.
 *
 * It answers the questions behind the incidents this app has actually had:
 *   - will a new signup receive anything at all? (mail driver + queue backlog)
 *   - did the pricing page lose its cards? (plans vs the canonical lineup)
 *   - can anybody still reach /admin? (active admins)
 *   - is the SMS path configured? (driver + URL)
 *
 * Every DB read is guarded: on a half-migrated box the report must still
 * render — that box is exactly who needs it.
 */
class OpsHealthReport
{
    /**
     * @return array{lines: list<string>, warnings: list<string>}
     */
    public function collect(): array
    {
        $lines = [];
        $warnings = [];

        $lines[] = '🩺 وضعیت عملیاتی بروکا';
        $lines[] = 'محیط: '.config('app.env').' | دیباگ: '.((bool) config('app.debug') ? 'روشن' : 'خاموش');

        // --- Database -----------------------------------------------------
        $lines[] = '';
        $lines[] = '🗄 پایگاه داده: '.$this->databaseStatus();

        // --- Delivery (the thing that broke every signup) -----------------
        $lines[] = '';
        $lines[] = '📮 ایمیل';
        $lines[] = '• درایور: '.config('mail.default').' | فرستنده: '.(string) (config('mail.from.address') ?: '—');
        $lines[] = '• صف اعلان‌ها: '.config('broca.notifications.queue')
            .' | صف پیش‌فرض: '.config('queue.default');
        $lines[] = '• کارهای در انتظار: '.$this->pendingJobs().' | ناموفق: '.$this->failedJobs();

        if (config('mail.default') === 'log') {
            $warnings[] = 'درایور ایمیل روی log است؛ هیچ ایمیلی ارسال نمی‌شود (فقط در لاگ نوشته می‌شود).';
        }

        if ((string) config('mail.from.address', '') === '' || str_contains((string) config('mail.from.address'), 'example.com')) {
            $warnings[] = 'آدرس فرستندهٔ ایمیل تنظیم نشده است (MAIL_FROM_ADDRESS).';
        }

        // A queued notification on a host with no worker is the exact failure
        // that locked new accounts out: say it in words, not just as a count.
        if ((string) config('broca.notifications.queue') !== 'sync' && $this->pendingJobs() > 0) {
            $warnings[] = 'اعلان‌ها روی صف «'.config('broca.notifications.queue').'» هستند و '
                .$this->pendingJobs().' کار در صف مانده است؛ احتمالاً هیچ queue worker فعالی روی هاست نیست.'
                .' یا cron را فعال کنید یا BROCA_NOTIFICATIONS_QUEUE=sync بگذارید.';
        }

        if ($this->failedJobs() > 0) {
            $warnings[] = $this->failedJobs().' کار ناموفق در صف است (php artisan queue:retry all).';
        }

        // --- SMS ----------------------------------------------------------
        $lines[] = '';
        $lines[] = '📲 پیامک';
        $lines[] = '• فعال: '.((bool) config('sms.enabled', true) ? 'بله' : 'خیر')
            .' | درایور: '.config('sms.default');
        $lines[] = '• تأیید موبایل: '.((bool) config('broca.phone_verification.enabled', true) ? 'فعال' : 'غیرفعال');

        if (config('sms.default') === 'log') {
            $warnings[] = 'درایور پیامک روی log است؛ کد تأیید فقط در لاگ نوشته می‌شود و به دست کاربر نمی‌رسد.';
        }

        if (config('sms.default') === 'http' && trim((string) config('sms.drivers.http.url', '')) === '') {
            $warnings[] = 'درایور پیامک http است اما SMS_HTTP_URL تنظیم نشده است.';
        }

        // --- Plans --------------------------------------------------------
        $lines[] = '';
        $lines[] = '💳 پلن‌ها';

        $planCount = $this->safeCount(fn () => Plan::query()->count());
        $activePlans = $this->safeCount(fn () => Plan::query()->where('is_active', true)->count());
        $lines[] = '• تعداد: '.$planCount.' | فعال: '.$activePlans;

        $missing = $this->missingPlanCodes();
        if ($missing !== []) {
            $lines[] = '• ناقص: '.implode('، ', $missing);
            $warnings[] = 'چیدمان پلن‌ها ناقص است ('.implode('، ', $missing).')؛ روی صفحهٔ /plans فقط '
                .$activePlans.' کارت نمایش داده می‌شود. اجرا کنید: php artisan broca:sync-plans';
        }

        if ($activePlans < 2) {
            $warnings[] = 'کمتر از دو پلن فعال دارید؛ صفحهٔ قیمت‌گذاری عملاً چیزی برای مقایسه نشان نمی‌دهد.';
        }

        // --- People -------------------------------------------------------
        $lines[] = '';
        $lines[] = '👥 کاربران';

        $users = $this->safeCount(fn () => User::query()->count());
        $activeUsers = $this->safeCount(fn () => User::query()->where('status', 'active')->count());
        $admins = $this->safeCount(fn () => User::query()->where('is_admin', true)->count());
        $activeAdmins = $this->safeCount(fn () => User::query()->where('is_admin', true)->where('status', 'active')->count());
        $unverified = $this->safeCount(fn () => User::query()
            ->whereNull('email_verified_at')
            ->whereNull('phone_verified_at')
            ->count());

        $lines[] = '• کل: '.$users.' | فعال: '.$activeUsers;
        $lines[] = '• مدیران: '.$admins.' | مدیران فعال: '.$activeAdmins;
        $lines[] = '• بدون هیچ راه تأیید: '.$unverified;

        if ($users > 0 && $activeAdmins === 0) {
            $warnings[] = 'هیچ مدیر فعالی وجود ندارد؛ هیچ‌کس نمی‌تواند وارد /admin شود.'
                .' با php artisan broca:user:repair {شناسه} --activate --promote یک مدیر را برگردانید.';
        }

        // --- Telegram -----------------------------------------------------
        $lines[] = '';
        $lines[] = '🤖 تلگرام';
        $lines[] = '• فعال: '.((bool) config('services.telegram.enabled') ? 'بله' : 'خیر')
            .' | ادمین‌ها: '.$this->safeCount(fn () => TelegramAdmin::query()->where('is_active', true)->count());

        if (! (bool) config('services.telegram.enabled')) {
            $warnings[] = 'ربات تلگرام غیرفعال است (TELEGRAM_BOT_ENABLED=false).';
        }

        if (app()->isProduction() && (bool) config('app.debug')) {
            $warnings[] = 'APP_DEBUG روی محیط تولید روشن است.';
        }

        if ($warnings !== []) {
            $lines[] = '';
            $lines[] = '⚠️ هشدارها:';
            foreach ($warnings as $warning) {
                $lines[] = '• '.$warning;
            }
        } else {
            $lines[] = '';
            $lines[] = '✅ مورد هشداری یافت نشد.';
        }

        return ['lines' => $lines, 'warnings' => $warnings];
    }

    public function render(): string
    {
        return implode("\n", $this->collect()['lines']);
    }

    /** @return list<string> */
    public function missingPlanCodes(): array
    {
        $existing = $this->safe(fn () => Plan::query()->pluck('code')->filter()->all()) ?? [];

        return array_values(array_diff(PlanCatalog::codes(), $existing));
    }

    private function databaseStatus(): string
    {
        try {
            DB::connection()->getPdo();

            return 'متصل';
        } catch (\Throwable) {
            return 'خطا در اتصال';
        }
    }

    private function pendingJobs(): int
    {
        return $this->safeCount(fn () => DB::table(config('queue.connections.database.table', 'jobs'))->count());
    }

    private function failedJobs(): int
    {
        return $this->safeCount(fn () => DB::table('failed_jobs')->count());
    }

    private function safeCount(callable $callback): int
    {
        try {
            return (int) $callback();
        } catch (\Throwable) {
            // Table missing / not migrated: report 0 rather than exploding —
            // the report is the tool you reach for when the box is broken.
            return 0;
        }
    }

    private function safe(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (\Throwable) {
            return null;
        }
    }
}

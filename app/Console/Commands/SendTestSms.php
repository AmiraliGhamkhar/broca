<?php

namespace App\Console\Commands;

use App\Services\Sms\SmsDeliveryException;
use App\Services\Sms\SmsManager;
use Illuminate\Console\Command;

/**
 * `php artisan broca:sms:test {phone}`
 *
 * The fastest way to answer "is SMS wired up?": one real send through the
 * configured driver, with the panel's own answer printed.
 *
 * With the default `log` driver the message is written to laravel.log and this
 * command reports success — which is still a useful test, because it proves the
 * whole path (normalization → channel → driver) works and shows the exact text
 * a student would receive. Switching to a live panel is then a .env change.
 */
class SendTestSms extends Command
{
    protected $signature = 'broca:sms:test
                            {phone : Iranian mobile number, e.g. 09123456789}
                            {--message= : Override the body}
                            {--code=123456 : Code placeholder value, when the body uses :code}';

    protected $description = 'Send one test SMS through the configured driver and report what the panel answered';

    public function handle(SmsManager $sms): int
    {
        $phone = trim((string) $this->argument('phone'));
        $message = (string) ($this->option('message') ?: 'تست پیامک بروکا: پیکربندی پیامک با موفقیت انجام شد.');

        $this->line('درایور: '.config('sms.default').' | فعال: '.((bool) config('sms.enabled', true) ? 'بله' : 'خیر'));

        try {
            $result = $sms->send($phone, $message, ['code' => (string) $this->option('code')]);
        } catch (SmsDeliveryException $exception) {
            $this->error('ارسال ناموفق بود: '.$exception->getMessage());

            return self::FAILURE;
        }

        if (! $result->delivered) {
            $this->warn('پیام ارسال نشد: '.$result->detail);

            return self::FAILURE;
        }

        $this->info('پیام با موفقیت از مسیر ارسال عبور کرد.');
        $this->line('جزئیات: '.$result->detail);

        return self::SUCCESS;
    }
}

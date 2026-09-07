<?php

namespace App\Services\Sms;

use App\Contracts\SmsTransport;
use Illuminate\Support\Facades\Log;

/**
 * Development / pre-credentials driver: writes every message to the log and
 * reports success.
 *
 * This is the DEFAULT driver (config/sms.php). With it, a fresh checkout can
 * run the whole signup flow — including the OTP form — without a vendor
 * account, and CI can assert on the flow without dialling out. The log line
 * deliberately carries the code only in non-production: an operator's local
 * `tail -f` is the fastest way to develop the form, while a production
 * misconfiguration (SMS_DRIVER left at `log`) must not turn laravel.log into
 * a list of live verification codes.
 */
class LogSmsTransport implements SmsTransport
{
    public function send(string $phone, string $message, array $context = []): SmsResult
    {
        $reference = (string) ($context['reference'] ?? '');

        Log::channel(config('sms.drivers.log.channel', 'stack'))->info('SMS (log driver, not delivered)', [
            'phone' => $this->mask($phone),
            'message' => $message,
            'code' => app()->isProduction() ? '[redacted]' : ($context['code'] ?? null),
            'reference' => $reference,
        ]);

        return SmsResult::delivered('log', 'written to log channel', $reference === '' ? null : $reference);
    }

    private function mask(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) < 4) {
            return '***';
        }

        return substr($digits, 0, 4).str_repeat('*', max(0, strlen($digits) - 7)).substr($digits, -3);
    }
}

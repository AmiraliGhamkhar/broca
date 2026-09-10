<?php

namespace App\Services\Sms;

use App\Contracts\SmsTransport;
use App\Support\PhoneNormalizer;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * The one entry point the rest of the app uses to send a text message.
 *
 * Responsibilities kept here, out of the transports and out of the callers:
 *
 *  - the master switch (`sms.enabled`), so an operator can silence SMS during
 *    an incident without touching code and without breaking signup;
 *  - canonicalization: every number is normalized to the same `09…` form used
 *    for uniqueness and login, so a panel never receives two rows for the
 *    same handset;
 *  - one log line per attempt at a fixed level, masked, so the ops story is
 *    "grep the log", not "read the vendor's dashboard".
 */
class SmsManager
{
    /** @var array<string, SmsTransport> */
    private array $transports = [];

    public function __construct() {}

    /**
     * @param  array<string, mixed>  $context
     *
     * @throws SmsDeliveryException only for a transport-level failure
     */
    public function send(string $phone, string $message, array $context = []): SmsResult
    {
        $driver = (string) config('sms.default', 'log');

        if (! (bool) config('sms.enabled', true)) {
            Log::info('SMS skipped (sms.enabled is false)', ['driver' => $driver]);

            return SmsResult::failed('disabled', 'SMS is disabled by configuration');
        }

        try {
            $canonical = PhoneNormalizer::normalize($phone);
        } catch (InvalidArgumentException $exception) {
            // A number that is not an Iranian mobile cannot be billed to a
            // local panel. Refuse here with a clear reason instead of letting
            // the vendor reject it and bill a retry.
            Log::warning('SMS skipped (number is not a valid Iranian mobile)', [
                'driver' => $driver,
                'reason' => $exception->getMessage(),
            ]);

            return SmsResult::failed($driver, 'شمارهٔ همراه معتبر نیست.');
        }

        $context['reference'] ??= $this->reference();

        try {
            $result = $this->transport($driver)->send($canonical, $message, $context);
        } catch (SmsDeliveryException $exception) {
            Log::error('SMS delivery failed', [
                'driver' => $driver,
                'phone' => $this->mask($canonical),
                'reference' => $context['reference'],
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        Log::info($result->delivered ? 'SMS sent' : 'SMS not delivered', [
            'driver' => $result->driver,
            'phone' => $this->mask($canonical),
            'reference' => $context['reference'],
            'detail' => mb_substr($result->detail, 0, 300),
        ]);

        return $result;
    }

    /**
     * A driver that is not configured (or an unknown name) resolves to the
     * null driver rather than throwing: a half-configured .env must degrade to
     * "no text messages" instead of taking signup down with a 500.
     */
    public function transport(?string $driver = null): SmsTransport
    {
        $driver = $driver ?: (string) config('sms.default', 'log');

        return $this->transports[$driver] ??= match ($driver) {
            'http' => new HttpSmsTransport,
            'log' => new LogSmsTransport,
            'null' => new NullSmsTransport,
            default => new NullSmsTransport,
        };
    }

    /**
     * Non-throwing wrapper for paths where a failed text message must not
     * change the response (registration, verification resend). The failure is
     * still reported to the log and to the exception handler.
     */
    public function sendQuietly(string $phone, string $message, array $context = []): SmsResult
    {
        try {
            return $this->send($phone, $message, $context);
        } catch (SmsDeliveryException $exception) {
            report($exception);

            return SmsResult::failed((string) config('sms.default', 'log'), $exception->getMessage());
        }
    }

    private function reference(): string
    {
        return 'sms-'.now()->format('YmdHis').'-'.strtolower(bin2hex(random_bytes(3)));
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

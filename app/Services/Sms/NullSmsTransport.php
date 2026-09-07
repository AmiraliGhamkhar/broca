<?php

namespace App\Services\Sms;

use App\Contracts\SmsTransport;

/**
 * Discards every message. For CI and for staging clones that hold production
 * data: the code path runs, nothing leaves the building, and no operator can
 * accidentally text a real student from a test run.
 */
class NullSmsTransport implements SmsTransport
{
    public function send(string $phone, string $message, array $context = []): SmsResult
    {
        return SmsResult::failed('null', 'SMS delivery is disabled (null driver)');
    }
}

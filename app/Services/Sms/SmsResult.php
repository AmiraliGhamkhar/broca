<?php

namespace App\Services\Sms;

/**
 * The outcome of one outbound text message.
 *
 * A value object rather than a bool because every caller needs the same two
 * things afterwards: what to tell the user (Persian, short) and what to write
 * in the log (driver name + panel response). Packing both into one return
 * type keeps `SmsManager` from leaking transport details into the auth flow.
 */
final class SmsResult
{
    private function __construct(
        public readonly bool $delivered,
        public readonly string $driver,
        public readonly string $detail,
        public readonly ?string $reference = null,
    ) {}

    public static function delivered(string $driver, string $detail = '', ?string $reference = null): self
    {
        return new self(true, $driver, $detail, $reference);
    }

    public static function failed(string $driver, string $detail, ?string $reference = null): self
    {
        return new self(false, $driver, $detail, $reference);
    }
}

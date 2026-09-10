<?php

namespace App\Contracts;

use App\Services\Sms\SmsDeliveryException;
use App\Services\Sms\SmsResult;

/**
 * One outbound text message.
 *
 * Implementations own everything vendor-specific (URL, auth, field names);
 * the manager owns everything app-specific (phone canonicalization, the
 * enabled switch, logging). Adding a panel is therefore one class plus one
 * entry in config/sms.php — no call site changes.
 *
 * @throws SmsDeliveryException when the transport was reachable but refused
 *                              or failed the message.
 */
interface SmsTransport
{
    /**
     * @param  array<string, mixed>  $context  Optional extras: `code` (the OTP,
     *                                         when the message carries one),
     *                                         `reference`, `sender`.
     */
    public function send(string $phone, string $message, array $context = []): SmsResult;
}

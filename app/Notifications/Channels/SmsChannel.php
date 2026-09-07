<?php

namespace App\Notifications\Channels;

use App\Notifications\Messages\SmsMessage;
use App\Services\Sms\SmsDeliveryException;
use App\Services\Sms\SmsManager;
use Illuminate\Notifications\Notification;

/**
 * Notification channel: `via()` = ['sms'].
 *
 * Deliberately FAIL-SOFT, and that is the whole point. A verification code is
 * sent inside the request that creates the account (transactional mail and
 * text messages are delivered inline — see config/broca.php
 * `notifications.queue`), so a panel outage would otherwise turn every signup
 * into a 500. Instead the error is reported, the account is created, and the
 * user gets the resend button on the verification screen.
 */
class SmsChannel
{
    public function __construct(private readonly SmsManager $sms)
    {
    }

    public function send(object $notifiable, Notification $notification): mixed
    {
        if (! method_exists($notification, 'toSms')) {
            return null;
        }

        $message = $notification->toSms($notifiable);

        if (! $message instanceof SmsMessage || $message->isEmpty()) {
            return null;
        }

        $to = method_exists($notifiable, 'routeNotificationFor')
            ? $notifiable->routeNotificationFor('sms', $notification)
            : null;

        if (! is_string($to) || trim($to) === '') {
            // No deliverable number — a user row without a phone is a normal
            // state for accounts created by an import, not an error to raise.
            return null;
        }

        try {
            return $this->sms->send($to, $message->text, ['code' => $message->code]);
        } catch (SmsDeliveryException $exception) {
            report($exception);

            return null;
        }
    }
}

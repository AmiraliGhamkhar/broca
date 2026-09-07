<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\Messages\SmsMessage;
use App\Support\PersianNumber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * The signup one-time code, sent to the mobile number on the account.
 *
 * Queued (so a slow panel cannot hold the request) but pushed to the
 * connection in `broca.notifications.queue` — `sync` by default, because a
 * code the user is waiting for must not depend on a queue worker that may not
 * be running on shared hosting (see config/broca.php).
 */
class PhoneVerificationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $code)
    {
        $this->connection = (string) config('broca.notifications.queue', 'sync');
    }

    /**
     * The channel is named by CLASS (the documented custom-channel form), so
     * it resolves without depending on how the notification manager maps
     * short driver names to classes. `sms` is registered as an alias in
     * AppServiceProvider too, for anyone who writes `via() == ['sms']`.
     */
    public function via(object $notifiable): array
    {
        return [SmsChannel::class];
    }

    public function toSms(User $notifiable): SmsMessage
    {
        $minutes = PersianNumber::digits((int) config('broca.phone_verification.ttl_minutes', 10));

        return (new SmsMessage)
            ->text(implode("\n", [
                'کد تأیید بروکا: '.PersianNumber::digits($this->code),
                "این کد تا {$minutes} دقیقه معتبر است.",
                'اگر شما این درخواست را نداده‌اید، این پیام را نادیده بگیرید.',
            ]))
            ->code($this->code);
    }
}

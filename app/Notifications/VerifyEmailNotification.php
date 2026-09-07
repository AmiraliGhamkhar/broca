<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Queued email verification (the brief mandates queueing slow operations —
 * signup must never block on SMTP). Mirrors the framework's built-in
 * VerifyEmail URL contract.
 */
class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        /*
         * Delivered on `broca.notifications.queue`, which is `sync` by
         * default. This notification is the ONLY way most users have ever had
         * to activate an account, and on shared hosting the cron-driven queue
         * worker is frequently missing or dead — every signup then ended with
         * an account nobody could log into. Sending inline costs a few hundred
         * milliseconds of SMTP once per signup and removes that dependency
         * entirely; a host with a monitored worker sets
         * BROCA_NOTIFICATIONS_QUEUE=database to move it off the request.
         */
        $this->connection = (string) config('broca.notifications.queue', 'sync');
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('تأیید ایمیل — بروکا')
            ->greeting('سلام!')
            ->line('برای فعال‌سازی حساب کاربری خود، روی دکمهٔ زیر بزنید.')
            ->action('تأیید ایمیل', $verificationUrl)
            ->line('اگر شما این درخواست را نداده‌اید، می‌توانید این ایمیل را نادیده بگیرید.');
    }

    protected function verificationUrl(User $notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );
    }
}

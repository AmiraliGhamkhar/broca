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

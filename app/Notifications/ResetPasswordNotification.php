<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Queued password-reset email with the same token contract as the framework
 * default (route 'password.reset'), so the broker flow is unchanged.
 */
class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $token)
    {
        // Same reasoning as VerifyEmailNotification: a password reset is
        // useless if the mail waits for a queue worker that never runs.
        $this->connection = (string) config('broca.notifications.queue', 'sync');
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $resetUrl = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForVerification(),
        ]);

        $expireMinutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject('بازیابی گذرواژه — بروکا')
            ->greeting('سلام!')
            ->line('این ایمیل برای تغییر گذرواژهٔ حساب شما ارسال شده است.')
            ->action('تعیین گذرواژهٔ جدید', $resetUrl)
            ->line("این لینک تا {$expireMinutes} دقیقه معتبر است.")
            ->line('اگر شما درخواست تغییر گذرواژه نداده‌اید، نیازی به اقدام نیست.');
    }
}

<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\PhoneVerificationNotification;
use App\Services\Sms\SmsManager;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Mobile verification by SMS one-time code.
 *
 * WHY THIS EXISTS: the signup funnel used to have exactly one way in — the
 * emailed verification link. Every failure mode of that one path (queue not
 * drained, SMTP blocked by the host, a provider that silently drops mail, a
 * spam folder the student never opens) produced the same symptom: the account
 * exists, the user cannot use it, and support cannot tell why. Sending a code
 * to the number the user already gave us is a second, independent path to an
 * activated account — and it is the one that works when mail does not.
 *
 * Threat model, because a 6-digit code is small:
 *  - the code is stored bcrypt-hashed (never plaintext) and cleared the
 *    moment it is used, expires, or burns its attempt budget;
 *  - attempts are counted per user, so guessing is bounded by
 *    `broca.phone_verification.max_attempts`, not by the attacker's bandwidth;
 *  - resends are rate-limited here (per user) *and* by the route limiter,
 *    because every resend costs money at the panel.
 */
class PhoneVerificationService
{
    public function __construct(private readonly SmsManager $sms) {}

    public function enabled(): bool
    {
        return (bool) config('broca.phone_verification.enabled', true);
    }

    /**
     * Seconds the user must wait before a new code may be issued.
     */
    public function secondsUntilResend(User $user): int
    {
        $lastSentAt = $user->phone_verification_last_sent_at;

        if (! $lastSentAt) {
            return 0;
        }

        $cooldown = (int) config('broca.phone_verification.resend_cooldown_seconds', 60);

        return max(0, $cooldown - (int) $lastSentAt->diffInSeconds(now(), false));
    }

    public function canResend(User $user): bool
    {
        return $this->enabled()
            && ! $user->hasVerifiedPhone()
            && $this->secondsUntilResend($user) === 0;
    }

    /**
     * Issue a fresh code and send it. Returns false (without throwing) when
     * verification is off, the account has no usable number, or the resend
     * cooldown has not elapsed — registration must still succeed then.
     */
    public function sendCode(User $user, bool $ignoreCooldown = false): bool
    {
        if (! $this->enabled() || $user->hasVerifiedPhone()) {
            return false;
        }

        $phone = $user->routeNotificationForSms();
        if (! is_string($phone) || $phone === '') {
            return false;
        }

        if (! $ignoreCooldown && $this->secondsUntilResend($user) > 0) {
            return false;
        }

        $code = $this->freshCode();

        $user->forceFill([
            // bcrypt, not a fast hash: the code is a short numeric secret and
            // a database dump must not be replayable without cracking cost.
            'phone_verification_code' => Hash::make($code),
            'phone_verification_expires_at' => now()->addMinutes((int) config('broca.phone_verification.ttl_minutes', 10)),
            'phone_verification_attempts' => 0,
            'phone_verification_last_sent_at' => now(),
        ])->save();

        // Notification carries the *plaintext* code exactly once, to the
        // number on file; it is never persisted anywhere else.
        $user->notify(new PhoneVerificationNotification($code));

        return true;
    }

    /**
     * @return bool true when the code was accepted (a real failure is always
     *              false — expiry, exhausted attempts and a wrong code are
     *              indistinguishable to the caller, and to the user).
     */
    public function verify(User $user, string $code): bool
    {
        $stored = $user->phone_verification_code;
        $expiresAt = $user->phone_verification_expires_at;

        if (! is_string($stored) || $stored === '') {
            return false;
        }

        if ($expiresAt === null || $expiresAt->isPast()) {
            $this->clear($user);

            return false;
        }

        if ((int) $user->phone_verification_attempts >= (int) config('broca.phone_verification.max_attempts', 5)) {
            $this->clear($user);

            return false;
        }

        // Digits only, and Persian/Arabic digits are accepted: the code is
        // shown in Persian numerals, and iOS keyboards on a Persian-language
        // device type Persian numerals by default.
        $normalized = $this->digits($code);

        if ($normalized === '' || ! Hash::check($normalized, $stored)) {
            $user->forceFill([
                'phone_verification_attempts' => (int) $user->phone_verification_attempts + 1,
            ])->save();

            return false;
        }

        $user->forceFill([
            'phone_verified_at' => now(),
            'phone_verification_code' => null,
            'phone_verification_expires_at' => null,
            'phone_verification_attempts' => 0,
        ])->save();

        return true;
    }

    public function clear(User $user): void
    {
        $user->forceFill([
            'phone_verification_code' => null,
            'phone_verification_expires_at' => null,
            'phone_verification_attempts' => 0,
        ])->save();
    }

    private function freshCode(): string
    {
        $length = max(4, min(8, (int) config('broca.phone_verification.code_length', 6)));
        $max = 10 ** $length;

        return Str::padLeft((string) random_int(0, $max - 1), $length, '0');
    }

    /**
     * Strip everything that is not a digit and fold Persian/Arabic numerals.
     */
    private function digits(string $value): string
    {
        $ascii = strtr(trim($value), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        return preg_replace('/\D+/', '', $ascii) ?? '';
    }
}

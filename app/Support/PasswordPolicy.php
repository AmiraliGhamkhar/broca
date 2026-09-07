<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * The single source of truth for what a usable password looks like, shared by
 * registration and password reset (and bound as `Password::defaults()` in
 * AppServiceProvider, so anything that validates "a new password" gets the same
 * rule without re-declaring it).
 *
 * The length cap is deliberate, not a formality: Laravel hashes with bcrypt,
 * which silently truncates at 72 bytes. Without a cap, a 90-character password
 * would be stored — and would then authenticate for anyone who types its first
 * 72 bytes. Rejecting over-long input turns that silent truncation into an
 * honest validation error, while keeping the cap generous enough that no real
 * password manager output is refused.
 *
 * The HaveIBeenPwned breach check is opt-in (`broca.password_leak_check`)
 * because it is the only part of registration that reaches out to a third-party
 * host: on a restricted or slow network that call either stalls the request or
 * rejects valid passwords, which is a worse failure mode than skipping it.
 */
final class PasswordPolicy
{
    /** bcrypt's input ceiling in bytes — anything longer is silently truncated. */
    public const BCRYPT_INPUT_LIMIT = 72;

    public static function min(): int
    {
        return max(8, (int) config('broca.password_min', 8));
    }

    public static function maxRule(): string
    {
        return 'max:'.min(self::BCRYPT_INPUT_LIMIT, self::min() * 4);
    }

    public static function rule(): Password
    {
        $rule = Password::min(self::min())
            ->letters()
            ->mixedCase()
            ->numbers();

        if (config('broca.password_leak_check')) {
            $rule = $rule->uncompromised();
        }

        return $rule;
    }

    /** Copy shown next to the field so the rule is never a surprise. */
    public static function hint(): string
    {
        $hint = 'حداقل '.PersianNumber::digits(self::min()).' کاراکتر، شامل یک حرف بزرگ، یک حرف کوچک و یک رقم.';

        if (config('broca.password_leak_check')) {
            $hint .= ' گذرواژه‌هایی که در فهرست نشت‌های عمومی هستند پذیرفته نمی‌شوند.';
        }

        return $hint;
    }
}

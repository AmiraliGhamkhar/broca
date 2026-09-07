<?php

namespace App\Support;

use App\Models\User;
use InvalidArgumentException;

/**
 * Finding an account by "email or mobile", tolerating the spellings that
 * predate (or bypassed) normalization.
 *
 * Shared by the login form and by the operator commands (`broca:user:diagnose`,
 * `broca:user:repair`) so that "the command says the account is fine but the
 * form says the password is wrong" can never happen: both go through exactly
 * one lookup.
 *
 * Why tolerance is needed at all: registration canonicalizes email (lowercased
 * and trimmed) and phone (`09…`) before writing them, but rows also arrive
 * from hand-run SQL during a migration, from imports, and from before those
 * normalizers existed. A byte-for-byte lookup simply cannot see
 * "Admin@Example.com " or "+98912…", and the person whose account that is gets
 * told their password is wrong — which is how an operator ends up locked out
 * of their own site holding the right credentials.
 */
final class UserLookup
{
    /**
     * @throws InvalidArgumentException when the identifier is neither a valid
     *                                  email nor a recognizable Iranian mobile
     */
    public static function find(string $identifier): ?User
    {
        $identifier = trim($identifier);

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return self::byEmail($identifier);
        }

        return self::byPhone($identifier);
    }

    public static function byEmail(string $email): ?User
    {
        $canonical = mb_strtolower(trim($email));

        // Fast path: an indexed equality on the canonical form.
        $exact = User::query()->where('email', $canonical)->first();

        if ($exact) {
            return $exact;
        }

        // Tolerance path: a row written with stray case or padding.
        return User::query()->whereRaw('LOWER(TRIM(email)) = ?', [$canonical])->first();
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function byPhone(string $phone): ?User
    {
        $canonical = PhoneNormalizer::normalize($phone);

        // Canonical first (indexed), then the historical spellings a row may
        // still hold: an international prefix, Persian digits, or the raw
        // input as typed.
        return User::query()->whereIn('phone', self::phoneVariants($canonical, $phone))->first();
    }

    /**
     * Every spelling of one number this app may have stored. Used for lookup
     * and by `broca:identifiers:normalize` to decide what to rewrite.
     *
     * @return list<string>
     */
    public static function phoneVariants(string $canonical, string $raw = ''): array
    {
        $variants = [
            $canonical,
            '+98'.substr($canonical, 1),
            '0098'.substr($canonical, 1),
            PersianNumber::digits($canonical),
        ];

        $trimmed = trim($raw);
        if ($trimmed !== '') {
            $variants[] = $trimmed;
        }

        return array_values(array_unique(array_filter($variants)));
    }
}

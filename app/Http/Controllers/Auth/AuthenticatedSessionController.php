<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\UserLookup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Login: email *or* Iranian mobile number + password.
 *
 * Two independent limits guard this path, because they answer two different
 * attacks:
 *  - `throttle:login` on the route (per IP) — credential *spraying*, i.e. one
 *    password tried against many accounts, which an identifier-keyed counter
 *    would never notice;
 *  - the counter below (identifier|IP) — repeated guessing at one account.
 *
 * LOOKUP IS CANONICAL, NOT EXACT — AND THAT IS A FIX, NOT A NICETY.
 *
 * Registration normalizes email (lowercased, trimmed) and phone (`09…`) before
 * writing them, but rows also arrive from imports, from SQL run by hand during
 * a migration, and from before those normalizers existed. An account stored as
 * "Admin@Example.com " or "+98912…" is invisible to a byte-for-byte lookup:
 * the password is right, the user exists, and the form answers «ایمیل/شمارهٔ
 * همراه یا گذرواژه درست نیست» forever — which is exactly how an operator ends
 * up locked out of their own site with correct credentials. The lookup
 * therefore matches the canonical form first (indexed) and then a small set of
 * known historical spellings; `broca:identifiers:normalize` rewrites those
 * rows for good.
 */
class AuthenticatedSessionController extends Controller
{
    private const MAX_ATTEMPTS_PER_IDENTIFIER = 5;

    private const DECAY_SECONDS = 60;

    /** One message for "no such account" and "wrong password": see below. */
    private const GENERIC_FAILURE = 'ایمیل/شمارهٔ همراه یا گذرواژه درست نیست.';

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:1000'],
        ]);

        $identifier = trim($validated['identifier']);
        $key = $this->throttleKey($identifier, $request);

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS_PER_IDENTIFIER)) {
            throw ValidationException::withMessages([
                'identifier' => 'تعداد تلاش‌ها زیاد است. '.
                    RateLimiter::availableIn($key).' ثانیه بعد دوباره امتحان کنید.',
            ]);
        }

        $user = $this->resolveUser($identifier);

        if (! $user) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            // Deliberately one message for "no such account" and "wrong
            // password": /login is a public form, and telling them apart is an
            // account-enumeration oracle for credential-stuffing tools.
            throw ValidationException::withMessages(['identifier' => self::GENERIC_FAILURE]);
        }

        if (! $user->isActive()) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            /*
             * Distinct message, and the distinction is safe: it is only ever
             * shown for credentials that already passed lookup — the visitor
             * knows the account exists, so nothing is leaked. What it buys is
             * the end of the worst support case this app has: an operator
             * (usually the only admin) typing a correct password into a
             * suspended or never-activated account and being told the
             * password is wrong.
             */
            throw ValidationException::withMessages([
                'identifier' => 'این حساب غیرفعال یا تعلیق شده است. '
                    .($user->hasVerifiedContact()
                        ? 'برای فعال‌سازی با پشتیبانی تماس بگیرید.'
                        : 'ابتدا ایمیل یا شمارهٔ همراه خود را تأیید کنید.'),
            ]);
        }

        $credentials = [
            $this->identifierField($identifier) => $user->{$this->identifierField($identifier)},
            'password' => $validated['password'],
            'status' => 'active',
        ];

        if (! auth()->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            // The account exists and is active, so the only thing left is the
            // password — log the why, because "the admin cannot log in" is
            // otherwise undebuggable without shell access. Never include the
            // submitted password; `password_hash_type` says whether the stored
            // value is even a hash this app can verify.
            logger()->warning('Login failed: password mismatch', [
                'user_id' => $user->getKey(),
                'identifier_kind' => $this->identifierField($identifier),
                'stored_hash_algo' => $this->hashAlgo((string) $user->password),
            ]);

            throw ValidationException::withMessages(['identifier' => self::GENERIC_FAILURE]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'با موفقیت از حساب خود خارج شدید.');
    }

    /**
     * Find the account behind an email or a mobile number.
     *
     * Delegates to App\Support\UserLookup so the operator commands diagnose
     * exactly what the form does — see the note on that class.
     *
     * @throws ValidationException when the input is neither an email nor a
     *                             phone — a 422, never a 500.
     */
    private function resolveUser(string $identifier): ?User
    {
        try {
            return UserLookup::find($identifier);
        } catch (InvalidArgumentException) {
            // "looks like a phone number at all" is the only split we make —
            // it says "this is not a mobile number", not "this account
            // exists", so it is not an enumeration oracle.
            $looksLikePhone = (bool) preg_match('/^[\s()+\-.\d۰-۹]*0?9[\s\d۰-۹-]{6,}$/u', $identifier);

            throw ValidationException::withMessages([
                'identifier' => $looksLikePhone
                    ? 'شمارهٔ همراه باید ۱۱ رقم و با ۰۹ شروع شود (مثلاً ۰۹۱۲۳۴۵۶۷۸۹).'
                    : 'ایمیل کامل (name@example.com) یا شمارهٔ همراه ایرانی وارد کنید.',
            ]);
        }
    }

    private function identifierField(string $identifier): string
    {
        return filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
    }

    /**
     * Which algorithm the stored password actually is — the whole answer to
     * "why can this account never log in". A value that is not bcrypt/argon
     * (md5, sha1, a plaintext import) can never pass Hash::check(), and no
     * amount of correct typing will fix it: the password must be reset.
     */
    private function hashAlgo(string $stored): string
    {
        if ($stored === '') {
            return 'empty';
        }

        if (preg_match('/^\$(2[axyb]|argon2(id|i)?)\$/', $stored, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/^[a-f0-9]{32}$/i', $stored) === 1) {
            return 'md5 (legacy, cannot be verified)';
        }

        if (preg_match('/^[a-f0-9]{40}$/i', $stored) === 1) {
            return 'sha1 (legacy, cannot be verified)';
        }

        return 'unknown/plaintext (cannot be verified)';
    }

    private function throttleKey(string $identifier, Request $request): string
    {
        return Str::transliterate(mb_strtolower($identifier).'|'.$request->ip());
    }
}

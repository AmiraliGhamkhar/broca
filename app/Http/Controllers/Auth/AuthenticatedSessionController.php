<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\PhoneNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Login: email *or* Iranian mobile number + password.
 *
 * Two independent limits guard this path, because they answer two different
 * attacks:
 *  - `throttle:login` on the route (per IP) — credential *spraying*, i.e. one
 *    password tried against many accounts, which an identifier-keyed counter
 *    would never notice;
 *  - the counter below (identifier|IP) — repeated guessing at one account.
 */
class AuthenticatedSessionController extends Controller
{
    private const MAX_ATTEMPTS_PER_IDENTIFIER = 5;

    private const DECAY_SECONDS = 60;

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

        // Registration normalizes both fields, so the lookup must normalize
        // identically or a correct credential would look wrong.
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $field = 'email';
            $lookup = mb_strtolower($identifier);
        } else {
            $field = 'phone';
            try {
                $lookup = PhoneNormalizer::normalize($identifier);
            } catch (\InvalidArgumentException) {
                // Validation error, never a 500. "looks like a phone number at
                // all" is the only split we make — it says "this is not a
                // mobile number", not "this account exists", so it is not an
                // enumeration oracle.
                $looksLikePhone = (bool) preg_match('/^[\s()+\-.\d۰-۹]*0?9[\s\d۰-۹-]{6,}$/u', $identifier);

                throw ValidationException::withMessages([
                    'identifier' => $looksLikePhone
                        ? 'شمارهٔ همراه باید ۱۱ رقم و با ۰۹ شروع شود (مثلاً ۰۹۱۲۳۴۵۶۷۸۹).'
                        : 'ایمیل کامل (name@example.com) یا شمارهٔ همراه ایرانی وارد کنید.',
                ]);
            }
        }

        // 'status' is an extra credential: the provider rejects a suspended
        // account before the password is ever compared, so suspension is not
        // only enforced at the middleware layer on already-live sessions.
        $credentials = [$field => $lookup, 'password' => $validated['password'], 'status' => 'active'];

        if (! auth()->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            // Deliberately one message for "no such account" and "wrong
            // password": /login is a public form, and telling them apart is an
            // account-enumeration oracle for credential-stuffing tools.
            throw ValidationException::withMessages([
                'identifier' => 'ایمیل/شمارهٔ همراه یا گذرواژه درست نیست.',
            ]);
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

    private function throttleKey(string $identifier, Request $request): string
    {
        return Str::transliterate(mb_strtolower($identifier).'|'.$request->ip());
    }
}

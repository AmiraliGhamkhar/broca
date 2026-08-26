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

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(['identifier' => ['required', 'string'], 'password' => ['required', 'string']]);
        $key = Str::transliterate(Str::lower($validated['identifier']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['identifier' => 'تعداد تلاش‌ها زیاد است. کمی بعد دوباره امتحان کنید.']);
        }

        $field = filter_var($validated['identifier'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if ($field === 'email') {
            $identifier = mb_strtolower($validated['identifier']);
        } else {
            try {
                $identifier = PhoneNormalizer::normalize($validated['identifier']);
            } catch (\InvalidArgumentException) {
                // Invalid phone-like identifier → validation error, never a 500.
                throw ValidationException::withMessages(['identifier' => 'ایمیل یا شمارهٔ همراه معتبر وارد کنید.']);
            }
        }

        $credentials = [$field => $identifier, 'password' => $validated['password'], 'status' => 'active'];

        if (! auth()->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['identifier' => 'ایمیل/شمارهٔ همراه یا گذرواژه درست نیست.']);
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

        return redirect()->route('home');
    }
}

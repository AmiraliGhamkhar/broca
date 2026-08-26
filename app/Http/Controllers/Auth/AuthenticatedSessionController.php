<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Support\PhoneNormalizer;

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
        $identifier = $field === 'email' ? mb_strtolower($validated['identifier']) : PhoneNormalizer::normalize($validated['identifier']);
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

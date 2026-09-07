<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterUserRequest;
use App\Models\User;
use App\Support\PasswordPolicy;
use App\Support\PhoneNormalizer;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PDOException;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register', [
            'passwordHint' => PasswordPolicy::hint(),
        ]);
    }

    public function store(RegisterUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $user = DB::transaction(function () use ($validated, $request): User {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => mb_strtolower($validated['email']),
                    'phone' => PhoneNormalizer::normalize($validated['phone']),
                    'password' => Hash::make($validated['password']),
                ]);

                $user->consents()->create([
                    'terms_version' => config('broca.terms_version'),
                    'privacy_version' => config('broca.privacy_version'),
                    'medical_disclaimer_version' => config('broca.medical_disclaimer_version'),
                    'accepted_at' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 1024),
                ]);

                return $user;
            });
        } catch (\Illuminate\Database\QueryException|PDOException $e) {
            // Two browsers (or a retry after a slow first response) can pass
            // the `unique` rule at the same instant; the DB unique index is the
            // thing that actually decides. Translate that into a form error —
            // a signup race must never surface as a 500.
            if (! $this->isUniqueViolation($e)) {
                throw $e;
            }

            $field = $this->uniqueViolationField($e);

            throw ValidationException::withMessages([
                $field => $field === 'phone'
                    ? 'این شمارهٔ همراه همین حالا ثبت شد. با همان حساب وارد شوید.'
                    : 'این ایمیل همین حالا ثبت شد. با همان حساب وارد شوید.',
            ]);
        }

        // Verification email is queued (see VerifyEmailNotification).
        event(new Registered($user));
        auth()->login($user);

        // Regenerate the session id now that the request carries real
        // credentials: the pre-auth session must not become the authed one.
        $request->session()->regenerate();

        return redirect()->route('verification.notice')->with('status',
            'حساب شما ساخته شد. برای فعال‌سازی کامل، لینک تأیید ارسال‌شده به ایمیل خود را باز کنید.');
    }

    private function isUniqueViolation(\Illuminate\Database\QueryException|PDOException $e): bool
    {
        // 23001 = SQLSTATE for integrity constraint violation, 23505 = the
        // PostgreSQL duplicate-key code; MySQL reports 1062 in errorInfo[1].
        return str_contains($e->getMessage(), 'Duplicate entry')
            || str_contains($e->getMessage(), 'UNIQUE constraint failed')
            || in_array((string) $e->getCode(), ['23000', '23001', '23505'], true);
    }

    private function uniqueViolationField(\Illuminate\Database\QueryException|PDOException $e): string
    {
        return str_contains($e->getMessage(), 'phone') ? 'phone' : 'email';
    }
}

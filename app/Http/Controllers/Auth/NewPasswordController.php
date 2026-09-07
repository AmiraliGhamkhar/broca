<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PasswordPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function create(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', old('email')),
            'passwordHint' => PasswordPolicy::hint(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'confirmed', PasswordPolicy::maxRule(), PasswordPolicy::rule()],
        ]);

        // The broker matches the address exactly; normalize it like every
        // other credential path so a reset link typed by hand still works.
        $validated['email'] = preg_match('//u', $validated['email']) === 1
            ? mb_strtolower(trim($validated['email']))
            : trim($validated['email']);

        $status = Password::reset(
            $validated,
            function (User $user, string $password): void {
                // The 'hashed' cast on User hashes the plain value on assign.
                $user->forceFill(['password' => $password])->save();

                // A password reset assumes the account was compromised —
                // every live session of that user must die, not just the one
                // doing the reset. Best-effort delete of the user's rows from
                // the sessions table (driver-independent: no-op if the table
                // doesn't exist, e.g. redis/file drivers).
                try {
                    DB::table(config('session.table', 'sessions'))
                        ->where('user_id', $user->getKey())
                        ->delete();
                } catch (\Throwable) {
                    // Non-database session driver: remember-token rotation
                    // below still invalidates "remember me" sessions.
                }

                $user->forceFill(['remember_token' => null])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'گذرواژه با موفقیت تغییر کرد؛ اکنون وارد شوید.')
            : back()->withInput($request->only('email'))->withErrors(['email' => 'لینک بازیابی نامعتبر یا منقضی شده است.']);
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function create(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', old('email')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()->uncompromised()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                // The 'hashed' cast on User hashes the plain value on assign.
                $user->forceFill(['password' => $password])->save();

                // A password reset assumes the account was compromised —
                // every live session of that user must die, not just the one
                // doing the reset. Sessions live in the database on this
                // stack, so remove the user's rows directly.
                if (config('session.driver') === 'database') {
                    DB::table(config('session.table', 'sessions'))
                        ->where('user_id', $user->getKey())
                        ->delete();
                }
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'گذرواژه با موفقیت تغییر کرد؛ اکنون وارد شوید.')
            : back()->withInput($request->only('email'))->withErrors(['email' => 'لینک بازیابی نامعتبر یا منقضی شده است.']);
    }
}

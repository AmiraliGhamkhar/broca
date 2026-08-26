<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Validation\Rules\Password;
use App\Support\PhoneNormalizer;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'consent' => ['accepted'],
        ], [
            'consent.accepted' => 'برای ساخت حساب، پذیرش شرایط و بیانیهٔ پزشکی لازم است.',
        ]);

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
                'user_agent' => $request->userAgent(),
            ]);

            return $user;
        });

        event(new Registered($user));
        auth()->login($user);

        return redirect()->route('verification.notice');
    }
}

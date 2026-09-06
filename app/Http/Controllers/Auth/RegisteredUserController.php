<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterUserRequest;
use App\Models\User;
use App\Support\PhoneNormalizer;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

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

        // Verification email is queued (see VerifyEmailNotification).
        event(new Registered($user));
        auth()->login($user);

        // Regenerate the session id now that the request carries real
        // credentials: the pre-auth session must not become the authed one.
        $request->session()->regenerate();

        return redirect()->route('verification.notice');
    }
}

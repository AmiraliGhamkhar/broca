<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
        ]);

        // Same normalization as registration and login: the broker looks the
        // address up byte-for-byte, so a capitalized or space-padded input
        // would silently "find no account" for a real user.
        $email = mb_strtolower(trim($validated['email']));

        $status = Password::sendResetLink(['email' => $email]);

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'لینک بازیابی گذرواژه به ایمیل شما ارسال شد.');
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()->with('status', 'لینک بازیابی پیش‌تر ارسال شده؛ کمی صبر کنید و ایمیل خود را بررسی کنید.');
        }

        // No user enumeration on a public form: the answer is the same whether
        // or not the address belongs to an account.
        return back()->withInput($request->only('email'))
            ->with('status', 'اگر این ایمیل در سامانه ثبت شده باشد، لینک بازیابی ارسال می‌شود.');
    }
}

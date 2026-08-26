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
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'لینک بازیابی گذرواژه به ایمیل شما ارسال شد.');
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()->with('status', 'لینک بازیابی پیش‌تر ارسال شده؛ کمی صبر کنید و ایمیل خود را بررسی کنید.');
        }

        // Do not reveal whether the address exists.
        return back()->with('status', 'اگر این ایمیل در سامانه ثبت شده باشد، لینک بازیابی ارسال می‌شود.');
    }
}

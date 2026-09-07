<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PhoneVerificationService;
use App\Support\PersianNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Mobile verification: send the one-time code, accept the one-time code.
 *
 * This is the second, independent way to activate an account (the first is the
 * emailed link). It exists because mail is the least reliable dependency on
 * the host this app is deployed to, and a signup funnel with one path in means
 * one outage blocks every new student — including the admin.
 */
class PhoneVerificationController extends Controller
{
    public function __construct(private readonly PhoneVerificationService $verification)
    {
    }

    /**
     * (Re)send the code to the number on file.
     *
     * Two bounds, for two different costs. the route limiter
     * (`verification-resend`, 3/min) stops one impatience from becoming a
     * mail/SMS bomb; the per-user cooldown inside the service stops a text
     * message — which costs real money per send — from being re-issued on
     * every page refresh.
     */
    public function send(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedPhone()) {
            return back()->with('status', 'شمارهٔ همراه این حساب پیش‌تر تأیید شده است.');
        }

        $wait = $this->verification->secondsUntilResend($user);
        if ($wait > 0) {
            return back()->withErrors([
                'phone_code' => 'کد همین حالا ارسال شده است. '.PersianNumber::digits($wait).' ثانیه دیگر دوباره تلاش کنید.',
            ]);
        }

        $sent = $this->verification->sendCode($user);

        // A false return is a *normal* state (SMS disabled, no usable number,
        // cooldown), never a 500: the email path is still open, and the notice
        // page says so. Operators see the reason in the log.
        return back()->with($sent ? 'status' : 'error', $sent
            ? 'کد تأیید به شمارهٔ '.$this->maskedPhone((string) $user->phone).' ارسال شد.'
            : 'ارسال پیامک در حال حاضر ممکن نیست. لینک تأیید ایمیل را بررسی کنید یا با پشتیبانی تماس بگیرید.');
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20'],
        ], [
            'code.required' => 'کد تأیید ارسال‌شده به موبایل را وارد کنید.',
        ]);

        $user = $request->user();

        if ($user->hasVerifiedPhone()) {
            return redirect()->intended(route('dashboard'))->with('status', 'شمارهٔ همراه این حساب پیش‌تر تأیید شده بود.');
        }

        if (! $this->verification->enabled()) {
            throw ValidationException::withMessages([
                'code' => 'تأیید با پیامک غیرفعال است؛ از لینک ایمیل استفاده کنید.',
            ]);
        }

        if (! $this->verification->verify($user, (string) $validated['code'])) {
            // One message for "wrong", "expired" and "too many attempts":
            // telling them apart teaches an attacker which codes are live.
            throw ValidationException::withMessages([
                'code' => 'کد واردشده درست نیست یا منقضی شده است. می‌توانید کد جدیدی درخواست کنید.',
            ]);
        }

        // intended() (not route()) because EnsureVerifiedContact used
        // Redirect::guest(): the student lands back on what they asked for
        // before the gate interrupted them.
        return redirect()->intended(route('dashboard'))->with('status',
            'شمارهٔ همراه تأیید شد؛ اکنون می‌توانید از همهٔ امکانات حساب استفاده کنید.');
    }

    /**
     * 0912*****89 — enough for the user to recognise their own number, not
     * enough for a screenshot taken over someone's shoulder to be useful.
     */
    private function maskedPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) < 5) {
            return '***';
        }

        return substr($digits, 0, 4).str_repeat('*', strlen($digits) - 6).substr($digits, -2);
    }
}

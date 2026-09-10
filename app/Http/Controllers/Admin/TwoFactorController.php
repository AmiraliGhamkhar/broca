<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RequireAdminTwoFactor;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Admin TOTP two-factor: enroll (manual key entry — no QR dependency yet),
 * challenge on every login, single-use recovery codes.
 */
class TwoFactorController extends Controller
{
    /** Session key holding the last accepted TOTP code (replay guard). */
    private const REPLAY_KEY = 'admin.2fa.last_code';

    public function challenge(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user?->is_admin || ! $user->hasConfirmedTwoFactor()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.two-factor.challenge');
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ], ['code.digits' => 'کد تأیید باید ۶ رقم باشد.']);

        $user = $request->user();

        // Identity is re-checked here rather than trusted from the route group,
        // and it is checked BEFORE the code: an admin who has lost 2FA must not
        // be able to use this endpoint as an oracle.
        if (! $user?->is_admin || ! $user->hasConfirmedTwoFactor()) {
            return redirect()->route('admin.two-factor.edit');
        }

        if ($this->alreadyUsed($request, $validated['code'])
            || ! Totp::verify((string) $user->totp_secret, $validated['code'])) {
            RateLimiter::hit($this->throttleKey($request), 60);

            return back()->withErrors(['code' => 'کد تأیید درست نیست.']);
        }

        RateLimiter::clear($this->throttleKey($request));
        $request->session()->put(self::REPLAY_KEY, trim($validated['code']));
        $request->session()->put(RequireAdminTwoFactor::SESSION_KEY, now()->timestamp);

        // Second factor passed → fresh session id, so a session issued before
        // 2FA cannot be the one that carries admin access.
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function recover(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recovery_code' => ['required', 'string', 'max:64'],
        ]);

        $user = $request->user();

        // Recovery codes are long, single-use and bcrypt-compared, so the route
        // limiter alone bounds them; no per-attempt bookkeeping here.
        if (! $user?->is_admin || ! $user->hasConfirmedTwoFactor() || ! $user->consumeRecoveryCode($validated['recovery_code'])) {
            return back()->withErrors(['recovery_code' => 'کد بازیابی درست نیست یا پیش‌تر استفاده شده است.']);
        }

        $request->session()->put(RequireAdminTwoFactor::SESSION_KEY, now()->timestamp);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')->with('status', 'با کد بازیابی وارد شدید؛ در صورت نیاز کلید بازیابی جدیدی بسازید.');
    }

    public function edit(Request $request): View
    {
        return view('admin.two-factor.edit', [
            'user' => $request->user(),
            'otpauthUri' => $request->user()->totp_secret
                ? Totp::uri($request->user()->totp_secret, $request->user()->email)
                : null,
        ]);
    }

    public function start(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasConfirmedTwoFactor()) {
            return redirect()->route('admin.two-factor.edit');
        }

        $user->forceFill(['totp_secret' => Totp::generateSecret(), 'totp_confirmed_at' => null])->save();

        return redirect()->route('admin.two-factor.edit');
    }

    public function enable(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ], ['code.digits' => 'کد تأیید باید ۶ رقم باشد.']);

        $user = $request->user();

        // Admission is decided by `throttle:admin-2fa-verify` on the route; this
        // path deliberately does not touch that bucket. A wrong code here is
        // already counted by the middleware, and clearing on success would let a
        // scripted guesser reset its own budget by guessing correctly.
        if (! $user->totp_secret || $user->hasConfirmedTwoFactor() || ! Totp::verify((string) $user->totp_secret, $validated['code'])) {
            return back()->withErrors(['code' => 'کد تأیید درست نیست؛ دوباره تلاش کنید.']);
        }

        $recoveryCodes = $this->freshRecoveryCodes();

        $user->forceFill(['totp_confirmed_at' => now()])->save();
        $user->storeRecoveryCodes($recoveryCodes);

        return redirect()->route('admin.two-factor.edit')
            ->with('recovery_codes', $recoveryCodes)
            ->with('status', 'ورود دومرحله‌ای فعال شد. کدهای بازیابی را همین حالا در جای امنی ذخیره کنید — فقط یک بار نمایش داده می‌شوند.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ], ['code.digits' => 'کد تأیید باید ۶ رقم باشد.']);

        $user = $request->user();

        // Same shape as enable: the route limiter counts the attempts, the
        // controller only refuses. Disabling 2FA is the most valuable code to
        // guess, which is exactly why it shares the challenge's budget.
        if (! $user->hasConfirmedTwoFactor() || ! Totp::verify((string) $user->totp_secret, $validated['code'])) {
            return back()->withErrors(['code' => 'کد تأیید درست نیست؛ غیرفعال‌سازی انجام نشد.']);
        }

        $user->forceFill([
            'totp_secret' => null,
            'totp_confirmed_at' => null,
            'recovery_codes' => null,
        ])->save();

        $request->session()->forget(RequireAdminTwoFactor::SESSION_KEY);

        return redirect()->route('admin.two-factor.edit')->with('status', 'ورود دومرحله‌ای غیرفعال شد.');
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasConfirmedTwoFactor()) {
            return redirect()->route('admin.two-factor.edit');
        }

        $recoveryCodes = $this->freshRecoveryCodes();

        $user->storeRecoveryCodes($recoveryCodes);

        return redirect()->route('admin.two-factor.edit')
            ->with('recovery_codes', $recoveryCodes)
            ->with('status', 'کدهای بازیابی جدید ساخته شدند. کدهای پیشین دیگر کار نمی‌کنند — این کدها را فقط یک بار می‌بینید.');
    }

    /**
     * A TOTP code stays valid for the whole drift window (~90 s), so "verify
     * once, accept forever within the window" would let anyone who briefly
     * read the code (shoulder-surf, log, screen share) replay it. Remembering
     * the last accepted code per session kills that without touching the
     * legitimate flow: a second factor is entered once per session anyway.
     */
    private function alreadyUsed(Request $request, string $code): bool
    {
        $normalized = preg_replace('/\D/', '', $code) ?? '';

        if ($request->session()->get(self::REPLAY_KEY) === $normalized) {
            return true;
        }

        $request->session()->put(self::REPLAY_KEY, $normalized);

        return false;
    }

    private function throttleKey(Request $request): string
    {
        // The sign-in challenge's own counter — separate from the route
        // limiter's bucket on purpose (see throttleKey's callers): it exists to
        // say "wait N seconds" about *this* admin's guessing and to be cleared
        // by a successful attempt, which is what keeps honest typos harmless.
        return '2fa:'.($request->user()?->id ?? $request->ip());
    }

    /**
     * @return list<string>
     */
    private function freshRecoveryCodes(int $count = 10): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(Str::random(5).'-'.Str::random(5));
        }

        return $codes;
    }
}

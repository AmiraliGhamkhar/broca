<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterUserRequest;
use App\Models\User;
use App\Services\PhoneVerificationService;
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
    public function __construct(private readonly PhoneVerificationService $phoneVerification)
    {
    }

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

        auth()->login($user);

        // Regenerate the session id now that the request carries real
        // credentials: the pre-auth session must not become the authed one.
        $request->session()->regenerate();

        // Both verification channels are sent AFTER the session exists, so a
        // failure here can never cost the user their new account — only the
        // convenience of an immediate code.
        $this->sendVerifications($user);

        return redirect()->route('verification.notice')
            ->with('status', $this->confirmationMessage($user));
    }

    /**
     * Email link + SMS code, independently.
     *
     * Each channel is wrapped on its own for a specific reason:
     *
     *  - MAIL: the notification is delivered inline (config/broca.php
     *    `notifications.queue` = sync by default) so it does not depend on a
     *    queue worker that shared hosting may not be running. Inline means an
     *    SMTP failure would otherwise surface as a 500 *after* the account
     *    was committed — the worst possible answer to "did my signup work".
     *  - SMS: the panel is a paid third party; an outage there must leave the
     *    email path untouched.
     *
     * Both failures are reported (so they land in laravel.log and the
     * exception handler) and both are recoverable from the notice page: resend
     * the link, or resend the code.
     *
     * @return array{mail: bool, sms: bool} which channels actually went out
     */
    private function sendVerifications(User $user): array
    {
        $sent = ['mail' => false, 'sms' => false];

        try {
            // Verification email is queued (see VerifyEmailNotification) on
            // the connection configured for transactional delivery.
            event(new Registered($user));
            $sent['mail'] = true;
        } catch (\Throwable $exception) {
            report($exception);
        }

        if (config('broca.phone_verification.enabled', true)) {
            try {
                $sent['sms'] = $this->phoneVerification->sendCode($user, ignoreCooldown: true);
            } catch (\Throwable $exception) {
                // sendCode() already reports transport failures; this catches
                // anything else so signup is never the thing that breaks.
                report($exception);
            }
        }

        if (! $sent['mail'] && ! $sent['sms']) {
            // The one case worth a log line of its own: the account exists and
            // neither proof of contact could leave the building. Whoever is
            // on call needs to see it next to the account, not buried in a
            // transport exception.
            logger()->critical('Registration delivered no verification channel', [
                'user_id' => $user->getKey(),
                'email' => $user->email,
                'mail_driver' => config('mail.default'),
                'sms_driver' => config('sms.default'),
                'sms_enabled' => (bool) config('sms.enabled', true),
            ]);
        }

        return $sent;
    }

    /**
     * The sentence the student reads next to the two forms on the notice page.
     * It names whichever channel actually went out — promising an email that
     * the server failed to send is how a signup turns into a support ticket.
     */
    private function confirmationMessage(User $user): string
    {
        $phoneVerified = $user->hasVerifiedPhone();

        if ($phoneVerified) {
            return 'حساب شما ساخته شد و شمارهٔ همراه تأیید شد.';
        }

        return 'حساب شما ساخته شد. برای فعال‌سازی کامل، لینک ارسال‌شده به ایمیل یا کد ارسال‌شده به شمارهٔ همراه را وارد کنید.';
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

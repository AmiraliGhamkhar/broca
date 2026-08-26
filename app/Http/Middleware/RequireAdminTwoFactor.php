<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin accounts with confirmed TOTP must pass a second factor each session
 * (valid 12 hours) before reaching any admin surface.
 */
class RequireAdminTwoFactor
{
    public const SESSION_KEY = 'admin.2fa.passed_at';

    private const MAX_AGE_MINUTES = 720; // 12 hours

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->is_admin && $user->hasConfirmedTwoFactor()) {
            $passedAt = $request->session()->get(self::SESSION_KEY);

            if (! $passedAt || (now()->timestamp - (int) $passedAt) > (self::MAX_AGE_MINUTES * 60)) {
                return redirect()->route('admin.two-factor.challenge');
            }
        }

        return $next($request);
    }
}

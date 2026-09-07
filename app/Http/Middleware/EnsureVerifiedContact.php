<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for everything behind "activated account": email OR mobile proven.
 *
 * The framework's own `verified` middleware accepts exactly one proof of
 * contact — the emailed link — which makes the signup funnel a single point of
 * failure. On the host this app ships to (shared cPanel), mail is the least
 * reliable thing in the stack: the queue worker may not be running, the SMTP
 * port may be blocked, the provider may silently drop the message, and a
 * student who never sees the link simply cannot use the account they just
 * paid for. Registration already collects a mobile number and now sends a
 * one-time code to it, so this middleware accepts either proof.
 *
 * Deliberately NOT an override of the framework's `verified` alias: routes
 * name this one explicitly (`verified.contact`) so the behaviour is greppable
 * and no future `alias()` merge order can silently change which middleware
 * guards /checkout.
 */
class EnsureVerifiedContact
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasVerifiedContact()) {
            return $request->expectsJson()
                ? abort(403, 'حساب شما هنوز تأیید نشده است.')
                // guest() (not a plain redirect) so the intended URL survives:
                // finishing verification returns the user where they were going
                // instead of dumping them on the dashboard.
                : Redirect::guest(route('verification.notice'));
        }

        return $next($request);
    }
}

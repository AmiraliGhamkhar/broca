<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trusted reverse proxies, resolved from config at REQUEST time.
 *
 * Why this class exists instead of `$middleware->trustProxies(...)` in
 * bootstrap/app.php:
 *
 *  - Reading `env('TRUSTED_PROXIES')` there is wrong: once
 *    `php artisan config:cache` has run (the cPanel deploy hook always runs
 *    it) Laravel stops loading the .env file, so env() returns null. Proxy
 *    trust silently vanishes in production — exactly where it is needed.
 *  - Reading `config(...)` there is also wrong: the withMiddleware closure is
 *    evaluated before the config repository is bound to the container, so it
 *    throws "Target class [config] does not exist".
 *
 * Resolving inside handle() sidesteps both: config is fully loaded (cached or
 * not) by the time a request is handled.
 *
 * This matters on Mizbanfa/cPanel because TLS terminates upstream. Without
 * proxy trust, $request->secure() is false forever, which sends
 * ForceSecureConnections into an infinite HTTPS redirect loop and prevents
 * the Secure session cookie from ever being set.
 */
class TrustProxies extends Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $configured = config('broca.trusted_proxies', []);

        if ($configured !== []) {
            // '*' (trust any proxy) must be the bare string, not an array.
            $this->proxies = $configured === ['*'] ? '*' : $configured;
        }

        return parent::handle($request, $next);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces HTTPS when the deployment opts in (APP_FORCE_HTTPS=true). Without
 * this, shared-hosting deployments that still answer on :80 will happily
 * exchange session cookies in cleartext.
 */
class ForceSecureConnections
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('broca.force_https') && ! $request->secure()) {
            return Redirect::secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }
}

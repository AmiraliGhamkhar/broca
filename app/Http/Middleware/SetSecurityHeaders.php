<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security headers on every response. Deliberately conservative:
 * a strict CSP (script-src without unsafe-inline) requires restructuring the
 * inline Alpine/JSON-LD blocks first — tracked in DECISIONS.md. This set
 * already blocks framing, object embedding, MIME sniffing and referrer leaks.
 */
class SetSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'");

        if ($request->secure()) {
            // Only send HSTS once we are actually serving HTTPS, otherwise
            // local HTTP development breaks with a cached strict header.
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}

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
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());

        if ($request->secure()) {
            // Only send HSTS once we are actually serving HTTPS, otherwise
            // local HTTP development breaks with a cached strict header.
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    /**
     * Same-origin policy everywhere. 'unsafe-inline' is required for the
     * inline Alpine boot code, JSON-LD blocks and inline style attributes —
     * the JSON-LD blocks escape their data (JSON_HEX_TAG), so inline scripts
     * stay inert even though the keyword is present.
     */
    private function contentSecurityPolicy(): string
    {
        $mediaOrigins = collect(config('broca.external_video_origins', []))
            ->filter(fn ($origin) => is_string($origin) && $origin !== '')
            ->map(fn (string $origin) => trim($origin))
            ->implode(' ');

        $mediaSrc = trim("'self' ".$mediaOrigins);

        $csp = "default-src 'self'; "
            ."script-src 'self' 'unsafe-inline'; "
            ."style-src 'self' 'unsafe-inline'; "
            ."img-src 'self' data:; "
            ."font-src 'self'; "
            ."connect-src 'self'; "
            ."media-src {$mediaSrc}; "
            ."frame-ancestors 'self'; "
            ."base-uri 'self'; "
            ."form-action 'self'; "
            ."object-src 'none'";

        // Vite dev server (only while `npm run dev` is active) serves the
        // client from its own origin — allow it in local development.
        if (app()->environment('local') && is_file(public_path('hot'))) {
            $csp .= "; connect-src 'self' http://localhost:5173 ws://localhost:5173 http://127.0.0.1:5173 ws://127.0.0.1:5173"
                ."; script-src 'self' 'unsafe-inline' http://localhost:5173 http://127.0.0.1:5173";
        }

        return $csp;
    }
}

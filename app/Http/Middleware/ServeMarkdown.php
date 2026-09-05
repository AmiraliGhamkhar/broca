<?php

namespace App\Http\Middleware;

use App\Support\MarkdownTwin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Accept: text/markdown content negotiation (RFC 9110 §12) for the public
 * pages that have a Markdown twin.
 *
 * A client that EXPLICITLY prefers text/markdown gets the clean Markdown
 * document with Vary: Accept and a Link header pointing back to the HTML
 * page. Browsers (Accept: text/html, or a bare wildcard) are never
 * affected — a wildcard means "no explicit preference", so we only switch
 * when the client named text/markdown with a winning q-value.
 *
 * This is NOT cloaking: same URL, same content, a different representation
 * the client asked for (the 25-year-old Accept: application/json precedent).
 */
class ServeMarkdown
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();

        if ($routeName && MarkdownTwin::hasTwin($routeName) && $this->prefersMarkdown($request->header('Accept'))) {
            $params = $request->route()->parameters() ?? [];
            $markdown = MarkdownTwin::markdownForRoute($routeName, $params);

            if ($markdown !== null) {
                return response($markdown, 200, [
                    'Content-Type' => 'text/markdown; charset=UTF-8',
                    'Vary' => 'Accept',
                    // The same URL we just served as Markdown — its HTML
                    // representation, for clients that want to follow along.
                    'Link' => '<'.$request->url().'>; rel="alternate"; type="text/html"',
                ]);
            }
        }

        return $next($request);
    }

    /**
     * True only when the Accept header EXPLICITLY lists text/markdown with
     * q > 0 and it ranks at least as high as text/html. A bare wildcard
     * (curl, wget, generic HTTP clients) never flips to Markdown.
     */
    private function prefersMarkdown(?string $accept): bool
    {
        if ($accept === null || trim($accept) === '') {
            return false;
        }

        $explicit = $this->parseAccept($accept, applyWildcard: false);
        $resolved = $this->parseAccept($accept, applyWildcard: true);

        $explicitMarkdown = $explicit['text/markdown'] ?? 0.0;
        $markdown = $resolved['text/markdown'] ?? 0.0;
        $html = $resolved['text/html'] ?? 0.0;

        return $explicitMarkdown > 0 && $markdown > 0 && $markdown >= $html;
    }

    /**
     * Parse an Accept header into type => highest q-value.
     *
     * @return array<string, float>
     */
    private function parseAccept(string $accept, bool $applyWildcard = true): array
    {
        $types = [];
        $wildcard = 1.0;
        $hasWildcard = false;

        foreach (explode(',', $accept) as $part) {
            $tokens = array_map('trim', explode(';', $part));
            $type = strtolower((string) array_shift($tokens));

            if ($type === '') {
                continue;
            }

            $q = 1.0;
            foreach ($tokens as $token) {
                if (stripos($token, 'q=') === 0) {
                    $candidate = (float) substr($token, 2);
                    if (is_numeric(trim(substr($token, 2))) && $candidate >= 0.0 && $candidate <= 1.0) {
                        $q = $candidate;
                    }
                }
            }

            if ($type === '*/*') {
                $wildcard = min($wildcard, $q);
                $hasWildcard = true;
            } elseif (! isset($types[$type]) || $q > $types[$type]) {
                $types[$type] = $q;
            }
        }

        // An unlisted type (e.g. text/html when the client only sent
        // "text/markdown") falls back to the wildcard's q.
        if ($applyWildcard) {
            foreach (['text/html', 'text/markdown'] as $candidate) {
                if (! isset($types[$candidate])) {
                    $types[$candidate] = $hasWildcard ? $wildcard : 0.0;
                }
            }
        }

        return $types;
    }
}

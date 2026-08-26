<?php

namespace App\Http\Middleware;

use App\Models\AdminActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Writes an audit row for every state-changing admin request. This is the
 * forensic trail the audit found missing: who published, re-priced,
 * designated or deleted what, from where.
 */
class LogAdminActivity
{
    /** Parameters that must never be persisted, even masked. */
    private const REDACTED_KEYS = ['password', 'password_confirmation', 'current_password', 'token', '_token', 'code', 'recovery_code'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldLog($request)) {
            try {
                AdminActivityLog::create([
                    'user_id' => $request->user()?->id,
                    'action' => $this->action($request),
                    'route_name' => $request->route()?->getName(),
                    'method' => $request->method(),
                    'url' => mb_substr($request->path(), 0, 500),
                    'ip_address' => $request->ip(),
                    'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
                    'payload' => $this->sanitize($request),
                    'status_code' => $response->getStatusCode(),
                ]);
            } catch (\Throwable $exception) {
                // Auditing must never take the admin panel down.
                report($exception);
            }
        }

        return $response;
    }

    private function shouldLog(Request $request): bool
    {
        if (! $request->user()?->is_admin) {
            return false;
        }

        return $request->isMethod('post')
            || $request->isMethod('put')
            || $request->isMethod('patch')
            || $request->isMethod('delete');
    }

    private function action(Request $request): string
    {
        $route = $request->route()?->getName() ?? $request->path();

        return mb_substr($request->method().' '.$route, 0, 255);
    }

    /**
     * @return array<string, mixed>
     */
    private function sanitize(Request $request): array
    {
        return collect($request->input())
            ->except(self::REDACTED_KEYS)
            ->map(fn ($value) => is_scalar($value) ? mb_substr((string) $value, 0, 500) : $value)
            ->all();
    }
}

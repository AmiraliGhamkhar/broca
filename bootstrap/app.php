<?php

use App\Http\Middleware\ForceSecureConnections;
use App\Http\Middleware\LogAdminActivity;
use App\Http\Middleware\RequireAdminTwoFactor;
use App\Http\Middleware\SetSecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'active' => \App\Http\Middleware\EnsureActive::class,
            'admin.2fa' => RequireAdminTwoFactor::class,
            'admin.audit' => LogAdminActivity::class,
        ]);

        // TLS-terminating reverse proxies (cPanel/shared hosting) must be
        // trusted so $request->secure() sees X-Forwarded-Proto — otherwise
        // the HTTPS redirect loops and HSTS/secure cookies never engage.
        $proxies = env('TRUSTED_PROXIES');
        if ($proxies) {
            $middleware->trustProxies(at: array_map('trim', explode(',', $proxies)));
        }

        $middleware->validateCsrfTokens(except: [
            'telegram/webhook',
        ]);

        $middleware->web(append: [
            ForceSecureConnections::class,
            SetSecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

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
        // Read from config, NOT env(): after `config:cache` (run by the
        // cPanel deploy hook) a raw env() call here returns null, silently
        // dropping proxy trust and breaking HTTPS detection behind the
        // host's TLS terminator. See config/broca.php.
        $proxies = config('broca.trusted_proxies', []);
        if ($proxies !== []) {
            // '*' must be passed as a bare string, not a single-item array.
            $middleware->trustProxies(at: $proxies === ['*'] ? '*' : $proxies);
        }

        $middleware->validateCsrfTokens(except: [
            'telegram/webhook',
        ]);

        $middleware->web(append: [
            ForceSecureConnections::class,
            SetSecurityHeaders::class,
            \App\Http\Middleware\ServeMarkdown::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

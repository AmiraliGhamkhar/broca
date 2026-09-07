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
            // Gates /dashboard, /checkout and /admin on "the account proved a
            // contact channel": the emailed link OR the SMS one-time code.
            // Named explicitly (rather than overriding the framework's
            // `verified` alias) so the guard is greppable at every route and
            // cannot be swapped out by an alias-merge order change.
            'verified.contact' => \App\Http\Middleware\EnsureVerifiedContact::class,
            'admin.2fa' => RequireAdminTwoFactor::class,
            'admin.audit' => LogAdminActivity::class,
        ]);

        // TLS-terminating reverse proxies (cPanel/shared hosting) must be
        // trusted so $request->secure() sees X-Forwarded-Proto — otherwise
        // the HTTPS redirect loops and HSTS/secure cookies never engage.
        // Proxy trust is resolved at request time in App\Http\Middleware\
        // TrustProxies. It cannot be done here: env() breaks under
        // config:cache, and config() is not yet bound in this closure.
        $middleware->prepend(\App\Http\Middleware\TrustProxies::class);

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

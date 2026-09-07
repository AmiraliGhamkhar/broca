<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Models\User;
use App\Support\Totp;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The failed-login path (and any Timebox'd code) sleeps for ~200 ms;
        // under the php-wasm CI harness that C-level usleep aborts the
        // asyncify engine. Faking Sleep there is a no-op in real PHP, where
        // PHP_SAPI is cli/fpm, and keeps the timing-normalization logic
        // fully exercised (Sleep::fake records the sleeps).
        if (getenv('WASM_HARNESS')) {
            \Illuminate\Support\Sleep::fake();
        }

        /*
         * Every rate limiter in the app (named limiters, the login counter, the
         * 2FA budgets) lives in the cache store. phpunit uses CACHE_STORE=array
         * and a fresh application per test normally isolates it — but that
         * isolation is exactly what makes auth tests order-dependent the moment
         * a store survives, and a test that posts to /register or /login a
         * handful of times would then be answered with a 429 instead of the
         * redirect it asserts. Clearing here keeps each test's limiter state
         * self-contained, and any test that *wants* throttling builds it up
         * inside its own body.
         */
        \Illuminate\Support\Facades\Cache::clear();
    }

    protected function actingAsAdmin(User $admin): static
    {
        if (! $admin->hasConfirmedTwoFactor()) {
            $admin->forceFill([
                'totp_secret' => Totp::generateSecret(),
                'totp_confirmed_at' => now(),
            ])->save();
        }

        return $this->actingAs($admin)->withSession([
            \App\Http\Middleware\RequireAdminTwoFactor::SESSION_KEY => now()->timestamp,
        ]);
    }
}

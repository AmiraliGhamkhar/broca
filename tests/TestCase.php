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

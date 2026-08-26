<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Models\User;
use App\Support\Totp;

abstract class TestCase extends BaseTestCase
{
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

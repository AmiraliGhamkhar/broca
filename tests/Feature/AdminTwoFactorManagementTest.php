<?php

namespace Tests\Feature;

use App\Models\AdminActivityLog;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 2FA management hardening: the enable/disable/recovery endpoints are
 * rate-limited (a 6-digit TOTP is brute-forceable without a bound) and the
 * whole lifecycle is audit-logged with one-time codes redacted.
 */
class AdminTwoFactorManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_enable_is_rate_limited_after_five_attempts(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post(route('admin.two-factor.start'));
        $admin = $admin->fresh();

        for ($i = 0; $i < 4; $i++) {
            $this->actingAs($admin)
                ->post(route('admin.two-factor.enable'), ['code' => '000000'])
                ->assertSessionHasErrors('code');
        }

        // The 5th is the last allowed one; the 6th is refused before the
        // controller runs, and the named limiter answers with a redirect plus a
        // Persian notice rather than a raw 429 page.
        $this->actingAs($admin)
            ->post(route('admin.two-factor.enable'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->actingAs($admin)
            ->post(route('admin.two-factor.enable'), ['code' => '000000'])
            ->assertRedirect(route('admin.two-factor.edit'))
            ->assertSessionHas('error');
    }

    /**
     * The disable form shares the code-verification budget with the challenge,
     * enable and recovery endpoints (five per minute): one attacker with four
     * forms is still one attacker, and splitting the budget per route would
     * simply multiply their guesses. The form answer is the Persian redirect
     * with `error` flashed, not a bare 429 page — that is what
     * `admin-2fa-verify`'s Limit::response() exists for.
     */
    public function test_disable_shares_the_code_verification_budget(): void
    {
        $admin = $this->enrolledAdmin();

        for ($i = 0; $i < 4; $i++) {
            $this->actingAs($admin)
                ->post(route('admin.two-factor.disable'), ['code' => '000000'])
                ->assertSessionHasErrors('code');
        }

        $this->actingAs($admin)
            ->post(route('admin.two-factor.disable'), ['code' => '000000'])
            ->assertRedirect(route('admin.two-factor.edit'))
            ->assertSessionHas('error');

        $this->assertStringContainsString('دو مرحله‌ای', session('error'));
    }

    public function test_recovery_code_regeneration_is_rate_limited(): void
    {
        $admin = $this->enrolledAdmin();

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($admin)->post(route('admin.two-factor.recovery-codes'))->assertRedirect();
        }

        $this->actingAs($admin)->post(route('admin.two-factor.recovery-codes'))->assertStatus(429);
    }

    public function test_two_factor_lifecycle_actions_are_audit_logged(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.two-factor.start'));
        $this->assertDatabaseHas('admin_activity_logs', [
            'user_id' => $admin->id,
            'route_name' => 'admin.two-factor.start',
            'method' => 'POST',
        ]);

        $admin = $admin->fresh();
        $this->actingAs($admin)
            ->post(route('admin.two-factor.enable'), ['code' => Totp::currentCode((string) $admin->totp_secret)])
            ->assertRedirect();
        $this->assertDatabaseHas('admin_activity_logs', ['route_name' => 'admin.two-factor.enable']);

        $admin = $admin->fresh();
        $this->actingAs($admin)
            ->post(route('admin.two-factor.recovery-codes'))
            ->assertRedirect();
        $this->assertDatabaseHas('admin_activity_logs', ['route_name' => 'admin.two-factor.recovery-codes']);

        $admin = $admin->fresh();
        $this->actingAs($admin)
            ->post(route('admin.two-factor.disable'), ['code' => Totp::currentCode((string) $admin->totp_secret)])
            ->assertRedirect();
        $this->assertDatabaseHas('admin_activity_logs', ['route_name' => 'admin.two-factor.disable']);
    }

    public function test_totp_inputs_have_accessible_labels(): void
    {
        // Enrollment step (enable form).
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post(route('admin.two-factor.start'));

        $content = $this->actingAs($admin)->get(route('admin.two-factor.edit'))->assertOk()->getContent();
        $this->assertStringContainsString('for="enable_code"', $content);
        $this->assertStringContainsString('id="enable_code"', $content);

        // Confirmed step (disable form).
        $admin = $admin->fresh();
        $admin->forceFill(['totp_secret' => Totp::generateSecret(), 'totp_confirmed_at' => now()])->save();

        $content = $this->actingAs($admin)->get(route('admin.two-factor.edit'))->assertOk()->getContent();
        $this->assertStringContainsString('for="disable_code"', $content);
        $this->assertStringContainsString('id="disable_code"', $content);
    }

    public function test_audit_logs_redact_one_time_codes_and_passwords(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post(route('admin.two-factor.start'));

        $admin = $admin->fresh();
        $this->actingAs($admin)
            ->post(route('admin.two-factor.enable'), ['code' => '123456', 'password' => 'hunter2'])
            ->assertRedirect();

        $row = AdminActivityLog::where('route_name', 'admin.two-factor.enable')->firstOrFail();
        $this->assertIsArray($row->payload);
        $this->assertArrayNotHasKey('code', $row->payload);
        $this->assertArrayNotHasKey('password', $row->payload);
        $this->assertStringNotContainsString('123456', json_encode($row->payload));
        $this->assertStringNotContainsString('hunter2', json_encode($row->payload));
    }

    private function enrolledAdmin(): User
    {
        $admin = User::factory()->admin()->create();
        $admin->forceFill([
            'totp_secret' => Totp::generateSecret(),
            'totp_confirmed_at' => now(),
        ])->save();

        return $admin->fresh();
    }
}

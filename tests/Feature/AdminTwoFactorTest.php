<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_without_2fa_is_required_to_enroll(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin')->assertRedirect(route('admin.two-factor.edit'));
    }

    public function test_admin_with_confirmed_2fa_is_challenged(): void
    {
        $admin = $this->enrolledAdmin();

        $this->actingAs($admin)->get('/admin')
            ->assertRedirect(route('admin.two-factor.challenge'));
    }

    public function test_wrong_code_does_not_pass_the_challenge(): void
    {
        $admin = $this->enrolledAdmin();

        $this->actingAs($admin)
            ->post(route('admin.two-factor.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->actingAs($admin)->get('/admin')
            ->assertRedirect(route('admin.two-factor.challenge'));
    }

    public function test_correct_code_passes_the_challenge(): void
    {
        $admin = $this->enrolledAdmin();
        $secret = $admin->totp_secret;

        $this->actingAs($admin)
            ->post(route('admin.two-factor.verify'), ['code' => Totp::currentCode($secret)])
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_recovery_code_works_exactly_once(): void
    {
        $admin = $this->enrolledAdmin();
        $admin->storeRecoveryCodes(['ABCDE-FGHJK']);

        $this->actingAs($admin)
            ->post(route('admin.two-factor.recover'), ['recovery_code' => 'abcde-fghjk'])
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($admin)->get('/admin')->assertOk();

        // Rotate: clear the pass flag, second use of the same code must fail.
        $this->session([\App\Http\Middleware\RequireAdminTwoFactor::SESSION_KEY => null]);
        $admin->forceFill(['totp_secret' => Totp::generateSecret(), 'totp_confirmed_at' => now()])->save();

        $this->actingAs($admin)
            ->post(route('admin.two-factor.recover'), ['recovery_code' => 'ABCDE-FGHJK'])
            ->assertSessionHasErrors('recovery_code');
    }

    public function test_non_admin_never_reaches_2fa_routes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.two-factor.challenge'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.two-factor.verify'), ['code' => '123456'])->assertForbidden();
    }

    public function test_admin_can_disable_two_factor_with_the_current_code(): void
    {
        $admin = $this->enrolledAdmin();
        $admin->storeRecoveryCodes(['AAAAA-BBBBB']);

        $this->actingAs($admin)
            ->post(route('admin.two-factor.disable'), ['code' => Totp::currentCode($admin->totp_secret)])
            ->assertRedirect(route('admin.two-factor.edit'));

        $admin = $admin->fresh();
        $this->assertNull($admin->totp_secret);
        $this->assertNull($admin->totp_confirmed_at);
        $this->assertSame([], $admin->recoveryCodes());
        $this->assertFalse($admin->hasConfirmedTwoFactor());

        // The panel no longer demands the challenge (same as never enrolled).
        $this->actingAs($admin)->get('/admin')
            ->assertRedirect(route('admin.two-factor.edit'));
    }

    public function test_admin_cannot_disable_two_factor_with_a_wrong_code(): void
    {
        $admin = $this->enrolledAdmin();

        $this->actingAs($admin)
            ->post(route('admin.two-factor.disable'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertTrue($admin->fresh()->hasConfirmedTwoFactor());
    }

    public function test_admin_can_regenerate_recovery_codes(): void
    {
        $admin = $this->enrolledAdmin();
        $admin->storeRecoveryCodes(['OLD01-AAAAA', 'OLD02-BBBBB']);

        $this->actingAs($admin)
            ->post(route('admin.two-factor.recovery-codes'))
            ->assertRedirect(route('admin.two-factor.edit'))
            ->assertSessionHas('recovery_codes');

        $admin = $admin->fresh();
        $codes = $admin->recoveryCodes();
        $this->assertCount(10, $codes);
        $this->assertNotContains('OLD01-AAAAA', $codes);

        // The DB stores sha256 hashes — plaintext is flashed to the session
        // exactly once at generation time.
        $plaintext = session('recovery_codes');
        $this->assertIsArray($plaintext);
        $this->assertCount(10, $plaintext);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $codes[0]);
        $this->assertNotContains($plaintext[0], $codes);

        // Old codes are dead immediately.
        $this->assertFalse($admin->consumeRecoveryCode('OLD01-AAAAA'));
        // A fresh code still works once (case-insensitively).
        $this->assertTrue($admin->consumeRecoveryCode(strtolower($plaintext[0])));
    }

    public function test_stored_recovery_codes_are_never_plaintext(): void
    {
        $admin = $this->enrolledAdmin();
        $admin->storeRecoveryCodes(['SECRET-XXXXX']);

        $stored = $admin->fresh()->recoveryCodes();
        $this->assertCount(1, $stored);
        $this->assertNotSame('SECRET-XXXXX', $stored[0]);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $stored[0]);
        // Raw DB payload must not contain the plaintext anywhere.
        $this->assertStringNotContainsString('SECRET-XXXXX', (string) $admin->fresh()->getRawOriginal('recovery_codes'));

        // Hashing must not change the acceptance semantics.
        $this->assertTrue($admin->consumeRecoveryCode('secret-xxxxx'));
        $this->assertSame([], $admin->fresh()->recoveryCodes());
    }

    public function test_totp_enrollment_flow(): void
    {
        $admin = User::factory()->admin()->create();

        // Start: generates a pending secret.
        $this->actingAs($admin)->post(route('admin.two-factor.start'));
        $admin = $admin->fresh();
        $this->assertNotNull($admin->totp_secret);
        $this->assertNull($admin->totp_confirmed_at);
        $this->assertFalse($admin->hasConfirmedTwoFactor());

        // Confirm with the current code.
        $this->actingAs($admin)
            ->post(route('admin.two-factor.enable'), ['code' => Totp::currentCode($admin->totp_secret)])
            ->assertRedirect(route('admin.two-factor.edit'))
            ->assertSessionHas('recovery_codes');

        $admin = $admin->fresh();
        $this->assertTrue($admin->hasConfirmedTwoFactor());
        $this->assertCount(10, $admin->recoveryCodes());

        // Now the panel requires the challenge.
        $this->actingAs($admin)->get('/admin')
            ->assertRedirect(route('admin.two-factor.challenge'));
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

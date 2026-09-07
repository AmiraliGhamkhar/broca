<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * "The admin cannot log in with the correct email and password."
 *
 * Four things produce that sentence, and only one of them is a wrong
 * password. These tests pin the other three:
 *
 *  1. the account is stored in a spelling the login lookup cannot match
 *     ("Admin@Example.com ", "+98912…") — an import, or hand-run SQL;
 *  2. the account is not `active`, which the login form used to report as a
 *     wrong password;
 *  3. the stored password is not a hash this app can verify — an md5/sha1/
 *     plaintext value left behind by a migration, which no correct password
 *     will ever match.
 *
 * Plus the recovery path for all three: the operator commands.
 */
class AdminLoginLookupTest extends TestCase
{
    use RefreshDatabase;

    private const STRONG_PASSWORD = 'Xk9vP2mQ7zR4tW8n';

    public function test_login_finds_an_email_stored_with_stray_case_and_padding(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => '  ADMIN@Broca.example  ',
            'password' => self::STRONG_PASSWORD,
        ]);

        // The stored value is not canonical, but it is the operator's address
        // and the password is right: login must succeed.
        $this->post('/login', ['identifier' => 'admin@broca.example', 'password' => self::STRONG_PASSWORD])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_login_finds_a_number_stored_with_an_international_prefix(): void
    {
        $user = User::factory()->create([
            'phone' => '+989123456789',
            'password' => self::STRONG_PASSWORD,
        ]);

        $this->post('/login', ['identifier' => '09123456789', 'password' => self::STRONG_PASSWORD])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_suspended_account_is_told_so_instead_of_being_told_its_password_is_wrong(): void
    {
        User::factory()->admin()->create([
            'email' => 'locked@broca.example',
            'password' => self::STRONG_PASSWORD,
            'status' => 'suspended',
        ]);

        // The old answer («…یا گذرواژه درست نیست») sent an operator looking for
        // a password problem when the problem was the account state.
        $this->post('/login', ['identifier' => 'locked@broca.example', 'password' => self::STRONG_PASSWORD])
            ->assertSessionHasErrors('identifier');

        $this->assertStringContainsString(
            'غیرفعال',
            session('errors')->first('identifier')
        );
    }

    public function test_an_unknown_account_keeps_the_single_neutral_message(): void
    {
        $this->post('/login', ['identifier' => 'nobody@broca.example', 'password' => self::STRONG_PASSWORD])
            ->assertSessionHasErrors('identifier');

        $this->assertSame(
            'ایمیل/شمارهٔ همراه یا گذرواژه درست نیست.',
            session('errors')->first('identifier')
        );
    }

    public function test_diagnose_flags_a_password_that_can_never_be_verified(): void
    {
        $user = User::factory()->create(['email' => 'legacy@broca.example']);

        // Written straight to the table: the model's `hashed` cast would
        // re-hash anything that is not already bcrypt/argon, which is exactly
        // why an imported row can hold an un-verifiable value.
        DB::table('users')->where('id', $user->getKey())->update(['password' => md5('old-panel')]);

        $this->artisan('broca:user:diagnose', ['identifier' => 'legacy@broca.example'])
            ->expectsOutputToContain('md5')
            ->assertFailed();
    }

    public function test_repair_restores_a_locked_out_admin(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'LOCKED@Broca.example',
            'status' => 'suspended',
            'password' => self::STRONG_PASSWORD,
        ]);

        $this->artisan('broca:user:repair', [
            'identifier' => $admin->getKey(),
            '--activate' => true,
            '--normalize' => true,
            '--verify-email' => true,
            '--force' => true,
        ])->assertSuccessful();

        $admin->refresh();

        $this->assertSame('active', $admin->status);
        $this->assertSame('locked@broca.example', $admin->email);
        $this->assertNotNull($admin->email_verified_at);

        // And the thing it was all for: the operator can now sign in.
        auth()->logout();
        $this->post('/login', ['identifier' => 'locked@broca.example', 'password' => self::STRONG_PASSWORD])
            ->assertRedirect(route('dashboard'));
    }

    public function test_repair_refuses_to_remove_the_last_active_admin(): void
    {
        $admin = User::factory()->admin()->create(['status' => 'suspended']);

        $this->artisan('broca:user:repair', [
            'identifier' => $admin->getKey(),
            '--demote' => true,
            '--force' => true,
        ])->assertSuccessful();

        // Still an admin: demoting the only one would leave /admin unreachable.
        $this->assertTrue((bool) $admin->fresh()->is_admin);
    }

    public function test_identifiers_can_be_normalized_in_place_for_every_account(): void
    {
        $user = User::factory()->create([
            'email' => 'Mixed@Case.example ',
            'phone' => '+989123456789',
        ]);

        $this->artisan('broca:identifiers:normalize')->assertSuccessful();

        $user->refresh();

        $this->assertSame('mixed@case.example', $user->email);
        $this->assertSame('09123456789', $user->phone);
    }
}

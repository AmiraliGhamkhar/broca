<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Tests\Concerns\WasmSafeRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use WasmSafeRefreshDatabase;

    public function test_registration_requires_name_email_phone_password_and_consent(): void
    {
        $this->post('/register', [])
            ->assertSessionHasErrors(['name', 'email', 'phone', 'password', 'consent']);
    }

    public function test_registration_stores_consent_and_redirects_to_verification_notice(): void
    {
        $response = $this->post('/register', [
            'name' => 'علی احمدی',
            'email' => 'ali@example.com',
            'phone' => '09123456789',
            'password' => 'Xk9vP2mQ7zR4tW8n',
            'password_confirmation' => 'Xk9vP2mQ7zR4tW8n',
            'consent' => '1',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $user = User::query()->where('email', 'ali@example.com')->first();
        $this->assertNotNull($user);
        $this->assertDatabaseHas('users', ['email' => 'ali@example.com', 'phone' => '09123456789']);
        // Never assert a hardcoded id: on MySQL, auto-increment values
        // consumed by rolled-back tests are never reused, so "the first
        // user is id 1" only holds on SQLite.
        $this->assertDatabaseHas('user_consents', ['user_id' => $user->id]);
    }

    public function test_registration_normalizes_persian_digits_and_plus_98_prefix(): void
    {
        $this->post('/register', [
            'name' => 'سارا محمدی',
            'email' => 'sara@example.com',
            'phone' => '+98 ۹۱۲ ۳۴۵ ۶۷۸۹',
            'password' => 'Xk9vP2mQ7zR4tW8n',
            'password_confirmation' => 'Xk9vP2mQ7zR4tW8n',
            'consent' => '1',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'sara@example.com', 'phone' => '09123456789']);
    }

    public function test_registration_with_an_invalid_phone_returns_422_not_500(): void
    {
        $response = $this->post('/register', [
            'name' => 'نام',
            'email' => 'x@example.com',
            'phone' => '12345',
            'password' => 'Xk9vP2mQ7zR4tW8n',
            'password_confirmation' => 'Xk9vP2mQ7zR4tW8n',
            'consent' => '1',
        ]);

        $response->assertSessionHasErrors('phone');
        $this->assertDatabaseMissing('users', ['email' => 'x@example.com']);
    }

    public function test_registration_cannot_grant_admin_through_mass_assignment(): void
    {
        $this->post('/register', [
            'name' => 'مهاجم',
            'email' => 'attacker@example.com',
            'phone' => '09121112233',
            'password' => 'Xk9vP2mQ7zR4tW8n',
            'password_confirmation' => 'Xk9vP2mQ7zR4tW8n',
            'consent' => '1',
            'is_admin' => '1',
            'status' => 'whatever',
        ]);

        $user = User::where('email', 'attacker@example.com')->firstOrFail();
        $this->assertFalse((bool) $user->is_admin);
        $this->assertSame('active', $user->status);
    }

    public function test_registration_sends_the_apps_queued_verification_notification(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'مهدی رضایی',
            'email' => 'mehdi@example.com',
            'phone' => '09129998877',
            'password' => 'Xk9vP2mQ7zR4tW8n',
            'password_confirmation' => 'Xk9vP2mQ7zR4tW8n',
            'consent' => '1',
        ]);

        $user = User::query()->where('email', 'mehdi@example.com')->firstOrFail();

        Notification::assertSentTo($user, VerifyEmailNotification::class);
        // The override must win: the framework's stock (English, sync)
        // notification is never what gets queued.
        Notification::assertNotSentTo($user, \Illuminate\Auth\Notifications\VerifyEmail::class);
    }

    public function test_guest_middleware_redirects_authed_users_to_dashboard(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($user)
            ->get(route('register'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_login_accepts_email_or_phone(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'phone' => '09123456789',
            'password' => 'Xk9vP2mQ7zR4tW8n',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->post('/login', ['identifier' => 'test@example.com', 'password' => 'Xk9vP2mQ7zR4tW8n'])
            ->assertRedirect(route('dashboard'));

        auth()->logout();

        $this->post('/login', ['identifier' => '09123456789', 'password' => 'Xk9vP2mQ7zR4tW8n'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_login_with_a_garbage_identifier_returns_422_not_500(): void
    {
        $this->post('/login', ['identifier' => 'not-an-email-or-phone', 'password' => 'Xk9vP2mQ7zR4tW8n'])
            ->assertSessionHasErrors('identifier');
    }

    public function test_login_rejects_suspended_user(): void
    {
        $user = User::factory()->create([
            'email' => 'suspended@example.com',
            'password' => 'Xk9vP2mQ7zR4tW8n',
            'status' => 'suspended',
            'email_verified_at' => now(),
        ]);

        $this->post('/login', ['identifier' => 'suspended@example.com', 'password' => 'Xk9vP2mQ7zR4tW8n'])
            ->assertSessionHasErrors('identifier');
    }

    public function test_suspended_user_with_a_live_session_is_logged_out_by_middleware(): void
    {
        $user = User::factory()->create(['password' => 'Xk9vP2mQ7zR4tW8n']);

        $this->actingAs($user)->get('/dashboard')->assertOk();

        $user->forceFill(['status' => 'suspended'])->save();

        $this->actingAs($user->fresh())->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_verified_middleware_redirects_unverified_to_notice(): void
    {
        $user = User::factory()->unverified()->create(['status' => 'active']);
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));
    }

    public function test_password_reset_uses_the_apps_queued_reset_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'reset@example.com', 'status' => 'active']);

        $this->post('/forgot-password', ['email' => 'reset@example.com'])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
        // The override must win over the framework's stock notification.
        Notification::assertNotSentTo($user, \Illuminate\Auth\Notifications\ResetPassword::class);
    }

    public function test_password_reset_changes_the_password_and_kills_every_live_session(): void
    {
        // The reset path assumes a compromised account: on the database
        // session driver (production) every stored session row of the user
        // must be removed, not just the one performing the reset.
        config(['session.driver' => 'database']);

        $user = User::factory()->create(['password' => 'OldPass123Xk9vP2mQ']);

        // A live session held by the (possibly compromised) account.
        DB::table('sessions')->insert([
            'id' => \Illuminate\Support\Str::random(40),
            'user_id' => $user->id,
            'payload' => 2,
            'last_activity' => now()->timestamp,
        ]);

        $token = \Illuminate\Support\Facades\Password::broker()->createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Xk9vP2mQ7zR4tW8n',
            'password_confirmation' => 'Xk9vP2mQ7zR4tW8n',
        ])->assertRedirect(route('login'))->assertSessionHas('status');

        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Xk9vP2mQ7zR4tW8n', $user->fresh()->password));
    }
}

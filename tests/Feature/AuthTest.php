<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Facades\Notification;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

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
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'consent' => '1',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertDatabaseHas('users', ['email' => 'ali@example.com', 'phone' => '09123456789']);
        $this->assertDatabaseHas('user_consents', ['user_id' => 1]);
    }

    public function test_registration_normalizes_persian_digits_and_plus_98_prefix(): void
    {
        $this->post('/register', [
            'name' => 'سارا محمدی',
            'email' => 'sara@example.com',
            'phone' => '+98 ۹۱۲ ۳۴۵ ۶۷۸۹',
            'password' => 'password123',
            'password_confirmation' => 'password123',
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
            'password' => 'password123',
            'password_confirmation' => 'password123',
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
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'consent' => '1',
            'is_admin' => '1',
            'status' => 'whatever',
        ]);

        $user = User::where('email', 'attacker@example.com')->firstOrFail();
        $this->assertFalse((bool) $user->is_admin);
        $this->assertSame('active', $user->status);
    }

    public function test_login_accepts_email_or_phone(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'phone' => '09123456789',
            'password' => 'password123',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->post('/login', ['identifier' => 'test@example.com', 'password' => 'password123'])
            ->assertRedirect(route('dashboard'));

        auth()->logout();

        $this->post('/login', ['identifier' => '09123456789', 'password' => 'password123'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_login_with_a_garbage_identifier_returns_422_not_500(): void
    {
        $this->post('/login', ['identifier' => 'not-an-email-or-phone', 'password' => 'password123'])
            ->assertSessionHasErrors('identifier');
    }

    public function test_login_rejects_suspended_user(): void
    {
        $user = User::factory()->create([
            'email' => 'suspended@example.com',
            'password' => 'password123',
            'status' => 'suspended',
            'email_verified_at' => now(),
        ]);

        $this->post('/login', ['identifier' => 'suspended@example.com', 'password' => 'password123'])
            ->assertSessionHasErrors('identifier');
    }

    public function test_suspended_user_with_a_live_session_is_logged_out_by_middleware(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        $this->actingAs($user)->get('/dashboard')->assertOk();

        $user->forceFill(['status' => 'suspended'])->save();

        $this->actingAs($user->fresh())->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_verified_middleware_redirects_unverified_to_notice(): void
    {
        $user = User::factory()->unverified()->create(['status' => 'active']);
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));
    }
}

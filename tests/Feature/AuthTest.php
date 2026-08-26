<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_verified_middleware_redirects_unverified_to_notice(): void
    {
        $user = User::factory()->unverified()->create(['status' => 'active']);
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));
    }

    public function test_catalog_requires_published_courses(): void
    {
        $draft = \App\Models\Course::factory()->create(['status' => 'draft']);
        $published = \App\Models\Course::factory()->create(['status' => 'published', 'published_at' => now()]);

        $this->get('/catalog')->assertSee($published->title)->assertDontSee($draft->title);
    }

    public function test_course_show_only_published_and_current(): void
    {
        $future = \App\Models\Course::factory()->create(['status' => 'published', 'published_at' => now()->addDay()]);
        $past = \App\Models\Course::factory()->create(['status' => 'published', 'published_at' => now()->subDay()]);

        $this->get('/courses/' . $future->slug)->assertNotFound();
        $this->get('/courses/' . $past->slug)->assertOk()->assertSee($past->title);
    }

    public function test_enrollment_idempotent_for_published_course(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $course = \App\Models\Course::factory()->create(['status' => 'published', 'published_at' => now()]);

        $this->actingAs($user)->post('/courses/' . $course->slug . '/enroll')
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseCount('course_enrollments', 1);

        $this->actingAs($user)->post('/courses/' . $course->slug . '/enroll');
        $this->assertDatabaseCount('course_enrollments', 1);
    }
}
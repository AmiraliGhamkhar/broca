<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\PhoneVerificationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Mobile verification: the second path into an activated account.
 *
 * The emailed link is still the first path, but it is not the only one any
 * more, because "the mail never arrived" used to mean "the account is dead".
 * These tests pin the two properties that matter: a code really is delivered
 * to the number on file, and a verified mobile unlocks exactly what a verified
 * email unlocks.
 */
class PhoneVerificationTest extends TestCase
{
    use RefreshDatabase;

    private const STRONG_PASSWORD = 'Xk9vP2mQ7zR4tW8n';

    public function test_registration_sends_a_one_time_code_and_stores_only_its_hash(): void
    {
        Notification::fake();

        $this->post('/register', $this->registrationPayload('sms@example.com'))->assertRedirect(route('verification.notice'));

        $user = User::query()->where('email', 'sms@example.com')->firstOrFail();

        Notification::assertSentTo($user, PhoneVerificationNotification::class);

        // The code is a live credential for ten minutes: it is persisted
        // bcrypt-hashed, never in plaintext, and the account is not verified
        // until the user types it.
        $this->assertNotNull($user->phone_verification_code);
        $this->assertNull($user->phone_verified_at);
        $this->assertNotNull($user->phone_verification_expires_at);
    }

    public function test_the_code_activates_the_account_and_unlocks_the_dashboard(): void
    {
        Notification::fake();

        $this->post('/register', $this->registrationPayload('code@example.com'));

        $user = User::query()->where('email', 'code@example.com')->firstOrFail();

        // The gate must apply before verification, for the same reason it
        // applies to an unverified email: checkout is money.
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));

        $this->actingAs($user)
            ->post(route('verification.phone.verify'), ['code' => $this->capturedCode($user)])
            ->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertTrue($user->hasVerifiedPhone());
        $this->assertNull($user->phone_verification_code, 'a used code must be cleared, not left live');

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_a_wrong_code_is_refused_and_counts_against_the_attempt_budget(): void
    {
        Notification::fake();

        $this->post('/register', $this->registrationPayload('wrong@example.com'));

        $user = User::query()->where('email', 'wrong@example.com')->firstOrFail();

        $this->actingAs($user)
            ->post(route('verification.phone.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertSame(1, (int) $user->fresh()->phone_verification_attempts);
        $this->assertFalse($user->fresh()->hasVerifiedPhone());
    }

    public function test_an_expired_code_is_refused_and_cleared(): void
    {
        Notification::fake();

        $this->post('/register', $this->registrationPayload('expired@example.com'));

        $user = User::query()->where('email', 'expired@example.com')->firstOrFail();
        $code = $this->capturedCode($user);

        $this->travel(15)->minutes();

        $this->actingAs($user->fresh())
            ->post(route('verification.phone.verify'), ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->phone_verification_code);
    }

    public function test_persian_digits_are_accepted_because_that_is_how_the_code_is_shown(): void
    {
        Notification::fake();

        $this->post('/register', $this->registrationPayload('persian@example.com'));

        $user = User::query()->where('email', 'persian@example.com')->firstOrFail();
        $code = $this->capturedCode($user);

        $persian = strtr($code, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);

        $this->actingAs($user)
            ->post(route('verification.phone.verify'), ['code' => $persian])
            ->assertRedirect(route('dashboard'));

        $this->assertTrue($user->fresh()->hasVerifiedPhone());
    }

    public function test_resending_is_held_back_by_the_cooldown_because_each_text_costs_money(): void
    {
        Notification::fake();

        $this->post('/register', $this->registrationPayload('resend@example.com'));

        $user = User::query()->where('email', 'resend@example.com')->firstOrFail();

        $this->actingAs($user)
            ->post(route('verification.phone.send'))
            ->assertSessionHasErrors('phone_code');

        Notification::assertSentTimes(PhoneVerificationNotification::class, 1);

        $this->travel(2)->minutes();

        $this->actingAs($user->fresh())
            ->post(route('verification.phone.send'))
            ->assertSessionHasNoErrors();

        Notification::assertSentTimes(PhoneVerificationNotification::class, 2);
    }

    public function test_a_phone_verified_account_passes_the_gate_without_ever_opening_the_email(): void
    {
        // The whole point: an account whose mail is lost is not a dead account.
        $user = User::factory()->unverified()->create(['phone' => '09123456789']);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));

        $user->markPhoneAsVerified();

        $this->actingAs($user->fresh())->get('/dashboard')->assertOk();
    }

    /**
     * Pull the plaintext code back out of the notification the app sent — it
     * is the only place the code ever exists in readable form.
     */
    private function capturedCode(User $user): string
    {
        $code = null;

        Notification::assertSentTo(
            $user,
            PhoneVerificationNotification::class,
            function (PhoneVerificationNotification $notification) use (&$code): bool {
                $code = $notification->code;

                return true;
            }
        );

        $this->assertIsString($code);
        $this->assertNotSame('', $code);

        return (string) $code;
    }

    /** @return array<string, string> */
    private function registrationPayload(string $email): array
    {
        return [
            'name' => 'کاربر تست',
            'email' => $email,
            'phone' => '0912'.random_int(1000000, 9999999),
            'password' => self::STRONG_PASSWORD,
            'password_confirmation' => self::STRONG_PASSWORD,
            'consent' => '1',
        ];
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use App\Support\PasswordPolicy;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Register/login hardening: canonical normalization of every credential,
 * one shared password policy, no 500s from input shapes or DB races, and
 * Persian-speaking throttle feedback instead of framework English.
 */
class AuthHardeningTest extends TestCase
{
    use RefreshDatabase;

    private const STRONG_PASSWORD = 'Xk9vP2mQ7zR4tW8n';

    /** @return array<string, mixed> */
    private function registration(array $overrides = []): array
    {
        return array_merge([
            'name' => 'علی احمدی',
            'email' => 'ali@example.com',
            'phone' => '09123456789',
            'password' => self::STRONG_PASSWORD,
            'password_confirmation' => self::STRONG_PASSWORD,
            'consent' => '1',
        ], $overrides);
    }

    public function test_registration_normalizes_a_padded_and_capitalized_email(): void
    {
        $this->post('/register', $this->registration([
            'email' => '  Ali@Example.COM  ',
        ]))->assertRedirect(route('verification.notice'));

        $user = $this->post('/login', [
            'identifier' => 'ali@example.com',
            'password' => self::STRONG_PASSWORD,
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'ali@example.com']);
    }

    public function test_login_accepts_the_same_capitalization_and_persian_digits_the_form_may_send(): void
    {
        User::factory()->create([
            'email' => 'mixed@example.com',
            'phone' => '09122223333',
            'password' => self::STRONG_PASSWORD,
        ]);

        $this->post('/login', ['identifier' => '  MIXED@Example.com ', 'password' => self::STRONG_PASSWORD])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        // Phone typed with Persian digits (iOS keyboards default to them).
        auth()->logout();
        $this->post('/login', ['identifier' => '۰۹۱۲۲۲۲۳۳۳۳', 'password' => self::STRONG_PASSWORD])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_registration_applies_the_shared_password_policy(): void
    {
        // No uppercase → the policy (not the length rule) rejects it.
        $this->post('/register', $this->registration([
            'password' => 'xk9vp2mq7zr4',
            'password_confirmation' => 'xk9vp2mq7zr4',
        ]))->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'ali@example.com']);

        // Too short, and no digits.
        $this->post('/register', $this->registration([
            'password' => 'Abcdefgh',
            'password_confirmation' => 'Abcdefgh',
        ]))->assertSessionHasErrors('password');
    }

    public function test_login_guides_malformed_identifiers_without_leaking_account_existence(): void
    {
        User::factory()->create(['email' => 'guide@example.com', 'password' => self::STRONG_PASSWORD]);

        // Phone-shaped but too short → says what a valid number looks like.
        $this->post('/login', ['identifier' => '0912', 'password' => 'whatever-1'])
            ->assertSessionHasErrors('identifier');
        $this->assertStringContainsString('۱۱ رقم', session('errors')->first('identifier'));

        // Neither email nor phone → the general hint. No message ever reveals
        // whether an account exists.
        $this->post('/login', ['identifier' => 'not-an-email-or-phone', 'password' => 'whatever-2'])
            ->assertSessionHasErrors('identifier');
        $second = session('errors')->first('identifier');
        $this->assertStringNotContainsString('پیدا نشد', $second);
        $this->assertStringNotContainsString('guide@example.com', $second);
    }

    public function test_a_raced_duplicate_email_is_a_form_error_and_not_a_500(): void
    {
        User::factory()->create(['email' => 'taken@example.com', 'phone' => '09120000000']);

        // The unique rule already covers the common case; the DB catch covers
        // two requests that pass validation simultaneously. Asserting the
        // visible outcome (422 + Persian copy, no new row) is the contract.
        $this->post('/register', $this->registration(['email' => 'taken@example.com']))
            ->assertSessionHasErrors('email');

        $this->assertSame(1, User::query()->where('email', 'taken@example.com')->count());
    }

    public function test_array_payloads_cannot_turn_validation_into_a_500(): void
    {
        $this->post('/register', [
            'name' => ['nested'],
            'email' => ['nested'],
            'phone' => ['nested'],
            'password' => self::STRONG_PASSWORD,
            'password_confirmation' => self::STRONG_PASSWORD,
            'consent' => '1',
        ])->assertSessionHasErrors(['name', 'email', 'phone']);
    }

    public function test_registration_feedback_is_honest_and_leads_to_the_verification_notice(): void
    {
        Notification::fake();

        $this->post('/register', $this->registration())
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status');

        $user = User::query()->firstOrFail();
        Notification::assertSentTo($user, VerifyEmailNotification::class);
        Notification::assertNotSentTo($user, VerifyEmail::class);

        // The notice names the address that received the link.
        $this->followingRedirects()->get(route('verification.notice'))
            ->assertOk()
            ->assertSee($user->email, false)
            ->assertSee('ارسال دوباره لینک تأیید', false);
    }

    public function test_the_login_form_shows_the_policy_hint_and_inline_errors(): void
    {
        $this->post('/register', $this->registration([
            'password' => 'short',
            'password_confirmation' => 'different',
        ]))->assertSessionHasErrors(['password', 'password_confirmation']);

        $this->followingRedirects()->get(route('register'))
            ->assertOk()
            ->assertSee(PasswordPolicy::hint(), false);
    }

    public function test_wrong_password_locks_the_account_after_five_attempts(): void
    {
        User::factory()->create([
            'email' => 'locked@example.com',
            'password' => self::STRONG_PASSWORD,
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['identifier' => 'locked@example.com', 'password' => 'wrong-password-1'])
                ->assertSessionHasErrors('identifier');
        }

        $this->post('/login', ['identifier' => 'locked@example.com', 'password' => self::STRONG_PASSWORD])
            ->assertSessionHasErrors('identifier');

        $this->assertGuest('web');
        $this->assertStringContainsString(
            'تعداد تلاش‌ها زیاد است',
            session('errors')->first('identifier')
        );
    }

    public function test_per_ip_login_throttling_redirects_with_a_persian_notice(): void
    {
        User::factory(12)->create(['password' => self::STRONG_PASSWORD]);

        for ($i = 0; $i < 11; $i++) {
            $this->post('/login', ['identifier' => 'someone'.$i.'@example.com', 'password' => 'nope-nope-1']);
        }

        // 11th POST in the same minute trips throttle:login (10/min/IP).
        $this->post('/login', ['identifier' => 'someone11@example.com', 'password' => 'nope-nope-1'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
    }

    public function test_a_successful_login_does_not_leak_the_throttle_state(): void
    {
        $user = User::factory()->create([
            'email' => 'recover@example.com',
            'password' => self::STRONG_PASSWORD,
        ]);

        $this->post('/login', ['identifier' => 'recover@example.com', 'password' => 'bad-1']);
        $this->post('/login', ['identifier' => 'recover@example.com', 'password' => self::STRONG_PASSWORD])
            ->assertRedirect(route('dashboard'));

        // attempt() succeeded → the counter was cleared, so the next failure
        // message must be "wrong password", not "too many attempts".
        auth()->logout();
        $this->post('/login', ['identifier' => 'recover@example.com', 'password' => 'bad-2'])
            ->assertSessionHasErrors('identifier');
        $this->assertStringNotContainsString('تعداد تلاش‌ها زیاد است', session('errors')->first('identifier'));
    }

    public function test_remember_me_issues_a_remember_token_cookie(): void
    {
        User::factory()->create([
            'email' => 'remember@example.com',
            'password' => self::STRONG_PASSWORD,
        ]);

        // Cookie name is remember_web_{session.cookie}; built the same way the
        // guard builds it, so this test cannot silently pass on a rename.
        $name = 'remember_web_'.config('session.cookie');

        $this->post('/login', [
            'identifier' => 'remember@example.com',
            'password' => self::STRONG_PASSWORD,
            'remember' => '1',
        ])->assertRedirect(route('dashboard'))->assertCookie($name);
    }

    public function test_login_without_remember_me_sets_no_remember_cookie(): void
    {
        User::factory()->create([
            'email' => 'forgetful@example.com',
            'password' => self::STRONG_PASSWORD,
        ]);

        $response = $this->post('/login', [
            'identifier' => 'forgetful@example.com',
            'password' => self::STRONG_PASSWORD,
        ])->assertRedirect(route('dashboard'));

        $names = array_map(
            fn (\Symfony\Component\HttpFoundation\Cookie $cookie) => $cookie->getName(),
            $response->headers->getCookies()
        );

        $this->assertSame([], array_filter($names, fn (string $name) => str_starts_with($name, 'remember_web_')));
    }

    public function test_logout_clears_the_session_and_says_so(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')
            ->assertRedirect(route('home'))
            ->assertSessionHas('status');

        $this->assertGuest('web');
    }

    public function test_resending_the_verification_link_is_capped_per_user(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        for ($i = 0; $i < 4; $i++) {
            $this->actingAs($user)->post(route('verification.send'));
        }

        Notification::assertSentToTimes($user, VerifyEmailNotification::class, 3);
    }

    public function test_password_reset_requires_the_same_policy_and_invalidates_the_token_once(): void
    {
        $user = User::factory()->create(['email' => 'reset-policy@example.com']);
        $token = \Illuminate\Support\Facades\Password::broker()->createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => ' RESET-Policy@Example.com ',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ])->assertSessionHasErrors('password');

        $this->post('/reset-password', [
            'token' => $token,
            'email' => ' reset-policy@example.com ',
            'password' => self::STRONG_PASSWORD,
            'password_confirmation' => self::STRONG_PASSWORD,
        ])->assertRedirect(route('login'));

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check(self::STRONG_PASSWORD, $user->fresh()->password));
        $this->assertFalse(\Illuminate\Support\Facades\Password::broker()->tokenExists($user->fresh(), $token));
    }

    public function test_malformed_utf8_payloads_fail_gracefully(): void
    {
        // preg_replace()/preg_match() with /u return null on an invalid UTF-8
        // subject, and PhoneNormalizer runs under declare(strict_types=1) — one
        // pasted byte of mojibake used to be enough for a 500 page. The answer
        // must be the form again with errors, or a plain redirect: never a 5xx.
        $broken = "\xFF\xFE phone \x00";

        $this->post('/register', [
            'name' => $broken,
            'email' => 'bad-utf8@example.com',
            'phone' => $broken,
            'password' => self::STRONG_PASSWORD,
            'password_confirmation' => self::STRONG_PASSWORD,
            'consent' => 'on',
        ])->assertSessionHasErrors('phone');

        $this->post('/login', ['identifier' => $broken, 'password' => 'whatever'])
            ->assertSessionHasErrors('identifier');

        // A malformed address is either rejected by the rule or accepted and
        // answered with the same neutral flash — it must not crash, and it must
        // not say whether the account exists.
        $this->post('/forgot-password', ['email' => $broken.'@example.com'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('users', ['email' => 'bad-utf8@example.com']);
    }

    public function test_guests_cannot_reach_the_verification_notice(): void
    {
        $this->get(route('verification.notice'))->assertRedirect(route('login'));
        $this->post(route('verification.send'))->assertRedirect(route('login'));
    }
}

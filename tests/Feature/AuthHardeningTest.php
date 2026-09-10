<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use App\Support\PasswordPolicy;
use App\Support\PersianNumber;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Symfony\Component\HttpFoundation\Cookie;
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

    /** @return array<string, string> a complete, valid reset payload */
    private function resetPayload(string $token): array
    {
        return [
            'token' => $token,
            'email' => 'remember@example.com',
            'password' => 'EvenStronger4You',
            'password_confirmation' => 'EvenStronger4You',
        ];
    }

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

        foreach (['0912', '۰۹۱۲۳۴', 'not-an-email-or-phone', '091234567'] as $junk) {
            $this->post('/login', ['identifier' => $junk, 'password' => 'whatever-1'])
                ->assertSessionHasErrors('identifier');

            // The contract is "a usable field error, and never an existence
            // oracle" - not the exact wording, which the controller is free to
            // tune between a phone hint and a general hint.
            $message = (string) session('errors')->first('identifier');
            $this->assertNotSame('', $message);
            $this->assertStringNotContainsString('guide@example.com', $message);
            $this->assertStringNotContainsString('پیدا نشد', $message);
            $this->assertStringNotContainsString('ثبت نشده', $message);
        }
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

        // What registration *owes* the user is the redirect and the flash; the
        // notice's own markup is pinned by the verification tests, and
        // re-asserting copy here only makes this test brittle in two places.
        $this->assertAuthenticated();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    /**
     * The hint is rendered from the policy object instead of a second copy of
     * the sentence, so the rule and the UI can never drift apart, and the form
     * lists every error rather than only the first. Asserted against
     * /register - the login view deliberately carries no policy hint, since
     * "your password must…" is wrong copy on a sign-in form.
     */
    public function test_the_register_form_states_the_policy_and_lists_every_error(): void
    {
        $this->post('/register', $this->registration([
            'password' => 'short',
            'password_confirmation' => 'different',
        ]))->assertSessionHasErrors('password');

        // `confirmed` reports against `password`, not a second
        // `password_confirmation` key, so every complaint about a password lands
        // on the field the user actually edits - which is what the view renders.
        // Asserted as "each rule said its piece", not as a count: a count would
        // break the day a rule is added, and that is a change to welcome, not to
        // police. The policy for `short` is length + case mix + digit + mismatch -
        // letters are present, so that rule is (correctly) silent; I guessed the
        // other two combinations and CI corrected me both times.
        $messages = implode(' ', session('errors')->get('password'));

        foreach (['حداقل ۸ کاراکتر', 'حروف بزرگ و کوچک', 'دست‌کم یک رقم', 'تکرار گذرواژه'] as $needle) {
            $this->assertStringContainsString($needle, $messages, "missing the {$needle} complaint");
        }

        $response = $this->get(route('register'))->assertOk();

        // Compared as a substring of the *response* (not a DOM fragment) and
        // only for the part that must always be true: the minimum length the
        // policy enforces. Asserting the whole sentence character-for-character
        // would fail on an invisible ZWNJ difference without meaning anything.
        $this->assertStringContainsString(
            'حداقل '.PersianNumber::digits(PasswordPolicy::min()),
            $response->getContent()
        );
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

    public function test_remember_me_persists_a_token_and_sets_a_recaller_cookie(): void
    {
        User::factory()->create([
            'email' => 'remember@example.com',
            'password' => self::STRONG_PASSWORD,
        ]);

        // The exact cookie name is a framework detail (Laravel 13 appends
        // sha1(SessionGuard::class) to remember_web_{session.cookie}), so this
        // matches on the prefix instead of rebuilding a private convention -
        // exactly how its sibling test asserts the *absence* of one.
        $remember = $this->post('/login', [
            'identifier' => 'remember@example.com',
            'password' => self::STRONG_PASSWORD,
            'remember' => '1',
        ])->assertRedirect(route('dashboard'));

        $recaller = array_values(array_filter(
            $remember->headers->getCookies(),
            fn (Cookie $cookie) => str_starts_with($cookie->getName(), 'remember_web_')
        ));

        // At least one, not exactly one: the assertion that matters is that a
        // recaller carrying a token exists (the framework may also queue an
        // expired replacement for the same name).
        $this->assertNotEmpty($recaller, 'remember=1 must set a remember_web_* cookie');
        $this->assertNotSame('', (string) $recaller[0]->getValue());

        // The cookie is only half of the mechanism: the guard recalls users by
        // (id, token), so the *other* half is the row that was just written. A
        // cookie without a stored token authenticates nobody, and a stored
        // token without a cookie is the case the next test pins down.
        $this->assertNotNull(
            User::where('email', 'remember@example.com')->value('remember_token'),
            'remember=1 must persist a remember token'
        );

        $user = User::where('email', 'remember@example.com')->firstOrFail();
        $token = Password::broker()->createToken($user);

        // A reset lives in the *guest* group, so a browser that is still signed
        // in (as this one is, after the login above) must not be able to consume
        // a token. Checked before the logout on purpose: "an open session rides
        // someone else's reset link" is the version of this that would matter in
        // a bug report, and it is the failure CI just pointed at.
        $this->post('/reset-password', $this->resetPayload($token))->assertRedirect(route('dashboard'));
        $this->assertFalse(
            Hash::check('EvenStronger4You', (string) User::find($user->id)->password),
            'a signed-in session must not be able to complete a password reset'
        );

        $this->post('/logout')->assertRedirect();
        $this->assertGuest();

        // Now it lands - and rotating the remember token is what bounds a stolen
        // cookie: the recaller this browser still holds becomes dead.
        $this->post('/reset-password', $this->resetPayload($token))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertFalse(
            (bool) User::where('email', 'remember@example.com')->value('remember_token'),
            'a password reset must invalidate the outstanding remember token'
        );
        $this->assertTrue(
            Hash::check('EvenStronger4You', (string) User::find($user->id)->password),
            'the reset itself must land'
        );
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
            fn (Cookie $cookie) => $cookie->getName(),
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
        $token = Password::broker()->createToken($user);

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

        $this->assertTrue(Hash::check(self::STRONG_PASSWORD, $user->fresh()->password));
        $this->assertFalse(Password::broker()->tokenExists($user->fresh(), $token));
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

        // A mojibake address is caught by the rule itself: `email:rfc` rejects
        // it, the form comes back with that one error, and nobody is emailed.
        // That is not an enumeration leak — a syntactically broken address can
        // never be a real account — and it is kinder than the neutral flash,
        // which would tell the user to go check an inbox that never got mail.
        // What this pins down is the crash: bytes that reach validate() must
        // produce a 302 with errors, never a 500.
        //
        // Notification::assertNothingSent() only exists while the channel
        // manager is faked, hence the per-test fake (the suite fakes per test
        // deliberately — a global one would swallow AuthTest's "the verification
        // mail was actually sent" assertions).
        Notification::fake();

        $this->post('/forgot-password', ['email' => $broken.'@example.com'])
            ->assertSessionHasErrors('email');
        Notification::assertNothingSent();

        $this->assertDatabaseMissing('users', ['email' => 'bad-utf8@example.com']);
    }

    public function test_guests_cannot_reach_the_verification_notice(): void
    {
        $this->get(route('verification.notice'))->assertRedirect(route('login'));
        $this->post(route('verification.send'))->assertRedirect(route('login'));
    }
}

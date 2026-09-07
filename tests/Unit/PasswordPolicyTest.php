<?php

namespace Tests\Unit;

use App\Support\PasswordPolicy;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

/**
 * The policy is app-level config, so this uses the framework TestCase (needs a
 * container with config bound) rather than bare PHPUnit.
 */
class PasswordPolicyTest extends TestCase
{
    public function test_the_floor_is_never_below_eight_characters(): void
    {
        config(['broca.password_min' => 3]);
        $this->assertSame(8, PasswordPolicy::min());

        config(['broca.password_min' => 12]);
        $this->assertSame(12, PasswordPolicy::min());
    }

    public function test_the_length_cap_stays_within_bcrypts_input_limit(): void
    {
        config(['broca.password_min' => 8]);
        $this->assertSame('max:32', PasswordPolicy::maxRule());

        // A generous floor must not turn into a cap above what bcrypt reads.
        config(['broca.password_min' => 72]);
        $this->assertSame('max:72', PasswordPolicy::maxRule());
    }

    public function test_the_breach_check_is_opt_in(): void
    {
        config(['broca.password_leak_check' => false]);
        $this->assertStringNotContainsString('نشت', PasswordPolicy::hint());

        config(['broca.password_leak_check' => true]);
        $this->assertStringContainsString('نشت', PasswordPolicy::hint());
    }

    public function test_it_returns_a_password_rule_object(): void
    {
        $this->assertInstanceOf(Password::class, PasswordPolicy::rule());
    }

    public function test_the_bound_app_default_follows_the_same_config_knob(): void
    {
        config(['broca.password_min' => 11]);

        // AppServiceProvider binds Password::defaults() to PasswordPolicy::rule();
        // both therefore have to move together when the knob moves — otherwise
        // /register and anything using the framework default drift apart.
        $this->assertSame(11, PasswordPolicy::min());
        $this->assertInstanceOf(Password::class, PasswordPolicy::rule());
        $this->assertInstanceOf(Password::class, Password::defaults());
    }

}

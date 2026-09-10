<?php

namespace App\Http\Requests;

use App\Rules\IranianMobile;
use App\Support\PasswordPolicy;
use App\Support\PersianNumber;
use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Registration input. Normalization happens BEFORE validation so the
 * uniqueness rules compare against exactly what will be stored — a
 * "+98 ۹۱۲..." duplicate of an existing 0912... number is caught as 422,
 * never as a DB-level 500.
 *
 * The same normalization is applied to `email`: browsers (and password
 * managers) routinely autofill with a trailing space or a capitalized
 * address, and an account stored as "Ali@Example.com " would never be found
 * by the login form's lookup. Everything the credentials are matched on is
 * therefore canonicalized here, once, for both validation and storage.
 */
class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $phone = $this->stringInput('phone');

        // Persian/Arabic digits first: a phone typed as ۰۹۱۲… on an iOS
        // keyboard must pass both the rule and the unique lookup. Anything
        // unrecognizable is merged back untouched, so the field rules — not a
        // normalizer — own the error message.
        $normalizedPhone = PhoneNormalizer::isValid($phone)
            ? PhoneNormalizer::normalize($phone)
            : $phone;

        $email = $this->stringInput('email');

        $this->merge([
            'name' => trim($this->stringInput('name')),
            // mb_strtolower() only rewrites valid UTF-8; the guard keeps a
            // mangled byte sequence from arriving at the unique lookup at all.
            'email' => preg_match('//u', $email) === 1 ? mb_strtolower($email) : $email,
            'phone' => $normalizedPhone,
        ]);
    }

    /**
     * Cast to a trimmed string, or an empty string for anything that is not a
     * string. `(string) ['a']` throws in PHP 8, so a hostile array payload
     * (`name[]=x`) would turn a validation failure into a 500 — the field
     * rules then reject the empty string as "required" like any other input.
     */
    private function stringInput(string $key): string
    {
        $value = $this->input($key);

        return is_string($value) ? trim($value) : '';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone', new IranianMobile],
            // The policy itself is PasswordPolicy::rule() — shared with
            // /reset-password and bound as Password::defaults(), so register
            // and reset can never disagree about what counts as strong.
            'password' => ['required', 'string', 'confirmed', PasswordPolicy::maxRule(), PasswordPolicy::rule()],
            'consent' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.email' => 'یک آدرس ایمیل معتبر وارد کنید (مانند name@example.com).',
            'phone.unique' => 'این شمارهٔ همراه قبلاً ثبت شده است.',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
            'email.max' => 'آدرس ایمیل نمی‌تواند بیشتر از ۲۵۵ کاراکتر باشد.',
            'password.confirmed' => 'تکرار گذرواژه با خود آن مطابقت ندارد.',
            /*
             * The Password rule's own messages are English and arrive with the
             * rule *name* as the key, so `attributes()` cannot translate them -
             * without these, a real signup failure shows a Persian field name
             * next to "validation.password.mixed". Kept in sync with
             * PasswordPolicy::rule(): one rule, one copy per complaint.
             */
            'password.min' => 'گذرواژه باید حداقل '.PersianNumber::digits(PasswordPolicy::min()).' کاراکتر باشد.',
            'password.max' => 'گذرواژه نمی‌تواند بیشتر از '.PersianNumber::digits(PasswordPolicy::BCRYPT_INPUT_LIMIT).' کاراکتر باشد (سقف هش رمز).',
            'password.mixed' => 'گذرواژه باید شامل حروف بزرگ و کوچک باشد.',
            'password.numbers' => 'گذرواژه باید دست‌کم یک رقم داشته باشد.',
            'password.symbols' => 'گذرواژه باید دست‌کم یک نماد داشته باشد.',
            'password.letters' => 'گذرواژه باید دست‌کم یک حرف داشته باشد.',
            'consent.accepted' => 'برای ساخت حساب، پذیرش شرایط و بیانیهٔ پزشکی لازم است.',
        ];
    }

    /**
     * Persian field names for the framework-generated messages (the password
     * policy message in particular, which we do not hand-write).
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'نام',
            'email' => 'ایمیل',
            'phone' => 'شمارهٔ همراه',
            'password' => 'گذرواژه',
            'consent' => 'پذیرش شرایط',
        ];
    }
}

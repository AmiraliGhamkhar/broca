<?php

namespace App\Http\Requests;

use App\Rules\IranianMobile;
use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Registration input. Normalization happens BEFORE validation so the
 * uniqueness rules compare against exactly what will be stored — a
 * "+98 ۹۱۲..." duplicate of an existing 0912... number is caught as 422,
 * never as a DB-level 500.
 */
class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $phone = (string) $this->input('phone');

        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'phone' => PhoneNormalizer::isValid($phone) ? PhoneNormalizer::normalize($phone) : $phone,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', new IranianMobile, 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'consent' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.unique' => 'این شمارهٔ همراه قبلاً ثبت شده است.',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
            'consent.accepted' => 'برای ساخت حساب، پذیرش شرایط و بیانیهٔ پزشکی لازم است.',
        ];
    }
}

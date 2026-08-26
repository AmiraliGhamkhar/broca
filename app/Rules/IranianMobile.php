<?php

namespace App\Rules;

use App\Support\PhoneNormalizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates Iranian mobile numbers, accepting Persian digits, +98 / 0098
 * prefixes and common separators. Use PhoneNormalizer::normalize() on the
 * validated value before storing.
 */
class IranianMobile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! PhoneNormalizer::isValid($value)) {
            $fail(':attribute باید یک شمارهٔ همراه ایرانی معتبر (مانند 09123456789) باشد.');
        }
    }
}

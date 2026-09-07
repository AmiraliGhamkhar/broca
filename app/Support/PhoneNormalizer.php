<?php

namespace App\Support;

use InvalidArgumentException;

final class PhoneNormalizer
{
    public static function normalize(string $phone): string
    {
        /*
         * preg_replace() with /u returns null (not false, not the input) when
         * the subject is not valid UTF-8 — paste mojibake or a truncated
         * multi-byte sequence and this is enough to take the register and login
         * forms down with a TypeError. The null coalescing operators are the
         * whole fix; an invalid payload then fails the format check below.
         */
        $phone = preg_replace('/[\s\-().]/u', '', trim($phone)) ?? '';
        $phone = strtr($phone, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9']);

        if (str_starts_with($phone, '+98')) {
            $phone = '0'.substr($phone, 3);
        } elseif (str_starts_with($phone, '0098')) {
            $phone = '0'.substr($phone, 4);
        }

        if (preg_match('/^09\d{9}$/', $phone) !== 1) {
            throw new InvalidArgumentException('شمارهٔ همراه معتبر نیست.');
        }

        return $phone;
    }

    /**
     * Non-throwing variant for validation rules.
     */
    public static function isValid(string $phone): bool
    {
        try {
            self::normalize($phone);
        } catch (InvalidArgumentException) {
            return false;
        }

        return true;
    }
}

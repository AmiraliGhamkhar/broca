<?php

namespace App\Support;

/**
 * Latin → Persian digit swap for short inline numbers (durations, percentages)
 * that sit inside Persian sentences. Amounts keep `number_format()` digits and
 * are wrapped in a `dir="ltr"` node instead, because grouping commas must stay
 * in reading order.
 */
class PersianNumber
{
    private const DIGITS = [
        '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
        '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
    ];

    public static function digits(int|string|null $value): string
    {
        return strtr((string) ($value ?? '0'), self::DIGITS);
    }
}

<?php

namespace Tests\Unit;

use App\Support\PersianNumber;
use PHPUnit\Framework\TestCase;

class PersianNumberTest extends TestCase
{
    public function test_it_converts_latin_digits_to_persian(): void
    {
        $this->assertSame('۳', PersianNumber::digits(3));
        $this->assertSame('۱۲', PersianNumber::digits(12));
        $this->assertSame('۲۶', PersianNumber::digits('26'));
        $this->assertSame('۰', PersianNumber::digits(null));
    }

    public function test_it_leaves_non_digit_characters_untouched(): void
    {
        $this->assertSame('۱,۲۳۴ ماه', PersianNumber::digits('1,234 ماه'));
    }
}

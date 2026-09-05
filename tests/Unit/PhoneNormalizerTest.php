<?php

namespace Tests\Unit;

use App\Support\PhoneNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneNormalizerTest extends TestCase
{
    /** @return array<string, array{0: string, 1: string}> */
    public static function validProvider(): array
    {
        return [
            'plain' => ['09123456789', '09123456789'],
            'spaced' => ['0912 345 6789', '09123456789'],
            'dashed' => ['0912-345-6789', '09123456789'],
            'plus 98' => ['+989123456789', '09123456789'],
            'double zero 98' => ['00989123456789', '09123456789'],
            'persian digits' => ['۰۹۱۲۳۴۵۶۷۸۹', '09123456789'],
            'persian digits with separators' => ['۰۹۱۲-۳۴۵-۶۷۸۹', '09123456789'],
        ];
    }

    /** @return array<string, array{0: string}> */
    public static function invalidProvider(): array
    {
        return [
            'too short' => ['0912345678'],
            'too long' => ['091234567890'],
            'wrong prefix' => ['10123456789'],
            'letters' => ['abcdefghijk'],
            'empty' => [''],
            'landline' => ['02112345678'],
        ];
    }

    #[DataProvider('validProvider')]
    public function test_normalizes_valid_numbers(string $input, string $expected): void
    {
        $this->assertSame($expected, PhoneNormalizer::normalize($input));
    }

    #[DataProvider('invalidProvider')]
    public function test_rejects_invalid_numbers(string $input): void
    {
        $this->assertFalse(PhoneNormalizer::isValid($input));
        $this->expectException(\InvalidArgumentException::class);
        PhoneNormalizer::normalize($input);
    }
}

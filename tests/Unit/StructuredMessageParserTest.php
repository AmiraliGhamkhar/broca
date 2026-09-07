<?php

namespace Tests\Unit;

use App\Support\StructuredMessageParser;
use PHPUnit\Framework\TestCase;

class StructuredMessageParserTest extends TestCase
{
    public function test_it_parses_scalar_lines_and_multiline_blocks(): void
    {
        $parsed = StructuredMessageParser::parse(implode("\n", [
            'title: نمونه',
            'status = draft',
            '[content]',
            'خط اول',
            'خط دوم',
            '[/content]',
        ]));

        $this->assertSame('نمونه', $parsed['title']);
        $this->assertSame('draft', $parsed['status']);
        $this->assertSame("خط اول\nخط دوم", $parsed['content']);
    }

    public function test_it_parses_persian_rtl_keys_blocks_and_values(): void
    {
        $parsed = StructuredMessageParser::parse(implode("\n", [
            'عنوان: مقاله فارسی',
            'وضعیت: در بازبینی',
            'فعال: بله',
            '[محتوا]',
            'خط نخست',
            '[/محتوا]',
        ]));

        $this->assertSame('مقاله فارسی', $parsed['title']);
        $this->assertSame('in_review', $parsed['status']);
        $this->assertSame('بله', $parsed['is_active']);
        $this->assertSame('خط نخست', $parsed['content']);
    }
}

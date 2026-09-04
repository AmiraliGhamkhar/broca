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
}

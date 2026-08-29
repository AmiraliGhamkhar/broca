<?php

namespace Tests\Feature;

use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression: JSON-LD blocks must survive a `</script>` payload inside
 * admin-authored content without offering an XSS breakout.
 */
class JsonLdEscapingTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_json_ld_escapes_script_breakout_payloads(): void
    {
        $course = Course::factory()->published()->create([
            'title' => '</script><script>alert("xss")</script>',
            'excerpt' => '</script><script>alert("xss")</script>',
        ]);

        $response = $this->get(route('courses.show', $course));

        $response->assertOk();

        $content = $response->getContent();

        // The raw payload must not survive anywhere in the document.
        $this->assertStringNotContainsString('</script><script>alert', $content);

        // The escaped form must be present inside the JSON-LD block.
        $this->assertStringContainsString('\u003C', $content);
    }

    public function test_layout_json_ld_is_present_on_the_home_page(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('application/ld+json');
    }

    public function test_course_json_ld_remains_valid_json_after_escaping(): void
    {
        $title = '</script><script>alert(1)</script>';
        $course = Course::factory()->published()->create([
            'title' => $title,
            'excerpt' => 'خلاصه دوره',
        ]);

        $content = $this->get(route('courses.show', $course))->assertOk()->getContent();

        preg_match_all('/<script type="application\/ld\+json">\s*(.*?)\s*<\/script>/s', $content, $matches);
        $this->assertNotEmpty($matches[0], 'at least one JSON-LD block must be present');

        $courseBlockFound = false;
        foreach ($matches[1] as $block) {
            // Every block must be parseable JSON even with a hostile title.
            $decoded = json_decode($block, true);
            $this->assertIsArray($decoded);

            if (($decoded['@type'] ?? null) === 'Course') {
                $this->assertSame($title, $decoded['name']);
                $this->assertStringNotContainsString('</script>', $block, 'no raw breakout may survive inside the block');
                $courseBlockFound = true;
            }
        }

        $this->assertTrue($courseBlockFound, 'Course JSON-LD block must be present');
    }
}

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
}

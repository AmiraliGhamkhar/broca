<?php

namespace Tests\Feature;

use App\Models\Contributor;
use App\Models\Course;
use App\Models\Note;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Free-badge honesty: every learner-facing "رایگان" badge must reflect the
 * EFFECTIVE entitlement (flag AND global cap), not the bare designation
 * flag — otherwise the page promises access the playback/download endpoints
 * will 403 on.
 */
class EntitlementBadgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The cap check caches counts for 300 s — never let one test's
        // state leak into the next (array cache is process-wide).
        Cache::flush();
    }

    public function test_course_page_badges_follow_the_global_cap(): void
    {
        $course = Course::factory()->published()->create();
        Video::factory()->count(2)->for($course)->create(['is_free_designated' => true]);

        $content = $this->get(route('courses.show', $course))->assertOk()->getContent();
        $this->assertSame(2, substr_count($content, '>رایگان</span>'));

        // A third designation pushes the count past the cap → fail-closed:
        // NO video may present itself as free, or the lock would be bypassed.
        Video::factory()->for($course)->create(['is_free_designated' => true]);
        Cache::flush(); // drop the 300 s cap cache

        $content = $this->get(route('courses.show', $course->fresh()))->assertOk()->getContent();
        $this->assertSame(0, substr_count($content, '>رایگان</span>'));
    }

    public function test_course_page_badges_only_appear_on_free_designated_items(): void
    {
        $course = Course::factory()->published()->create();
        Video::factory()->count(2)->for($course)->create();

        $content = $this->get(route('courses.show', $course))->assertOk()->getContent();
        $this->assertSame(0, substr_count($content, '>رایگان</span>'));
    }

    public function test_dashboard_badges_follow_the_global_cap(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->published()->create();
        $user->enrollments()->create(['course_id' => $course->id, 'enrolled_at' => now()]);

        Video::factory()->count(2)->for($course)->create(['is_free_designated' => true]);

        $content = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();
        $this->assertSame(2, substr_count($content, '>رایگان</span>'));

        // Past the cap → every row degrades to "ویژه".
        Video::factory()->for($course)->create(['is_free_designated' => true]);
        Cache::flush(); // drop the 300 s cap cache

        $content = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();
        $this->assertSame(0, substr_count($content, '>رایگان</span>'));
        $this->assertSame(3, substr_count($content, '>ویژه</span>'));
    }

    public function test_plans_page_copy_describes_a_global_not_per_course_cap(): void
    {
        $this->get(route('plans'))
            ->assertOk()
            ->assertSee('در کل آرشیو (همه دوره‌ها روی هم)')
            ->assertSee('تا ۲ ویدیوی منتخب، در کل آرشیو و همه دوره‌ها');
    }

    public function test_free_cap_counts_only_items_on_published_courses(): void
    {
        // Regression (2026-09-05): the cap used to count free-designated
        // items regardless of whether their course was still published, so
        // invisible content (archived/soft-deleted courses) silently ate
        // the quota and failed closed for everyone.
        $visible = Course::factory()->published()->create();
        $hidden = Course::factory()->create(); // draft

        Video::factory()->count(2)->for($visible)->create(['is_free_designated' => true]);
        Video::factory()->count(2)->for($hidden)->create(['is_free_designated' => true]);

        // Two free videos on a VISIBLE course: within the cap (2) — the two
        // videos on the draft course must not count.
        $content = $this->get(route('courses.show', $visible))->assertOk()->getContent();
        $this->assertSame(2, substr_count($content, '>رایگان</span>'));

        // Archiving the hidden course changes nothing for visible content.
        $hidden->update(['status' => 'archived']);
        Cache::flush();

        $content = $this->get(route('courses.show', $visible->fresh()))->assertOk()->getContent();
        $this->assertSame(2, substr_count($content, '>رایگان</span>'));
    }

    public function test_free_cap_releases_quota_when_a_course_is_soft_deleted(): void
    {
        $visible = Course::factory()->published()->create();
        $hidden = Course::factory()->published()->create();

        Video::factory()->count(2)->for($visible)->create(['is_free_designated' => true]);
        Video::factory()->count(2)->for($hidden)->create(['is_free_designated' => true]);

        // Soft-deleting the second course drops its (still-published)
        // videos out of the count → the visible course stays within cap.
        $hidden->delete();
        Cache::flush();

        $content = $this->get(route('courses.show', $visible->fresh()))->assertOk()->getContent();
        $this->assertSame(2, substr_count($content, '>رایگان</span>'));
    }

    public function test_note_badge_uses_the_effective_entitlement(): void
    {
        $course = Course::factory()->published()->create();
        $contributor = Contributor::factory()->create();
        Note::create([
            'course_id' => $course->id,
            'title' => 'جزوه نمونه',
            'slug' => 'sample-note',
            'description' => 'توضیح',
            'sort_order' => 1,
            'storage_disk' => 'local',
            'storage_key' => 'notes/electrophysiology-summary.pdf',
            'mime_type' => 'application/pdf',
            'is_free_designated' => true,
            'status' => 'published',
            'published_at' => now()->subDay(),
            'author_id' => $contributor->id,
            'reviewer_id' => $contributor->id,
        ]);

        $content = $this->get(route('courses.show', $course))->assertOk()->getContent();
        $this->assertSame(1, substr_count($content, '>رایگان</span>'));

        // A second free-designated note exceeds the note cap (1) → none free.
        Note::create([
            'course_id' => $course->id,
            'title' => 'جزوه دوم',
            'slug' => 'sample-note-2',
            'description' => 'توضیح',
            'sort_order' => 2,
            'storage_disk' => 'local',
            'storage_key' => 'notes/neuroanatomy-broca-atlas.pdf',
            'mime_type' => 'application/pdf',
            'is_free_designated' => true,
            'status' => 'published',
            'published_at' => now()->subDay(),
            'author_id' => $contributor->id,
            'reviewer_id' => $contributor->id,
        ]);

        Cache::flush(); // drop the 300 s cap cache
        $content = $this->get(route('courses.show', $course->fresh()))->assertOk()->getContent();
        $this->assertSame(0, substr_count($content, '>رایگان</span>'));
    }
}

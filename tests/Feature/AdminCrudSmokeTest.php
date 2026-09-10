<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Contributor;
use App\Models\Course;
use App\Models\FlashcardDeck;
use App\Models\Note;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke coverage for the admin CRUD index pages (the panel is in scope for
 * every design and audit change — a broken index breaks everything else).
 */
class AdminCrudSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_the_hero_accessibility_text(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAsAdmin($admin)->patch(route('admin.appearance.update'), [
            'hero_image_alt' => 'تصویر کلاس آموزش پزشکی',
        ])->assertRedirect();

        $this->assertDatabaseHas('site_settings', [
            'id' => 1,
            'hero_image_alt' => 'تصویر کلاس آموزش پزشکی',
        ]);
    }

    public function test_admin_index_pages_render_with_seeded_data(): void
    {
        $admin = User::factory()->admin()->create();
        $subject = Subject::factory()->create();
        $course = Course::factory()->published()->create(['subject_id' => $subject->id]);
        Video::factory()->count(3)->for($course)->create();
        Quiz::factory()->count(2)->for($course)->create();
        FlashcardDeck::factory()->count(2)->for($course)->create();
        BlogPost::factory()->published()->create();

        $contributor = Contributor::factory()->create();
        Note::create([
            'course_id' => $course->id,
            'title' => 'جزوه نمونه',
            'slug' => 'smoke-note',
            'description' => 'توضیح',
            'sort_order' => 1,
            'storage_disk' => 'local',
            'storage_key' => 'notes/electrophysiology-summary.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'author_id' => $contributor->id,
            'reviewer_id' => $contributor->id,
        ]);

        $routes = [
            'admin.courses.index',
            'admin.subjects.index',
            'admin.videos.index',
            'admin.notes.index',
            'admin.quizzes.index',
            'admin.flashcards.index',
            'admin.blogs.index',
            'admin.appearance.edit',
        ];

        foreach ($routes as $route) {
            $this->actingAsAdmin($admin)->get(route($route))->assertOk();
        }
    }
}

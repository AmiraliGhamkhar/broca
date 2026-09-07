<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Course;
use App\Models\FlashcardDeck;
use App\Models\Note;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\User;
use App\Models\Video;
use Tests\Concerns\WasmSafeRefreshDatabase;
use Tests\TestCase;

/**
 * Smoke coverage for the admin CRUD index pages (the panel is in scope for
 * every design and audit change — a broken index breaks everything else).
 */
class AdminCrudSmokeTest extends TestCase
{
    use WasmSafeRefreshDatabase;

    public function test_admin_index_pages_render_with_seeded_data(): void
    {
        $admin = User::factory()->admin()->create();
        $subject = Subject::factory()->create();
        $course = Course::factory()->published()->create(['subject_id' => $subject->id]);
        Video::factory()->count(3)->for($course)->create();
        Quiz::factory()->count(2)->for($course)->create();
        FlashcardDeck::factory()->count(2)->for($course)->create();
        BlogPost::factory()->published()->create();

        $contributor = \App\Models\Contributor::factory()->create();
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
        ];

        foreach ($routes as $route) {
            $this->actingAsAdmin($admin)->get(route($route))->assertOk();
        }
    }
}

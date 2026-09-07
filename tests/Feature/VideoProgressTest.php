<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoProgress;
use Tests\Concerns\WasmSafeRefreshDatabase;
use Tests\TestCase;

class VideoProgressTest extends TestCase
{
    use WasmSafeRefreshDatabase;

    private User $user;

    private Video $video;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $course = Course::factory()->published()->create();
        $this->video = Video::factory()->for($course)->create([
            'is_free_designated' => true,
            'duration_seconds' => 1000,
        ]);

        $this->user->enrollments()->create([
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);
    }

    public function test_progress_is_recorded_and_clamped_to_duration(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('videos.progress', $this->video), ['watched_seconds' => 500])
            ->assertOk()
            ->assertJson(['watched_percent' => 50, 'completed' => false]);

        // Watched more than the video's duration → clamped, marked complete.
        $this->actingAs($this->user)
            ->postJson(route('videos.progress', $this->video), ['watched_seconds' => 999999])
            ->assertOk()
            ->assertJson(['watched_percent' => 100, 'completed' => true]);

        $this->assertDatabaseHas('video_progress', [
            'user_id' => $this->user->id,
            'video_id' => $this->video->id,
            'watched_percent' => 100,
        ]);
    }

    public function test_progress_never_regresses(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('videos.progress', $this->video), ['watched_seconds' => 800]);

        // A later stale report (e.g. seek back) must not lower the max.
        $this->actingAs($this->user)
            ->postJson(route('videos.progress', $this->video), ['watched_seconds' => 100])
            ->assertOk()
            ->assertJson(['watched_percent' => 80]);

        $this->assertSame(80, VideoProgress::first()->watched_percent);
    }

    public function test_completion_threshold_is_respected(): void
    {
        // Default threshold is 70%.
        $this->actingAs($this->user)
            ->postJson(route('videos.progress', $this->video), ['watched_seconds' => 690])
            ->assertOk()
            ->assertJson(['completed' => false]);

        $this->actingAs($this->user)
            ->postJson(route('videos.progress', $this->video), ['watched_seconds' => 750])
            ->assertOk()
            ->assertJson(['completed' => true]);
    }

    public function test_show_hydrates_the_player_with_existing_progress(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('videos.progress', $this->video), ['watched_seconds' => 500]);

        $course = $this->video->course;

        $this->actingAs($this->user)
            ->get(route('videos.show', [$course, $this->video]))
            ->assertOk()
            ->assertSee('videoPlayback(50')
            ->assertSee('false');
    }

    public function test_progress_requires_entitlement(): void
    {
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->postJson(route('videos.progress', $this->video), ['watched_seconds' => 100])
            ->assertForbidden();
    }
}

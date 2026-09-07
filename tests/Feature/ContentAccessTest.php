<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Video;
use Tests\Concerns\WasmSafeRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ContentAccessTest extends TestCase
{
    use WasmSafeRefreshDatabase;

    private User $user;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->user = User::factory()->create();

        $this->course = Course::factory()->published()->create();

        $this->user->enrollments()->create([
            'course_id' => $this->course->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);
    }

    public function test_free_designated_video_within_cap_is_accessible_without_subscription(): void
    {
        $video = Video::factory()->for($this->course)->create(['is_free_designated' => true]);

        $this->actingAs($this->user)
            ->get(route('videos.show', [$this->course, $video]))
            ->assertOk()
            ->assertSee($video->title);
    }

    public function test_paid_video_is_locked_without_subscription(): void
    {
        $video = Video::factory()->for($this->course)->create(['is_free_designated' => false]);

        $this->actingAs($this->user)
            ->get(route('videos.playback', $video))
            ->assertForbidden();
    }

    public function test_active_subscription_unlocks_paid_videos(): void
    {
        $video = Video::factory()->for($this->course)->create(['is_free_designated' => false]);

        $this->subscribe($this->user);

        $this->actingAs($this->user)
            ->get(route('videos.playback', $video))
            ->assertOk();
    }

    public function test_expired_subscription_does_not_unlock_paid_videos(): void
    {
        $video = Video::factory()->for($this->course)->create(['is_free_designated' => false]);

        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'activated_at' => now()->subMonths(2),
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subMonth(),
        ]);

        $this->actingAs($this->user)
            ->get(route('videos.playback', $video))
            ->assertForbidden();
    }

    public function test_not_enrolled_user_cannot_watch_even_free_designated_videos(): void
    {
        $video = Video::factory()->for($this->course)->create(['is_free_designated' => true]);
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->get(route('videos.playback', $video))
            ->assertForbidden();
    }

    public function test_draft_video_is_never_accessible(): void
    {
        $video = Video::factory()->draft()->for($this->course)->create(['is_free_designated' => true]);

        $this->actingAs($this->user)
            ->get(route('videos.show', [$this->course, $video]))
            ->assertNotFound();
    }

    public function test_exceeding_the_free_cap_fails_closed(): void
    {
        // Cap for videos is 2; force-designate 3 directly in the DB
        // (simulating drift) and none of them may be served free.
        Video::factory()->count(3)->for($this->course)->create(['is_free_designated' => true, 'status' => 'published', 'published_at' => now()->subDay()]);

        $video = Video::first();

        $this->actingAs($this->user)
            ->get(route('videos.playback', $video))
            ->assertForbidden();
    }

    public function test_admin_cannot_designate_more_free_videos_than_the_cap(): void
    {
        $admin = User::factory()->admin()->create();

        Video::factory()->count(2)->for($this->course)->create();

        foreach (Video::all() as $video) {
            $response = $this->actingAsAdmin($admin)->patch(route('admin.free-items.update', ['type' => 'videos', 'id' => $video->id]), ['designated' => true]);
            $response->assertRedirect();
        }

        $third = Video::factory()->for($this->course)->create();
        $this->actingAsAdmin($admin)
            ->patch(route('admin.free-items.update', ['type' => 'videos', 'id' => $third->id]), ['designated' => true])
            ->assertSessionHasErrors('free_item');
    }

    public function test_non_admin_cannot_reach_the_admin_panel(): void
    {
        $this->actingAs($this->user)->get('/admin')->assertForbidden();
    }

    public function test_guest_is_redirected_from_admin_to_login(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    private function subscribe(User $user): void
    {
        Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'activated_at' => now(),
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);
    }
}

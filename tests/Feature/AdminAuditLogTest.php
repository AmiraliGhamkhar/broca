<?php

namespace Tests\Feature;

use App\Models\AdminActivityLog;
use App\Models\Course;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_state_changing_admin_requests_are_logged(): void
    {
        $admin = User::factory()->admin()->create();
        $video = Video::factory()->for(Course::factory())->create();

        $this->actingAs($admin)
            ->patch(route('admin.free-items.update', ['type' => 'videos', 'id' => $video->id]), ['designated' => true])
            ->assertRedirect();

        $this->assertDatabaseHas('admin_activity_logs', [
            'user_id' => $admin->id,
            'route_name' => 'admin.free-items.update',
            'method' => 'PATCH',
        ]);
    }

    public function test_read_requests_are_not_logged(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

        $this->assertSame(0, AdminActivityLog::count());
    }

    public function test_sensitive_parameters_are_redacted(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.two-factor.verify'), ['code' => '123456']);

        // 2FA verify is outside the audit group (it contains one-time codes)
        // — prove nothing landed in the log at all.
        $this->assertSame(0, AdminActivityLog::count());

        $video = Video::factory()->for(Course::factory())->create();
        $this->actingAs($admin)->patch(route('admin.videos.update', $video), [
            'course_id' => $video->course_id,
            'title' => 'عنوان جدید',
            'status' => 'draft',
            'password' => 'super-secret',
            '_token' => 'csrf-value',
        ]);

        $log = AdminActivityLog::latest('id')->first();
        $payload = $log->payload ?? [];

        $this->assertArrayNotHasKey('password', $payload);
        $this->assertArrayNotHasKey('_token', $payload);
        $this->assertSame('عنوان جدید', $payload['title'] ?? null);
    }

    public function test_activity_log_page_is_admin_only(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.activity.index'))->assertForbidden();
    }
}

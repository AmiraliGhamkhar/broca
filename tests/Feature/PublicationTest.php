<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Tests\Concerns\WasmSafeRefreshDatabase;
use Tests\TestCase;

class PublicationTest extends TestCase
{
    use WasmSafeRefreshDatabase;

    public function test_admin_publishes_a_course_and_published_at_is_stamped(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create(['status' => 'in_review', 'published_at' => null]);

        $this->actingAsAdmin($admin)
            ->patch(route('admin.publication.update', ['type' => 'courses', 'id' => $course->id]), ['status' => 'published'])
            ->assertRedirect();

        $course = $course->fresh();
        $this->assertSame('published', $course->status);
        $this->assertNotNull($course->published_at);
    }

    public function test_invalid_transition_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create(['status' => 'draft']);

        // draft → archived is not a legal transition.
        $this->actingAsAdmin($admin)
            ->patch(route('admin.publication.update', ['type' => 'courses', 'id' => $course->id]), ['status' => 'archived'])
            ->assertSessionHasErrors('status');

        $this->assertSame('draft', $course->fresh()->status);
    }

    public function test_publishing_without_a_distinct_reviewer_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create(['status' => 'in_review', 'author_id' => null]);

        $this->actingAsAdmin($admin)
            ->patch(route('admin.publication.update', ['type' => 'courses', 'id' => $course->id]), ['status' => 'published'])
            ->assertSessionHasErrors('status');

        $this->assertSame('in_review', $course->fresh()->status);
    }

    public function test_non_admin_cannot_publish(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['status' => 'draft']);

        $this->actingAs($user)
            ->patch(route('admin.publication.update', ['type' => 'courses', 'id' => $course->id]), ['status' => 'published'])
            ->assertForbidden();
    }
}

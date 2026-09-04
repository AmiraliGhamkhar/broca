<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_edit_a_blog_post(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAsAdmin($admin)
            ->post(route('admin.blogs.store'), [
                'title' => 'مقاله جدید',
                'category' => 'نورولوژی',
                'author_name' => 'دکتر الف',
                'reviewer_name' => 'دکتر ب',
                'excerpt' => 'خلاصه مقاله',
                'content' => 'متن کامل مقاله',
                'status' => 'draft',
            ])
            ->assertRedirect(route('admin.blogs.index'));

        $post = BlogPost::query()->firstOrFail();

        $this->actingAsAdmin($admin)
            ->patch(route('admin.blogs.update', $post), [
                'title' => 'مقاله ویرایش‌شده',
                'category' => 'قلب',
                'author_name' => 'دکتر الف',
                'reviewer_name' => 'دکتر ج',
                'excerpt' => 'خلاصه تازه',
                'content' => 'متن تازه',
                'status' => 'in_review',
            ])
            ->assertRedirect(route('admin.blogs.index'));

        $this->assertDatabaseHas('blog_posts', [
            'id' => $post->id,
            'title' => 'مقاله ویرایش‌شده',
            'status' => 'in_review',
        ]);
    }

    public function test_admin_can_publish_blog_through_transition_route_only(): void
    {
        $admin = User::factory()->admin()->create();
        $post = BlogPost::factory()->create([
            'status' => 'in_review',
            'author_name' => 'دکتر الف',
            'reviewer_name' => 'دکتر ب',
        ]);

        $this->actingAsAdmin($admin)
            ->patch(route('admin.blogs.transition', $post), ['status' => 'published'])
            ->assertRedirect();

        $post = $post->fresh();
        $this->assertSame('published', $post->status);
        $this->assertNotNull($post->published_at);
    }

    public function test_create_route_rejects_direct_publish(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAsAdmin($admin)
            ->post(route('admin.blogs.store'), [
                'title' => 'انتشار مستقیم',
                'content' => 'متن',
                'status' => 'published',
            ])
            ->assertStatus(422);
    }

    public function test_admin_can_delete_blog_post(): void
    {
        $admin = User::factory()->admin()->create();
        $post = BlogPost::factory()->create();

        $this->actingAsAdmin($admin)
            ->delete(route('admin.blogs.destroy', $post))
            ->assertRedirect(route('admin.blogs.index'));

        $this->assertDatabaseMissing('blog_posts', ['id' => $post->id]);
    }

    public function test_non_admin_cannot_manage_blog(): void
    {
        $user = User::factory()->create();
        $post = BlogPost::factory()->create();

        $this->actingAs($user)->get(route('admin.blogs.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.blogs.edit', $post))->assertForbidden();
        $this->actingAs($user)->patch(route('admin.blogs.transition', $post), ['status' => 'published'])->assertForbidden();
    }
}

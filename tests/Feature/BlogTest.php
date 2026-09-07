<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use Tests\Concerns\WasmSafeRefreshDatabase;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use WasmSafeRefreshDatabase;

    public function test_published_article_renders(): void
    {
        $post = BlogPost::factory()->published()->create([
            'title' => 'کالبدشناسی ناحیه بروکا',
            'slug' => 'broca-area-and-aphasia',
        ]);

        $this->get(route('blog.show', $post->slug))
            ->assertOk()
            ->assertSee('کالبدشناسی ناحیه بروکا');
    }

    public function test_unknown_slug_404s(): void
    {
        $this->get('/blog/anything-else')->assertNotFound();
    }

    public function test_draft_posts_are_not_public(): void
    {
        $post = BlogPost::factory()->create([
            'slug' => 'draft-post',
            'status' => 'draft',
            'published_at' => null,
        ]);

        $this->get(route('blog.show', $post->slug))->assertNotFound();
    }

    public function test_blog_index_lists_only_published_posts(): void
    {
        $published = BlogPost::factory()->published()->create(['title' => 'مقاله منتشرشده']);
        BlogPost::factory()->create(['title' => 'مقاله پیش‌نویس']);

        $this->get(route('blog.index'))
            ->assertOk()
            ->assertSee($published->title)
            ->assertDontSee('مقاله پیش‌نویس');
    }
}

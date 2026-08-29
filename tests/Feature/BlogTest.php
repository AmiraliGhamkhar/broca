<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blog routing regression: previously ANY slug rendered the same static
 * article (duplicate content on arbitrary URLs). Only the canonical slug
 * and the explicitly announced slugs may resolve; everything else 404s.
 */
class BlogTest extends TestCase
{
    use RefreshDatabase;

    public function test_canonical_article_renders(): void
    {
        $this->get(route('blog.show', 'broca-area-and-aphasia'))
            ->assertOk()
            ->assertSee('کالبدشناسی ناحیه بروکا');
    }

    public function test_announced_but_unwritten_slugs_render_a_coming_soon_panel(): void
    {
        $this->get(route('blog.show', 'ecg-electrophysiology'))
            ->assertOk()
            ->assertSee('به‌زودی');

        $this->get(route('blog.show', 'sm2-spaced-repetition-medicine'))
            ->assertOk()
            ->assertSee('به‌زودی');
    }

    public function test_unknown_slugs_404_instead_of_duplicate_content(): void
    {
        $this->get('/blog/anything-else')->assertNotFound();
        $this->get('/blog/ecg')->assertNotFound(); // partial slug is not a post
    }

    public function test_blog_index_lists_the_real_article(): void
    {
        $this->get(route('blog.index'))
            ->assertOk()
            ->assertSee('broca-area-and-aphasia');
    }
}

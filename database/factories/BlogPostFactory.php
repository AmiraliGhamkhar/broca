<?php

namespace Database\Factories;

use App\Models\BlogPost;
use App\Support\Slug;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlogPost>
 */
class BlogPostFactory extends Factory
{
    protected $model = BlogPost::class;

    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'title' => $title,
            'slug' => Slug::unique($title, fn (string $slug): bool => BlogPost::query()->where('slug', $slug)->exists()),
            'category' => 'آموزش پزشکی',
            'author_name' => fake()->name(),
            'reviewer_name' => fake()->name(),
            'excerpt' => fake()->paragraph(),
            'content' => fake()->paragraphs(4, true),
            'status' => 'draft',
            'published_at' => null,
            'meta_title' => null,
            'meta_description' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
    }
}

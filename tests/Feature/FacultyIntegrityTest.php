<?php

namespace Tests\Feature;

use App\Models\Contributor;
use App\Models\Course;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards SPEC.md: "No placeholder may be presented as a real medical
 * credential, source, price, provider, or launch guarantee."
 *
 * The landing page previously hardcoded four invented doctors with invented
 * institutional credentials. These tests make that regression fail loudly.
 */
class FacultyIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_shows_no_faculty_when_none_are_published(): void
    {
        // A contributor that exists but is attached to nothing published must
        // never be presented as faculty.
        Contributor::create([
            'name' => 'نویسندهٔ نمونه',
            'slug' => 'sample-unattached',
            'credentials' => 'نمونهٔ توسعه',
            'is_visible' => true,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee('نویسندهٔ نمونه');
        // The honest empty state describes the process instead.
        $response->assertSee('مسیر انتشار محتوا در بروکا');
    }

    public function test_invisible_contributors_never_reach_the_landing_page(): void
    {
        $hidden = Contributor::create([
            'name' => 'مخفی',
            'slug' => 'hidden-one',
            'credentials' => 'نباید نمایش داده شود',
            'is_visible' => false,
        ]);

        $subject = Subject::create([
            'name' => 'آزمون',
            'slug' => 'test-subject',
            'is_visible' => true,
        ]);

        Course::factory()->create([
            'subject_id' => $subject->id,
            'author_id' => $hidden->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $this->get('/')->assertStatus(200)->assertDontSee('نباید نمایش داده شود');
    }

    public function test_real_published_contributor_is_shown_with_credentials(): void
    {
        $author = Contributor::create([
            'name' => 'نویسندهٔ واقعی',
            'slug' => 'real-author',
            'credentials' => 'اعتبارنامهٔ ثبت‌شده',
            'is_visible' => true,
        ]);

        $subject = Subject::create([
            'name' => 'آزمون دو',
            'slug' => 'test-subject-2',
            'is_visible' => true,
        ]);

        Course::factory()->create([
            'subject_id' => $subject->id,
            'author_id' => $author->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $this->get('/')
            ->assertStatus(200)
            ->assertSee('نویسندهٔ واقعی')
            ->assertSee('اعتبارنامهٔ ثبت‌شده');
    }

    public function test_seeder_contributors_are_not_publicly_visible(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        // Demo/sample rows must never be visible; production faculty is
        // entered by hand through the admin.
        $this->assertSame(
            0,
            Contributor::query()->where('is_visible', true)->count(),
            'Seeded demo contributors must all be is_visible = false.'
        );
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_landing_page_is_persian_and_rtl(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('<html lang="fa" dir="rtl">', false)
            ->assertSee('آموزش پزشکی برای دانشجویان', false)
            // Assert the copy rendered beside the selected editorial hero artwork.
            ->assertSee('پلتفرم بازبینی‌شده آموزش پزشکی', false);
    }
}

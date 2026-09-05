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
            // The 2026-09 hero is the editorial card, not the old
            // heart-placeholder poster — assert copy the current hero renders.
            ->assertSee('پلتفرم بازبینی‌شده آموزش پزشکی', false);
    }

    public function test_the_health_endpoint_reports_service_status(): void
    {
        $response = $this->getJson('/health');

        $response->assertOk()
            ->assertJsonStructure(['status', 'db', 'time'])
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('db', 'ok');
    }
}

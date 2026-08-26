<?php

namespace Tests\Feature;

use Tests\TestCase;

class FoundationTest extends TestCase
{
    public function test_the_landing_page_is_persian_and_rtl(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('<html lang="fa" dir="rtl">', false)
            ->assertSee('آموزش پزشکی برای دانشجویان', false)
            ->assertSee('heart-placeholder', false);
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

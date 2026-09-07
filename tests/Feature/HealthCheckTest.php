<?php

namespace Tests\Feature;

use Tests\Concerns\WasmSafeRefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_route_returns_ok_json(): void
    {
        $response = $this->getJson('/health');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'db', 'time'])
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('db', 'ok');
    }
}

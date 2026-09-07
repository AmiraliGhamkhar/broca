<?php

namespace Tests\Feature;

use Tests\Concerns\WasmSafeRefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use WasmSafeRefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $this->get('/')->assertOk();
    }
}

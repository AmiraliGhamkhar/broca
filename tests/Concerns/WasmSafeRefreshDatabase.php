<?php

namespace Tests\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Drop-in replacement for RefreshDatabase that survives the php-wasm
 * harness. Trait methods win over inherited ones, so the wasm-safe
 * overrides must live in the SAME trait the test classes import — an
 * override in Tests\TestCase would simply be shadowed.
 */
trait WasmSafeRefreshDatabase
{
    use RefreshDatabase {
        refreshDatabase as baseRefreshDatabase;
        refreshTestDatabase as baseRefreshTestDatabase;
    }

    protected function refreshTestDatabase()
    {
        if (getenv('WASM_HARNESS')) {
            return; // handled by refreshDatabase() below
        }

        $this->baseRefreshTestDatabase();
    }

    protected function refreshDatabase()
    {
        if (! getenv('WASM_HARNESS')) {
            $this->baseRefreshDatabase();

            return;
        }

        // db:wipe + migrate via the Artisan facade — RefreshDatabase's own
        // PendingCommand path aborts the wasm engine during setUp, and the
        // `migrate:fresh` drop-tables path does the same even via facade.
        \Illuminate\Support\Facades\Artisan::call('db:wipe', ['--drop-views' => true]);
        $exit = \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);

        if ($exit !== 0) {
            self::fail('migrate failed under the wasm harness: '.\Illuminate\Support\Facades\Artisan::output());
        }
    }
}

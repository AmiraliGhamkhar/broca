<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Services\OpsHealthReport;
use App\Support\PlanCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The three-card pricing lineup.
 *
 * Regression cover for "what happened to the two other plan cards": the
 * deploy pipeline runs `artisan migrate` but never `artisan db:seed`, so on a
 * host whose `plans` table was never populated /plans rendered the built-in
 * free fallback and nothing else — one card where the client expects three.
 * The lineup is now declared in App\Support\PlanCatalog and pushed into the
 * database by an idempotent command.
 */
class PlanLineupTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_creates_the_canonical_three_and_the_page_shows_three_cards(): void
    {
        $this->assertSame(0, Plan::query()->count());

        $this->artisan('broca:sync-plans')->assertSuccessful();

        $this->assertSame(3, Plan::query()->count());
        $this->assertDatabaseHas('plans', ['code' => 'free', 'price_irr' => 0, 'is_active' => true]);
        $this->assertDatabaseHas('plans', ['code' => 'monthly', 'price_irr' => 2700, 'duration_months' => 1]);
        $this->assertDatabaseHas('plans', ['code' => 'quarterly', 'price_irr' => 6000, 'duration_months' => 3]);

        $html = $this->get(route('plans'))->assertOk()->getContent();
        $this->assertSame(3, substr_count($html, '<article class="prc-05__card'));
    }

    public function test_running_sync_twice_changes_nothing(): void
    {
        $this->artisan('broca:sync-plans')->assertSuccessful();
        $this->artisan('broca:sync-plans')->assertSuccessful();

        $this->assertSame(3, Plan::query()->count());
    }

    public function test_sync_does_not_overwrite_a_price_the_operator_edited(): void
    {
        $this->artisan('broca:sync-plans')->assertSuccessful();

        $plan = Plan::query()->where('code', 'monthly')->firstOrFail();
        $plan->forceFill(['price_irr' => 999000])->save();
        $plan->forceFill(['is_active' => false])->save();

        // Deploy hooks run this on every release: it must never undo a
        // deliberate price change.
        $this->artisan('broca:sync-plans')->assertSuccessful();

        $this->assertSame(999000, (int) $plan->fresh()->price_irr);
        $this->assertFalse((bool) $plan->fresh()->is_active);
    }

    public function test_reset_restores_the_canonical_values(): void
    {
        $this->artisan('broca:sync-plans')->assertSuccessful();

        Plan::query()->where('code', 'monthly')->firstOrFail()
            ->forceFill(['price_irr' => 999000, 'is_active' => false])->save();

        $this->artisan('broca:sync-plans', ['--reset' => true])->assertSuccessful();

        $this->assertDatabaseHas('plans', ['code' => 'monthly', 'price_irr' => 2700, 'is_active' => true]);
    }

    public function test_a_missing_paid_tier_is_reported_rather_than_silently_hidden(): void
    {
        Plan::query()->create(PlanCatalog::free() + ['is_active' => true]);

        // One card is what the client saw; the admin panel must name the gap.
        $this->assertSame(
            ['monthly', 'quarterly'],
            (new OpsHealthReport)->missingPlanCodes()
        );

        $admin = User::factory()->admin()->create();

        $this->actingAsAdmin($admin)
            ->get(route('admin.plans.index'))
            ->assertOk()
            ->assertSee('چیدمان پلن‌ها ناقص است')
            ->assertSee('broca:sync-plans');
    }

    public function test_the_seeder_and_the_command_agree_because_both_read_the_catalog(): void
    {
        $this->seed();

        $this->assertSame([], (new OpsHealthReport)->missingPlanCodes());
        $this->assertSame(3, Plan::query()->count());

        // Seeding twice must not duplicate the lineup: `code` is the identity.
        $this->artisan('broca:sync-plans')->assertSuccessful();
        $this->assertSame(3, Plan::query()->count());
    }
}

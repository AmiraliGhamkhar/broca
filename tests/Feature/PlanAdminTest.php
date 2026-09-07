<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Tests\Concerns\WasmSafeRefreshDatabase;
use Tests\TestCase;

class PlanAdminTest extends TestCase
{
    use WasmSafeRefreshDatabase;

    public function test_admin_can_update_plan_price_and_activation(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->monthly()->create(['price_irr' => 5000000]);

        $this->actingAsAdmin($admin)
            ->patch(route('admin.plans.update', $plan), [
                'name' => 'یک‌ماهه',
                'description' => 'دسترسی کامل',
                'price_irr' => 6000000,
                'duration_months' => 1,
                'sort_order' => 2,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.plans.index'));

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'price_irr' => 6000000,
            'is_active' => true,
        ]);
    }

    public function test_plan_validation_rejects_negative_and_fractional_prices(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->monthly()->create();

        $this->actingAsAdmin($admin)
            ->patch(route('admin.plans.update', $plan), [
                'name' => 'x',
                'price_irr' => -100,
                'duration_months' => 1,
            ])
            ->assertSessionHasErrors('price_irr');

        $this->actingAsAdmin($admin)
            ->patch(route('admin.plans.update', $plan), [
                'name' => 'x',
                'price_irr' => 'not-a-number',
                'duration_months' => 1,
            ])
            ->assertSessionHasErrors('price_irr');
    }

    public function test_non_admin_cannot_manage_plans(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->monthly()->create();

        $this->actingAs($user)->get(route('admin.plans.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.plans.edit', $plan))->assertForbidden();
        $this->actingAs($user)->patch(route('admin.plans.update', $plan), [
            'name' => 'x', 'price_irr' => 1, 'duration_months' => 1,
        ])->assertForbidden();
    }

    public function test_inactive_plans_are_hidden_from_the_public_page(): void
    {
        Plan::factory()->monthly()->create(['is_active' => false, 'name' => 'پلان مخفی']);
        Plan::factory()->monthly()->create(['is_active' => true, 'name' => 'پلان عمومی']);

        $this->get(route('plans'))
            ->assertOk()
            ->assertSee('پلان عمومی')
            ->assertDontSee('پلان مخفی');
    }
}

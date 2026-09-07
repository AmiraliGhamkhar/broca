<?php

namespace Tests\Feature;

use App\Models\Subscription;
use Tests\Concerns\WasmSafeRefreshDatabase;
use Tests\TestCase;

/**
 * Single source of truth: User::activeSubscription()/hasActiveSubscription()
 * and Subscription::isActive() must agree — a NULL ends_at means the
 * subscription never expires (fixes the previous latent disagreement).
 */
class SubscriptionEntitlementTest extends TestCase
{
    use WasmSafeRefreshDatabase;

    public function test_subscription_with_null_ends_at_is_active_forever(): void
    {
        $subscription = Subscription::factory()->create(['ends_at' => null]);

        $this->assertTrue($subscription->isActive());
        $this->assertTrue($subscription->user->hasActiveSubscription());
        $this->assertSame($subscription->id, $subscription->user->activeSubscription()->id);
    }

    public function test_subscription_with_future_ends_at_is_active(): void
    {
        $subscription = Subscription::factory()->create(['ends_at' => now()->addMonth()]);

        $this->assertTrue($subscription->isActive());
        $this->assertTrue($subscription->user->hasActiveSubscription());
    }

    public function test_expired_subscription_is_not_active(): void
    {
        $subscription = Subscription::factory()->create([
            'activated_at' => now()->subMonths(2),
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subMonth(),
        ]);

        $this->assertFalse($subscription->isActive());
        $this->assertFalse($subscription->user->hasActiveSubscription());
        $this->assertNull($subscription->user->activeSubscription());
    }

    public function test_unactivated_or_future_subscription_is_not_active(): void
    {
        $notActivated = Subscription::factory()->create(['activated_at' => null, 'starts_at' => null]);
        $this->assertFalse($notActivated->isActive());
        $this->assertFalse($notActivated->user->hasActiveSubscription());

        $scheduled = Subscription::factory()->scheduled()->create();
        $this->assertFalse($scheduled->isActive());
        $this->assertFalse($scheduled->user->hasActiveSubscription());
    }
}

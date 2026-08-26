<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy
{
    public function hasActiveSubscription(User $user, Subscription $subscription): bool
    {
        return $subscription->user_id === $user->id && $subscription->isActive();
    }

    public function view(User $user, Subscription $subscription): bool
    {
        return $this->hasActiveSubscription($user, $subscription);
    }
}

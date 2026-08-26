<?php

namespace App\Providers;

use App\Models\Course;
use App\Models\Subscription;
use App\Policies\ContentPolicy;
use App\Policies\SubscriptionPolicy;
use Illuminate\Foundation\Auth\Access\Gate;
use Illuminate\Foundation\Auth\Access\AuthorizationManager;
use Illuminate\Foundation\Auth\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Course::class, ContentPolicy::class);
        Gate::policy(Subscription::class, SubscriptionPolicy::class);
    }
}
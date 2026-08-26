<?php

namespace App\Providers;

use App\Contracts\VideoProvider;
use App\Services\PlaceholderVideoProvider;
use App\Contracts\PaymentGateway;
use App\Services\ZarinPalGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(VideoProvider::class, PlaceholderVideoProvider::class);
        $this->app->bind(PaymentGateway::class, ZarinPalGateway::class);
        // Register EntitlementService singleton for freemium gating
        $this->app->singleton(\App\Services\EntitlementService::class, fn($app) => new \App\Services\EntitlementService());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('video-progress', function (Request $request): Limit {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });
    }
}

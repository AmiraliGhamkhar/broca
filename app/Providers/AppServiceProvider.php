<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Contracts\VideoProvider;
use App\Services\EntitlementService;
use App\Services\PlaceholderVideoProvider;
use App\Services\ZarinPalGateway;
use App\Models\Video;
use App\Models\Note;
use App\Models\Flashcard;
use App\Models\QuizQuestion;
use App\Observers\FreeCapObserver;
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
        $this->app->singleton(EntitlementService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Video::observe(FreeCapObserver::class);
        Note::observe(FreeCapObserver::class);
        Flashcard::observe(FreeCapObserver::class);
        QuizQuestion::observe(FreeCapObserver::class);
        RateLimiter::for('video-progress', function (Request $request): Limit {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // Registration sends a verification email — throttle to stop
        // account spam and email bombing through the signup form.
        RateLimiter::for('registration', function (Request $request): Limit {
            return Limit::perMinute(10)->by($request->ip());
        });
    }
}

<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Contracts\VideoProvider;
use App\Services\EntitlementService;
use App\Services\PlaceholderVideoProvider;
use App\Services\ZarinPalGateway;
use App\Models\Course;
use App\Models\Video;
use App\Models\Note;
use App\Models\Flashcard;
use App\Models\QuizQuestion;
use App\Observers\FreeCapObserver;
use App\Observers\CourseFreeCapObserver;
use App\Support\MarkdownTwin;
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
        // Every page that has a Markdown twin advertises it in <head>
        // (<link rel="alternate"> + hidden agent hint). Pure URL
        // construction — no DB work on the hot path.
        \Illuminate\Support\Facades\View::composer('layouts.app', function (\Illuminate\View\View $view): void {
            $request = request();
            $view->with(
                'markdownAlternate',
                MarkdownTwin::alternateUrlForRoute(
                    (string) ($request->route()?->getName() ?? ''),
                    $request->route()?->parameters() ?? []
                )
            );
        });

        Video::observe(FreeCapObserver::class);
        Note::observe(FreeCapObserver::class);
        Flashcard::observe(FreeCapObserver::class);
        QuizQuestion::observe(FreeCapObserver::class);
        // Course visibility feeds the free-cap counts; invalidation must also
        // fire when a course is published, archived, or soft-deleted.
        Course::observe(CourseFreeCapObserver::class);
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

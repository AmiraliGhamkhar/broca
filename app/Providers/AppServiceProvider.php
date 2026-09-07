<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Contracts\VideoProvider;
use App\Services\EntitlementService;
use App\Services\PlaceholderVideoProvider;
use App\Services\ZarinPalGateway;
use App\Models\BlogPost;
use App\Models\Course;
use App\Models\Subject;
use App\Models\Video;
use App\Models\Note;
use App\Models\Flashcard;
use App\Models\QuizQuestion;
use App\Observers\FreeCapObserver;
use App\Observers\CourseFreeCapObserver;
use App\Observers\PublicIndexCacheObserver;
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

        // The gateway binding follows config('payment.default') so switching
        // to Zibal is an env change (PAYMENT_GATEWAY=zibal), not a code
        // change. The shetabit Payment instance reads the same default, so
        // driver selection stays in one place.
        $this->app->bind(PaymentGateway::class, match (config('payment.default', 'zarinpal')) {
            'zibal' => ZibalGateway::class,
            default => ZarinPalGateway::class,
        });

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
            $view->with([
                'markdownAlternate' => MarkdownTwin::alternateUrlForRoute(
                    (string) ($request->route()?->getName() ?? ''),
                    $request->route()?->parameters() ?? []
                ),
                'siteAppearance' => \App\Models\SiteSetting::current(),
                'footerSubjects' => \Illuminate\Support\Facades\Cache::remember(
                    'footer_subjects',
                    300,
                    fn () => Subject::query()
                        ->where('is_visible', true)
                        ->orderBy('sort_order')
                        ->limit(4)
                        ->get(['slug', 'name'])
                        ->map(fn ($subject) => ['slug' => $subject->slug, 'name' => $subject->name])
                        ->all()
                ),
            ]);
        });

        \Illuminate\Support\Facades\View::composer(
            ['welcome', 'components.landing-hero'],
            fn (\Illuminate\View\View $view) => $view->with('siteAppearance', \App\Models\SiteSetting::current())
        );

        Video::observe(FreeCapObserver::class);
        Note::observe(FreeCapObserver::class);
        Flashcard::observe(FreeCapObserver::class);
        QuizQuestion::observe(FreeCapObserver::class);
        // Course visibility feeds the free-cap counts; invalidation must also
        // fire when a course is published, archived, or soft-deleted.
        Course::observe(CourseFreeCapObserver::class);

        // sitemap.xml and llms.txt are cached (they enumerate the whole
        // published catalog on every hit, and AI crawlers poll them hard).
        // Publishing must invalidate them immediately, or new content stays
        // undiscoverable for the cache TTL.
        foreach ([Course::class, Subject::class, BlogPost::class] as $model) {
            $model::observe(PublicIndexCacheObserver::class);
        }

        RateLimiter::for('video-progress', function (Request $request): Limit {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // Registration sends a verification email — throttle to stop
        // account spam and email bombing through the signup form.
        RateLimiter::for('registration', function (Request $request): Limit {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Checkout hits ZarinPal and can create an invoice row per call.
        // Keyed by user (not IP): university/hospital networks NAT many
        // legitimate students behind one address, and an IP key would let
        // one user's retries lock out everyone else on that network.
        RateLimiter::for('checkout', function (Request $request): Limit {
            return Limit::perMinute(6)->by($request->user()?->id ?: $request->ip());
        });
    }
}

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
use App\Support\PasswordPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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

        // One password policy for the whole app: /register, /reset-password and
        // anything else that sets a password validate through this, so the rule
        // and the copy shown to users can never drift apart.
        Password::defaults(fn (): Password => PasswordPolicy::rule());

        // Staging safety valve (Laravel's Mail::alwaysTo): every outbound mail
        // is retargeted to one inbox so a staging run can exercise real SMTP
        // without mailing students. Unset in production = no-op.
        if (($alwaysTo = config('broca.mail_to')) && ! app()->isProduction()) {
            \Illuminate\Support\Facades\Mail::alwaysTo($alwaysTo);
        }

        RateLimiter::for('video-progress', function (Request $request): Limit {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        /*
         * Auth limiters. Both are keyed by IP on purpose:
         *  - registration: stops account spam and verification-mail flooding;
         *  - login: stops credential *spraying* (one password across many
         *    accounts), which the controller's identifier|IP counter cannot see.
         * A NATed university/hospital network shares one address, so the
         * numbers stay human-generous and the identifier counter in
         * AuthenticatedSessionController carries the targeted-guessing case.
         * The Limit response is what a real visitor sees — Persian, and
         * honestly describes a wait rather than a wrong password.
         */
        RateLimiter::for('registration', function (Request $request): Limit {
            return Limit::perMinute(6)->response(function () use ($request) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'message' => 'تعداد ثبت‌نام از این شبکه بیش از حد مجاز است. لطفاً یک دقیقه بعد دوباره تلاش کنید.',
                    ], 429);
                }

                // Plain form post: bounce the visitor back to the form with a
                // readable notice instead of a raw 429 page. route() (not
                // ->back()) — the previous URL *is* this form, and a redirect
                // to it inside the throttled request would loop.
                return redirect()->route('register')->with('error',
                    'تعداد ثبت‌نام از این شبکه بیش از حد مجاز است. لطفاً یک دقیقه بعد دوباره تلاش کنید.');
            });
        });

        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(10)->response(function () use ($request) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'message' => 'تعداد تلاش‌های ورود از این شبکه بیش از حد مجاز است. لطفاً یک دقیقه بعد دوباره تلاش کنید.',
                    ], 429);
                }

                return redirect()->route('login')->withInput($request->only('identifier'))
                    ->with('error',
                        'تعداد تلاش‌های ورود از این شبکه بیش از حد مجاز است. لطفاً یک دقیقه بعد دوباره تلاش کنید.');
            });
        });

        // Both password-reset steps share one budget: request-a-link and
        // complete-a-reset are the same abuse surface (each can send mail or
        // hammer the token table). Keyed by the submitted address when there is
        // one, falling back to IP so malformed payloads are still bounded.
        RateLimiter::for('password-reset', function (Request $request): Limit {
            // is_string guard: a hostile `email[]=x` payload must not turn a
            // throttled route into a 500 on the (string) cast.
            $email = $request->input('email');
            $key = (is_string($email) ? mb_strtolower(trim($email)) : '') ?: ($request->ip() ?? 'guest');

            $limit = Limit::perMinute(6)->by($key);

            return $limit->response(function () use ($request) {
                $message = 'زیاد تلاش کرده‌اید. لطفاً یک دقیقه صبر کنید و دوباره امتحان کنید.';

                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json(['message' => $message], 429);
                }

                return back()->withInput($request->only('email'))->with('error', $message);
            });
        });

        // "Resend verification link" is per user, not per IP: a campus NAT
        // must not let one student's impatience mute everyone else's resend
        // button. Capped at 3/min so the queue can't be used to mail-bomb one
        // inbox. The 429 body explains the wait in Persian instead of
        // "Too Many Attempts".
        RateLimiter::for('verification-resend', function (Request $request): Limit {
            return Limit::perMinute(3)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () use ($request) {
                    $message = 'برای ارسال دوباره لینک تأیید کمی صبر کنید (هر دقیقه حداکثر ۳ بار).';

                    if ($request->expectsJson() || $request->is('api/*')) {
                        return response()->json(['message' => $message], 429);
                    }

                    return back()->with('error', $message);
                });
        });

        // A 6-digit TOTP code is 10^6 possibilities, and this app accepts it
        // across a 90-second drift window — an unbounded endpoint is therefore
        // realistically guessable. 5 tries/minute per admin, keyed by user id
        // (not IP: admins share offices and NAT), turns that into ~10^4
        // windows, i.e. not an attack anyone can actually run.
        RateLimiter::for('admin-2fa', function (Request $request): Limit {
            return Limit::perMinute(5)->by('2fa:'.($request->user()?->id ?? $request->ip()));
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

<?php

return [
    'video_completion_threshold' => (int) env('BROCA_VIDEO_COMPLETION_THRESHOLD', 70),
    'currency' => env('BROCA_CURRENCY', 'IRR'),
    'currency_display' => env('BROCA_CURRENCY_DISPLAY', 'toman'),
    'display_timezone' => env('BROCA_DISPLAY_TIMEZONE', 'Asia/Tehran'),
    'terms_version' => env('BROCA_TERMS_VERSION', 'v1-placeholder'),
    'privacy_version' => env('BROCA_PRIVACY_VERSION', 'v1-placeholder'),
    'medical_disclaimer_version' => env('BROCA_MEDICAL_DISCLAIMER_VERSION', 'v1-placeholder'),

    /*
    | Auth policy (register / login / password reset) — see docs/RUNBOOK.md.
    */

    // Floor for both registration and password reset. The full rule (case +
    // digit requirements, generated error copy) lives in
    // App\Support\PasswordPolicy and is also bound as Password::defaults().
    'password_min' => max(8, (int) env('BROCA_PASSWORD_MIN', 8)),

    // HaveIBeenPwned breach check on new passwords. It calls
    // api.pwnedpasswords.com at validation time, so on a restricted or slow
    // host that call can hold a signup for seconds or fail the request
    // outright — it therefore stays opt-in. Enable only on hosts with proven
    // outbound connectivity to that API.
    'password_leak_check' => (bool) env('BROCA_PASSWORD_LEAK_CHECK', false),

    // Mail safety valve (Laravel's Mail::alwaysTo): set a single address in
    // staging to stop accidental bulk mail while still exercising the real
    // SMTP path. Never set it in production.
    'mail_to' => env('BROCA_MAIL_TO'),

    /*
    |--------------------------------------------------------------------------
    | Transactional delivery — verification mail, verification SMS, reset mail
    |--------------------------------------------------------------------------
    |
    | `queue` is the queue connection these three notifications are pushed to.
    |
    | IT DEFAULTS TO `sync`, AND THAT IS THE FIX FOR THE LAUNCH BLOCKER.
    |
    | They are the only messages a user must receive *during* the request that
    | creates the account: on a host whose cron-driven queue worker is missing,
    | misconfigured, or silently dead, a `database` connection means the mail
    | sits in `jobs` forever — the account is created, nobody can verify, and
    | every new signup (and every password reset) is stuck. Sending inline is
    | a few hundred milliseconds of SMTP on one request, and it is the
    | difference between "signup works" and "nobody can sign up".
    |
    | On a host with a proven, monitored worker, set
    | BROCA_NOTIFICATIONS_QUEUE=database to move the SMTP round-trip off the
    | request. Everything else (backups, media jobs) keeps using the default
    | QUEUE_CONNECTION.
    */
    'notifications' => [
        'queue' => (string) env('BROCA_NOTIFICATIONS_QUEUE', 'sync'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mobile verification (SMS one-time code)
    |--------------------------------------------------------------------------
    |
    | Signup asks for a mobile number, so proving control of it is what lets a
    | user who never receives the verification mail (spam folder, corporate
    | filter, a mail server the host cannot reach) still activate the account
    | — see App\Http\Middleware\EnsureVerifiedContact: EITHER a verified email
    | OR a verified mobile unlocks the dashboard and checkout.
    |
    | The TTL is deliberately short and the attempt budget deliberately small:
    | a 6-digit code is 10^6 possibilities, and the code is stored bcrypt-
    | hashed, so the window is what protects a dump of the table.
    */
    'phone_verification' => [
        'enabled' => (bool) env('BROCA_PHONE_VERIFICATION', true),
        // 6 digits is the longest code a person retypes without error.
        'code_length' => (int) env('BROCA_PHONE_CODE_LENGTH', 6),
        'ttl_minutes' => (int) env('BROCA_PHONE_CODE_TTL', 10),
        'max_attempts' => (int) env('BROCA_PHONE_CODE_ATTEMPTS', 5),
        // Per-user cooldown between codes; also the anti-SMS-pumping valve,
        // because a text message costs real money per send.
        'resend_cooldown_seconds' => (int) env('BROCA_PHONE_RESEND_COOLDOWN', 60),
    ],

    // Kill switch for the checkout while payment incidents are being
    // investigated. Plans stay visible; purchase buttons are replaced by a
    // notice (see the plans view).
    'checkout_enabled' => (bool) env('BROCA_CHECKOUT_ENABLED', false),

    // Force every request to HTTPS (production). Set APP_FORCE_HTTPS=true
    // once TLS is live at the host; local dev stays on plain HTTP.
    'force_https' => (bool) env('APP_FORCE_HTTPS', false),

    'external_video_origins' => array_values(array_filter(array_map('trim', explode(',', (string) env('BROCA_EXTERNAL_VIDEO_ORIGINS', ''))))),

    // TLS-terminating reverse proxies. MUST live in config (not a raw env()
    // call in bootstrap/app.php): once `php artisan config:cache` runs — and
    // the cPanel deploy hook always runs it — env() outside config/ returns
    // null. Trusting no proxies on a host that terminates TLS upstream makes
    // $request->secure() false forever, which sends ForceSecureConnections
    // into an infinite HTTPS redirect loop and stops the Secure session
    // cookie from ever being set.
    'trusted_proxies' => array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', ''))))),
];

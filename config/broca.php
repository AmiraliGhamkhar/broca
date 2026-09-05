<?php

return [
    'video_completion_threshold' => (int) env('BROCA_VIDEO_COMPLETION_THRESHOLD', 70),
    'currency' => env('BROCA_CURRENCY', 'IRR'),
    'currency_display' => env('BROCA_CURRENCY_DISPLAY', 'toman'),
    'display_timezone' => env('BROCA_DISPLAY_TIMEZONE', 'Asia/Tehran'),
    'terms_version' => env('BROCA_TERMS_VERSION', 'v1-placeholder'),
    'privacy_version' => env('BROCA_PRIVACY_VERSION', 'v1-placeholder'),
    'medical_disclaimer_version' => env('BROCA_MEDICAL_DISCLAIMER_VERSION', 'v1-placeholder'),

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

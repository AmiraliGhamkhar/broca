<?php

return [
    'video_completion_threshold' => (int) env('BROCA_VIDEO_COMPLETION_THRESHOLD', 70),
    'currency' => env('BROCA_CURRENCY', 'IRR'),
    'currency_display' => env('BROCA_CURRENCY_DISPLAY', 'toman'),
    'display_timezone' => env('BROCA_DISPLAY_TIMEZONE', 'Asia/Tehran'),
    'terms_version' => env('BROCA_TERMS_VERSION', 'v1-placeholder'),
    'privacy_version' => env('BROCA_PRIVACY_VERSION', 'v1-placeholder'),
    'medical_disclaimer_version' => env('BROCA_MEDICAL_DISCLAIMER_VERSION', 'v1-placeholder'),
];

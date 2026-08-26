# Project status

Updated 2026-08-26 after the full codebase audit and remediation pass.

## Completed

- **Foundation:** Laravel 13 + Blade/Tailwind v4/Alpine, RTL-first layout, self-hosted Vazirmatn (Regular/Medium/Bold/Black), auth with email+phone login, rate-limited registration/login, suspended-session lockout middleware, password reset flow (email).
- **Content core:** courses/subjects catalog with search + subject filter, course detail with published-only listings and JSON-LD `Course` schema, open enrollment (idempotent), notes with private-disk download.
- **Freemium gating:** `ContentPolicy` delegating to `EntitlementService` (single source of truth: `User::hasActiveSubscription()`), global free caps (2 videos / 1 note / 10 flashcards / 1 quiz question) fail-closed, admin designation with quota enforcement and cache-lock serialization.
- **Video learning:** published gating (datetime-cast), short-lived signed playback URLs that re-check entitlement server-side, progress tracking endpoint (row-locked, race-safe) wired to the player UI, configurable completion threshold.
- **Flashcards:** SM-2-compatible scheduler (pure `review()` + persisting `apply()`), throttled review endpoint, per-user schedules with first-insert race handling, study page interactivity fixed (`@stack('scripts')`).
- **Quizzes:** submission keyed by question IDs (the previous build 422'd on every attempt), option-belongs-to-question validation, transactional attempt scoring, private result pages.
- **Monetization:** `invoices` migration (previously missing — `php artisan migrate` failed on MySQL), plan-driven integer-Rial amounts, ZarinPal gateway rewritten against the real `shetabit/payment` v7 API (amount-bound verification), idempotent callback (row-lock + unique `gateway_reference` + replay-safe), invoice reuse, free-plan short-circuit, `broca:expire-subscriptions` command (also expires stale gateway invoices).
- **Admin:** real-metrics dashboard, videos CRUD (course binding, Persian-safe slugs, quota-aware free designation), publication workflow (draft→review→published→archived with byline enforcement).
- **SEO/GEO:** robots.txt (AI crawlers explicitly allowed on public pages), sitemap.xml, JSON-LD Organization/WebSite/Course.
- **Tests:** payment flow with fake gateway (checkout, success, failure, idempotency, replay, privacy), freemium gating matrix, quiz grading, SRS unit tests, phone normalization, auth edge cases (invalid phone ≠ 500, mass-assignment privesc attempt, suspended session).

## In progress

- CI pipeline (none exists yet — see follow-ups in DECISIONS.md).

## Pending / future

- Blog CMS (authoring + SEO fields + Trix in admin).
- FSRS port (`devdch/fsrs`) and/or Filament admin panel and/or Livewire — all require `composer` installs.
- Zibal secondary gateway (driver configured; needs callback route + param mapping).
- Lenis + GSAP landing scroll experience once the client's 3D-heart video asset arrives.
- Real plan prices, final brand palette hexes, legal copy (see placeholder register in SPEC §15).

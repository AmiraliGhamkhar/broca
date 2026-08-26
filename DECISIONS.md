# DECISIONS.md

Running log of decisions made that were not explicit in the brief, per the
working-process rules. Newest first.

## 2026-08-26 — Full audit remediation + brief alignment

### Resolved

| Decision | Rationale |
|---|---|
| **DB: MySQL 8 remains the default** | Original SPEC targeted shared/cPanel Iranian hosting. Postgres 16 is the preferred choice on a VPS (better JSON/FTS for quizzes and blog search) — switch `DB_CONNECTION` and port if the client confirms a VPS. **Open question for client.** |
| **Gateway: `shetabit/payment` v7, ZarinPal driver first** | One driver interface across ZarinPal/Zibal/30+ gateways. Zibal's driver is configured in `config/payment.php` but has no callback route yet; adding it is a ~50-line `ZibalGateway` + route + param mapping (Zibal uses `trackId`/`success` instead of `Authority`/`Status`). |
| **PHP bumped `^8.3` → `^8.4`** | `shetabit/payment` v7.0.0 (in `composer.lock`) requires PHP `^8.4`; the old constraint would break `composer install` on 8.3. |
| **Currency: Rial, integer, `currency => 'R'` in driver config** | The multipay ZarinPal driver multiplies amounts by 10 when `currency === 'T'`. All app amounts are integer Rial, so `'R'` prevents a silent 10× over/under-charge. |
| **Scheduler stays SM-2, not FSRS** | The brief prefers FSRS, but every PHP option (`fsrs-rs-php` Rust binding, `devdch/fsrs`, `scottlaurent/fsrs`) needs a `composer require` that couldn't be run in this environment. `SrsService` was split into pure `review()` + persisting `apply()` so an FSRS port can replace it behind the same signatures with no controller changes. **Follow-up: `composer require devdch/fsrs` (pure PHP) once dependencies can be installed, then port.** |
| **Admin stays custom Blade CRUD (not Filament v5)** | Filament pulls Livewire + its own stack via composer; not installable here. The existing Blade admin (dashboard, videos CRUD, free-item designation, publication workflow) was fixed and wired instead. **Follow-up: `composer require filament/filament` and migrate resources if the client wants the richer panel.** |
| **Public pages Blade+Alpine; no Livewire yet** | Same composer constraint. Blade+Alpine covers the current interactivity (video player, flashcard flip, quiz forms). Livewire can be introduced behind login later without touching public/SEO pages. |
| **`free_item_quotas` table deleted; caps are constants** | The table was written but never read; caps (`2/1/10/1`) are locked product decisions living in `EntitlementService`. If caps must become admin-editable, reintroduce the table and read it there — single point of change. |
| **Phone: contact field + login identifier, no OTP** | Per original SPEC decision. Brief leaves OTP open. **Open question for client** — OTP needs an SMS provider account and changes the auth flow. |
| **Password reset by email only** | Laravel's broker is email-based; phone reset would need custom tokens. |
| **Landing scroll: no GSAP/Lenis bundled yet** | The hero section accepts an MP4/WebM pair + poster fallback (client asset pending). Adding Lenis+GSAP is a view-only change once the 3D-heart asset arrives; CDN scripts would be pushed on `welcome` only. |
| **Placeholder playback asset: `public/videos/sample.mp4`** | The signed-URL chain (`/videos/{id}/playback` → provider → signed `/video-playback/{video}` → streamed file) is fully wired and entitlement-checked; drop a sample MP4 at that path to see it play end-to-end. A real VOD/CDN provider replaces `PlaceholderVideoProvider` only. |
| **Palette: token names fixed, hexes still placeholder** | Views referenced `coral/sun/teal/plum/ink/cream` classes that had **no** Tailwind tokens (they silently rendered unstyled). Tokens now exist in `resources/css/app.css`. Final hex values must come from the client's design file — **open question, do not invent.** |
| **i18n: shared strings via `lang/fa/app.php`; page copy inline** | Full extraction of every Persian string is churn without near-term value for a Persian-only v1; the shared layer exists so a second language is additive. |
| **SEO: robots.txt + sitemap.xml + JSON-LD shipped; no `llms.txt`** | Per brief §7 — skip weak-evidence hacks. AI crawlers are explicitly allowed in robots.txt for public pages. |
| **Blog remains placeholder views** | Phase-2 per the build plan (blog CMS + Trix authoring in admin). |

### Follow-ups requiring `composer` on a machine with PHP

1. `composer install` (lock is pinned; PHP ≥ 8.4).
2. `php artisan migrate --seed && php artisan test` — the suite is written to pass with the fake payment gateway; run it to confirm.
3. `vendor/bin/pint` — style pass.
4. Optional: `composer require devdch/fsrs` (FSRS port), `composer require filament/filament` (admin panel), Livewire for the authenticated app.

### Open questions for the client (from brief §12)

1. Hosting target (shared cPanel vs VPS) → final DB engine.
2. Real Toman prices for 1-month / 3-month plans (seeder uses placeholders).
3. OTP SMS verification required at launch?
4. Video hosting/CDN provider preference.
5. Content volume at launch (sizes the admin workflow).
6. ZarinPal and/or Zibal — primary or user-choice at checkout?
7. AI-crawler visibility for free content (currently **allowed**).
8. The 5-color brand palette design file — needed to replace placeholder hexes.
9. Admin roles: single admin (current) or multiple permission levels?

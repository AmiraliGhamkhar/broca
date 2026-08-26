# Broca Platform — Phase 0 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Establish the Laravel 13 foundation: RTL-first layout, self-hosted Vazirmatn font, Tailwind v4 + Alpine.js, a landing-page skeleton with a placeholder 3D-heart hero, a health route, Persian RTL error pages, and a Pint + PHPUnit CI base — so all later page work (blog, payment, admin, dashboard, quizzes, flashcards) sits on a correct foundation.

**Architecture:** A conventional Laravel monolith. Blade views use Tailwind v4 (configured via `resources/css/app.css` with `@theme` tokens) and Alpine.js for small interactive islands. The root HTML document carries `dir="rtl"` and `lang="fa"`. The landing hero is a responsive section with a `<video>`/poster placeholder boundary, clearly marked as a placeholder until the client supplies the 3D-heart asset. No SPA, no separate API — plain server-rendered HTML/CSS/JS.

**Tech Stack:** Laravel 13.17, PHP 8.3, Tailwind CSS v4 (via `@tailwindcss/vite`), Alpine.js 3, Vite 8, PHPUnit 12 (SQLite `:memory:` in tests), Pint 1.27.

**Spec:** `docs/superpowers/specs/2026-08-26-broca-phase-0-design.md` and the authoritative `SPEC.md` (root) — especially SPEC §3.1, §8.3, §10, §11 Phase 0.

## Global Constraints

- Backend: PHP 8.3+, Laravel 13.x (current scaffold is 13.17). [SPEC §3.1]
- Frontend: Blade, Tailwind CSS v4, Alpine.js, vanilla JS — no SPA, no admin framework. [SPEC §1.1, §3.1]
- Database: MySQL 8 on shared/cPanel; remain environment-variable driven. [SPEC §1.1]
- Language: Persian only in v1; route all copy through `lang/fa/*.php` so adding a language later is a config change. [SPEC §1.1, §8.3]
- RTL-first: `dir="rtl"` at HTML root; use Tailwind logical utilities (`ms-`/`me-`/`ps-`/`pe-`) throughout. [SPEC §8.3]
- Vazirmatn self-hosted; never depend on a third-party font CDN. [SPEC §1.1, §10]
- Money stored as integer IRR; display as toman. (Not yet exercised in Phase 0, but config exists.) [SPEC §1.1]
- No real credentials or launch-ready claims; placeholder assets must be visibly tracked. [SPEC §15]
- Timezone: store UTC; display `Asia/Tehran`. App default `APP_TIMEZONE=UTC`, `BROCA_DISPLAY_TIMEZONE=Asia/Tehran`. [.env.example]

---

## File Structure

```
resources/
  css/
    app.css                      # Tailwind v4 entry + @theme brand tokens + Vazirmatn @font-face
  js/
    app.js                       # Alpine.js bootstrap
  views/
    layouts/
      app.blade.php              # RTL root, Vazirmatn, Alpine, @yield sections
    components/
      landing-hero.blade.php     # placeholder 3D-heart video/poster boundary
    welcome.blade.php            # extends layout, uses hero + CTA
    errors/
      404.blade.php              # RTL Persian 404
      500.blade.php              # RTL Persian 500 (optional, minimal)
  fonts/
    (vazirmatn files copied to public/fonts/vazirmatn/ at setup)
routes/
  web.php                        # add /health
app/
  Http/
    Controllers/
      HealthCheckController.php  # JSON status
  Providers/
    AppServiceProvider.php       # (verify nothing breaks; no change needed unless wiring)
lang/
  fa/
    app.php                      # common UI strings (site name, CTAs)
config/
  broca.php                      # (exists) brand/threshold settings
tests/
  Feature/
    LandingPageTest.php          # 200, dir=rtl, Vazirmatn ref, hero placeholder present
    HealthCheckTest.php          # /health returns 200 JSON with db ok
public/
  fonts/vazirmatn/              # self-hosted woff2 slices
  images/                       # poster placeholder (simple SVG or solid-color jpg)
```

---

### Task 1: Verify environment & tooling

**Files:**
- Read: `composer.json`, `phpunit.xml`, `package.json`, `vite.config.js`, `.env.example`
- Run: `php artisan --version`, `./vendor/bin/pint --version`, `./vendor/bin/phpunit --version`

**Interfaces:**
- Consumes: nothing (greenfield check)
- Produces: confirmation that `artisan`, `pint`, `phpunit` all run on this machine

- [ ] **Step 1: Check CLI tool versions**

Run:
```bash
php artisan --version
./vendor/bin/pint --version
./vendor/bin/phpunit --version
```
Expected: Laravel 13.x, Pint 1.27.x, PHPUnit 12.x all print without error.

- [ ] **Step 2: Confirm Vite + Tailwind v4 entry points exist**

Run:
```bash
ls resources/css/app.css resources/js/app.js vite.config.js
```
Expected: the three files exist (scaffold default). If `app.css` is missing, create it in Task 2.

- [ ] **Step 3: Commit nothing yet** — this is a verification task. Note any failure and stop.

---

### Task 2: Tailwind v4 theme + Vazirmatn self-host

**Files:**
- Create: `resources/css/app.css`
- Create: `public/fonts/vazirmatn/Vazirmatn-Regular.woff2`, `Vazirmatn-Medium.woff2`, `Vazirmatn-Bold.woff2` (download from https://github.com/rastikerdar/vazirmatn/releases — woff2 subset; if network unavailable, use a single woff2 and note in spec/placeholder register)
- Modify: `resources/js/app.js` (import Alpine)
- Modify: `vite.config.js` (verify `@tailwindcss/vite` + `laravel()` plugin present)

**Interfaces:**
- Consumes: Tailwind v4, `@tailwindcss/vite` (already in devDependencies)
- Produces: a compiled `app.css` with `@theme` brand tokens and `@font-face` for Vazirmatn; `app.js` mounting Alpine

- [ ] **Step 1: Write `resources/css/app.css`**

```css
@import "tailwindcss";

/* Self-hosted Vazirmatn — never a third-party CDN (SPEC §1.1, §10) */
@font-face {
    font-family: "Vazirmatn";
    src: url("/fonts/vazirmatn/Vazirmatn-Regular.woff2") format("woff2");
    font-weight: 400;
    font-style: normal;
    font-display: swap;
}
@font-face {
    font-family: "Vazirmatn";
    src: url("/fonts/vazirmatn/Vazirmatn-Medium.woff2") format("woff2");
    font-weight: 500;
    font-style: normal;
    font-display: swap;
}
@font-face {
    font-family: "Vazirmatn";
    src: url("/fonts/vazirmatn/Vazirmatn-Bold.woff2") format("woff2");
    font-weight: 700;
    font-style: normal;
    font-display: swap;
}

@theme {
    /* Five-color brand palette — placeholder tokens; replace from design spec (SPEC §9, §15) */
    --color-broca-cream: #FDFBF7;
    --color-broca-ink: #1C1B19;
    --color-broca-slate: #4B5563;
    --color-broca-accent: #C2410C;
    --color-broca-sand: #E7DED2;
    --font-sans: "Vazirmatn", ui-sans-serif, system-ui, sans-serif;
}

html {
    background-color: var(--color-broca-cream);
    color: var(--color-broca-ink);
    font-family: var(--font-sans);
}
```

- [ ] **Step 2: Write `resources/js/app.js`**

```js
import Alpine from "alpinejs";

window.Alpine = Alpine;
Alpine.start();
```

- [ ] **Step 3: Verify `vite.config.js` includes Tailwind + Laravel plugins**

Expected content (adjust if differs):
```js
import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwind from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
        tailwind(),
    ],
});
```

- [ ] **Step 4: Download/place Vazirmatn woff2 files into `public/fonts/vazirmatn/`**

Run (if network available):
```bash
mkdir -p public/fonts/vazirmatn
curl -L -o public/fonts/vazirmatn/Vazirmatn-Regular.woff2 https://github.com/rastikerdar/vazirmatn/releases/download/v33.003/Vazirmatn-Regular.woff2
curl -L -o public/fonts/vazirmatn/Vazirmatn-Medium.woff2  https://github.com/rastikerdar/vazirmatn/releases/download/v33.003/Vazirmatn-Medium.woff2
curl -L -o public/fonts/vazirmatn/Vazirmatn-Bold.woff2    https://github.com/rastikerdar/vazirmatn/releases/download/v33.003/Vazirmatn-Bold.woff2
```
If network fails, create the folder and add a `README.md` noting the font must be supplied; the `@font-face` will fall back to system-ui until then. Mark in SPEC §15 placeholder register.

- [ ] **Step 5: Build assets to confirm no compile error**

Run:
```bash
npm install --ignore-scripts 2>/dev/null; npm run build
```
Expected: Vite builds `public/build/` with `app.css` + `app.js` and no error.

- [ ] **Step 6: Commit**

```bash
git add resources/css/app.css resources/js/app.js vite.config.js public/fonts/vazirmatn/
git commit -m "feat: Tailwind v4 theme + self-hosted Vazirmatn font"
```

---

### Task 3: RTL root layout + Persian lang file

**Files:**
- Create: `resources/views/layouts/app.blade.php`
- Create: `lang/fa/app.php`
- Modify: `routes/web.php` (already imports `welcome` view — keep; we'll point welcome at layout)

**Interfaces:**
- Consumes: Vite `@vite` directive (`@vite(['resources/css/app.css','resources/js/app.js'])`)
- Produces: a reusable `layouts.app` Blade with `dir="rtl" lang="fa"`, Vite assets, Alpine, and `@yield('content')`

- [ ] **Step 1: Write `lang/fa/app.php`**

```php
<?php

return [
    "name" => "بروکا",
    "tagline" => "آموزش پزشکی برای دانشجویان",
    "cta_register" => "ثبت‌نام",
    "cta_login" => "ورود",
    "hero_placeholder_note" => "این ویدیوی نمونه است؛ تصویر نهایی قلب سه‌بعدی توسط کارفرما تأمین می‌شود.",
    "footer_disclaimer" => "بروکا مشاوره پزشکی ارائه نمی‌دهد؛ محتوا صرفاً آموزشی است.",
];
```

- [ ] **Step 2: Write `resources/views/layouts/app.blade.php`**

```blade
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ __('app.tagline') }}">
    <title>@yield('title', __('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-broca-cream text-broca-ink font-sans antialiased min-h-screen flex flex-col">

    <header class="border-b border-broca-sand">
        <nav class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="{{ url('/') }}" class="text-xl font-bold">{{ __('app.name') }}</a>
            <div class="flex gap-3">
                <a href="{{ route('login') }}" class="px-3 py-1.5 rounded-md hover:bg-broca-sand">{{ __('app.cta_login') }}</a>
                <a href="{{ route('register') }}" class="px-3 py-1.5 rounded-md bg-broca-accent text-white">{{ __('app.cta_register') }}</a>
            </div>
        </nav>
    </header>

    <main class="flex-1">
        @yield('content')
    </main>

    <footer class="border-t border-broca-sand mt-16">
        <div class="max-w-6xl mx-auto px-4 py-6 text-sm text-broca-slate">
            {{ __('app.footer_disclaimer') }}
        </div>
    </footer>

</body>
</html>
```

- [ ] **Step 3: Commit**

```bash
git add resources/views/layouts/app.blade.php lang/fa/app.php
git commit -m "feat: RTL root layout with Vazirmatn + Persian lang strings"
```

---

### Task 4: Landing page skeleton with placeholder 3D-heart hero

**Files:**
- Create: `resources/views/components/landing-hero.blade.php`
- Modify: `resources/views/welcome.blade.php` (replace default starter with layout-extending content)

**Interfaces:**
- Consumes: `layouts.app`, `lang/fa/app.php`
- Produces: `/` renders RTL hero with a `<video>` + poster placeholder clearly marked

- [ ] **Step 1: Write `resources/views/components/landing-hero.blade.php`**

```blade
{{-- Placeholder 3D-heart hero boundary. Replace MP4/WebM + poster when client asset arrives (SPEC §1.1, §15). --}}
<section class="relative w-full min-h-[70vh] flex items-center justify-center overflow-hidden bg-broca-sand">
    <video
        class="absolute inset-0 w-full h-full object-cover opacity-60"
        autoplay muted loop playsinline
        poster="{{ asset('images/heart-placeholder.jpg') }}">
        <source src="{{ asset('videos/heart-placeholder.mp4') }}" type="video/mp4">
        <source src="{{ asset('videos/heart-placeholder.webm') }}" type="video/webm">
    </video>

    <div class="relative z-10 text-center px-4">
        <h1 class="text-4xl md:text-6xl font-bold text-broca-ink drop-shadow">
            {{ __('app.name') }}
        </h1>
        <p class="mt-4 text-lg md:text-2xl text-broca-ink/80">
            {{ __('app.tagline') }}
        </p>
        <div class="mt-8 flex gap-3 justify-center">
            <a href="{{ route('register') }}" class="px-5 py-2.5 rounded-md bg-broca-accent text-white font-medium">{{ __('app.cta_register') }}</a>
            <a href="{{ route('login') }}" class="px-5 py-2.5 rounded-md border border-broca-ink/30 font-medium">{{ __('app.cta_login') }}</a>
        </div>
    </div>

    <p class="absolute bottom-3 inset-x-0 text-center text-xs text-broca-ink/60">
        {{ __('app.hero_placeholder_note') }}
    </p>
</section>
```

- [ ] **Step 2: Rewrite `resources/views/welcome.blade.php`**

```blade
@extends('layouts.app')

@section('title', __('app.name') . ' — ' . __('app.tagline'))

@section('content')
    <x-landing-hero />
@endsection
```

- [ ] **Step 3: Add a simple placeholder poster image**

Create `public/images/heart-placeholder.jpg` — a solid cream/sand colored image (e.g. 1200x675). If image tooling is unavailable, create `public/images/heart-placeholder.svg` and update the `poster` attribute to the `.svg`. Keep the note that the real asset is pending (SPEC §15).

- [ ] **Step 4: Commit**

```bash
git add resources/views/components/landing-hero.blade.php resources/views/welcome.blade.php public/images/heart-placeholder.jpg
git commit -m "feat: landing page skeleton with placeholder 3D-heart hero"
```

---

### Task 5: Health-check route + controller

**Files:**
- Create: `app/Http/Controllers/HealthCheckController.php`
- Modify: `routes/web.php` (add `GET /health`)

**Interfaces:**
- Consumes: `Illuminate\Support\Facades\DB`, `Illuminate\Support\Facades\Response`
- Produces: `GET /health` returning JSON `{ status: "ok", db: "ok", time: <UTC iso> }`

- [ ] **Step 1: Write the failing test `tests/Feature/HealthCheckTest.php`**

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_route_returns_ok_json(): void
    {
        $response = $this->getJson('/health');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'db', 'time'])
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('db', 'ok');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run:
```bash
./vendor/bin/phpunit --filter HealthCheckTest
```
Expected: FAIL — route `/health` not found (404).

- [ ] **Step 3: Write `app/Http/Controllers/HealthCheckController.php`**

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class HealthCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::connection()->getPdo();
            $db = 'ok';
        } catch (\Throwable $e) {
            $db = 'error';
        }

        return Response::json([
            'status' => $db === 'ok' ? 'ok' : 'degraded',
            'db' => $db,
            'time' => now()->toIso8601ZuluString(),
        ]);
    }
}
```

- [ ] **Step 4: Register route in `routes/web.php`**

Add (near the top, after the existing `/` route):
```php
Route::get('/health', \App\Http\Controllers\HealthCheckController::class);
```

- [ ] **Step 5: Run test to verify it passes**

Run:
```bash
./vendor/bin/phpunit --filter HealthCheckTest
```
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/HealthCheckController.php routes/web.php tests/Feature/HealthCheckTest.php
git commit -m "feat: /health route returning DB + app status JSON"
```

---

### Task 6: Persian RTL 404 error page

**Files:**
- Create: `resources/views/errors/404.blade.php`

**Interfaces:**
- Consumes: Laravel's error rendering (aborts render `errors.404` automatically)
- Produces: a styled RTL 404 page with a link home

- [ ] **Step 1: Write `resources/views/errors/404.blade.php`**

```blade
@extends('layouts.app')

@section('title', '۴۰۴ — ' . __('app.name'))

@section('content')
    <section class="max-w-2xl mx-auto px-4 py-24 text-center">
        <p class="text-7xl font-bold text-broca-accent">۴۰۴</p>
        <h1 class="mt-4 text-2xl font-bold">صفحه مورد نظر یافت نشد</h1>
        <p class="mt-3 text-broca-slate">ممکن است آدرس تغییر کرده باشد یا صفحه حذف شده باشد.</p>
        <a href="{{ url('/') }}" class="inline-block mt-8 px-5 py-2.5 rounded-md bg-broca-accent text-white font-medium">
            بازگشت به صفحه اصلی
        </a>
    </section>
@endsection
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/errors/404.blade.php
git commit -m "feat: Persian RTL 404 page"
```

---

### Task 7: Landing page feature test + Pint baseline

**Files:**
- Create: `tests/Feature/LandingPageTest.php`

**Interfaces:**
- Consumes: `routes/web.php` `/` route, `layouts.app`, `landing-hero`
- Produces: assertion that home renders 200, is RTL, references Vazirmatn, and shows hero placeholder note

- [ ] **Step 1: Write `tests/Feature/LandingPageTest.php`**

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_home_page_renders_successfully(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="fa"', false);
        $response->assertSee('Vazirmatn', false);
        $response->assertSee('heart-placeholder', false);
        $response->assertSee('این ویدیوی نمونه است', false);
    }
}
```

- [ ] **Step 2: Run the landing test**

Run:
```bash
./vendor/bin/phpunit --filter LandingPageTest
```
Expected: PASS.

- [ ] **Step 3: Run full suite + Pint**

Run:
```bash
./vendor/bin/phpunit
./vendor/bin/pint --test
```
Expected: all tests pass; Pint reports no style issues (or fix them).

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/LandingPageTest.php
git commit -m "test: landing page RTL/Vazirmatn/hero assertions + Pint baseline"
```

---

## Self-Review

**1. Spec coverage:** SPEC §11 Phase 0 requires: Laravel 13 app (present), MySQL/Tailwind/Blade/Alpine/Vite/RTL/Vazirmatn (Tasks 2–3), env-driven config (present in `.env.example`), baseline test + lint (Task 7), health route (Task 5), landing skeleton with placeholder heart (Task 4), error pages (Task 6). All covered.

**2. Placeholder scan:** No TBD/TODO. The only "placeholder" is the hero video asset itself, which is intentionally and visibly marked per SPEC §15 and §10. Font download has a documented fallback path. Good.

**3. Type consistency:** `/health` returns `JsonResponse` with keys `status`, `db`, `time`; test asserts exactly those keys. Layout/hero/lang strings match between `lang/fa/app.php` and the views. No naming drift.

---

## Execution Handoff

Plan complete and saved to `docs/superpowers/plans/2026-08-26-broca-phase-0.md`.

**Note on the additional pages you requested (blog, payment, 404, admin, user dashboard, quizzes, flashcards):** these are deliberately *not* in Phase 0. Per the SPEC's phased plan and the brief's "build in vertical slices" rule, each sits in a later phase (404 is done here as part of foundation; the rest follow in Phases 1–4). Building them now, before the content models, auth, and entitlement layer exist, would produce hollow pages that can't enforce freemium gating or load real data. After Phase 0 is green, we continue Phase 1 (auth + courses + catalog) and onward, at which point each requested page gets its real implementation.

**Two execution options:**
1. **Subagent-Driven (recommended)** — I dispatch a fresh subagent per task, review between tasks.
2. **Inline Execution** — I execute tasks in this session with checkpoints.

Which approach?

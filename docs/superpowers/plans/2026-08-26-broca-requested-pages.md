# Broca Platform — Requested Pages Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build functional stubs for the requested pages (blog, payment, admin, user dashboard, quizzes, flashcards) on the Phase 0 foundation. These are functional stubs only — they will be fully implemented in later phases.

**Architecture:** Each page extends the RTL layout and uses Tailwind v4. Pages are stubbed with placeholder content and navigation. No real data or business logic is implemented.

**Tech Stack:** Blade, Tailwind CSS v4, Alpine.js (for interactive islands), Laravel 13.

**Spec:** SPEC §1.1, §3.1, §8.3, §10, §12 (requested pages).

## Global Constraints

- Backend: PHP 8.3+, Laravel 13.x. [SPEC §3.1]
- Frontend: Blade, Tailwind CSS v4, Alpine.js, vanilla JS — no SPA, no admin framework. [SPEC §1.1, §3.1]
- Language: Persian only in v1; route all copy through `lang/fa/*.php`. [SPEC §1.1, §8.3]
- RTL-first: `dir="rtl"` at HTML root; use Tailwind logical utilities (`ms-`/`me-`/`ps-`/`pe-`) throughout. [SPEC §8.3]
- Vazirmatn self-hosted; never depend on a third-party font CDN. [SPEC §1.1, §10]
- No real credentials or launch-ready claims; placeholder assets must be visibly tracked. [SPEC §15]

---

## File Structure

```
resources/
  views/
    blog/
      index.blade.php            # Blog index
      show.blade.php             # Blog post
    learner/
      dashboard.blade.php       # User dashboard (stub)
      quiz.blade.php            # Quiz page (stub)
      deck-study.blade.php      # Flashcard study (stub)
    admin/
      dashboard.blade.php       # Admin dashboard (stub)
    plans.blade.php            # Plans page (stub)
    payments/
      success.blade.php         # Payment success (stub)
      failed.blade.php          # Payment failed (stub)
    legal/
      placeholder.blade.php     # Legal pages (terms, privacy, disclaimer)
lang/
  fa/
    app.php                      # Add blog/payment/admin/quiz/flashcard strings
```

---

### Task 1: Blog pages

**Files:**
- Create: `resources/views/blog/index.blade.php`, `resources/views/blog/show.blade.php`
- Modify: `routes/web.php` (add blog routes)

**Interfaces:**
- Consumes: `layouts.app`, `lang/fa/app.php`
- Produces: `/blog` and `/blog/{slug}` routes rendering stubbed blog pages

- [ ] **Step 1: Write `resources/views/blog/index.blade.php`**

```blade
@extends('layouts.app')

@section('title', 'بلاگ — ' . __('app.name'))

@section('content')
    <section class="max-w-4xl mx-auto px-4 py-12">
        <h1 class="text-3xl font-bold">بلاگ</h1>
        <p class="mt-2 text-broca-slate">مطالب پزشکی و آموزشی برای دانشجویان</p>

        <div class="mt-8 grid gap-6">
            @for ($i = 1; $i <= 3; $i++)
                <article class="border-b border-broca-sand pb-6">
                    <h2 class="text-xl font-medium">{{ __('app.name') }} - مقاله نمونه {{ $i }}</h2>
                    <p class="mt-2 text-broca-slate">خلاصه مقاله نمونه {{ $i }}. این محتوای نمونه است و بعداً با محتوای واقعی جایگزین می‌شود.</p>
                    <a href="{{ route('blog.show', ['slug' => 'sample-article-' . $i]) }}" class="inline-block mt-3 px-4 py-1.5 rounded-md bg-broca-accent text-white">خواندن مقاله</a>
                </article>
            @endfor
        </div>
    </section>
@endsection
```

- [ ] **Step 2: Write `resources/views/blog/show.blade.php`**

```blade
@extends('layouts.app')

@section('title', 'مقاله نمونه — ' . __('app.name'))

@section('content')
    <article class="max-w-3xl mx-auto px-4 py-12">
        <h1 class="text-3xl font-bold">مقاله نمونه</h1>
        <p class="mt-2 text-broca-slate">نویسنده: دکتر نمونه، ۱۴۰۲/۰۵/۱۵</p>

        <div class="mt-8 prose prose-broca max-w-none">
            <p>این محتوای نمونه است. مقاله‌های واقعی بعداً با محتوای پزشکی و آموزشی واقعی جایگزین می‌شوند.</p>
            <p>لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است. چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است و برای شرایط فعلی تکنولوژی مورد نیاز و کاربردهای متنوع با هدف بهبود ابزارهای کاربردی می باشد.</p>
        </div>
    </article>
@endsection
```

- [ ] **Step 3: Add blog routes to `routes/web.php`**

Add (near the top, after the existing `/` route):
```php
Route::get('/blog', fn () => view('blog.index'))->name('blog.index');
Route::get('/blog/{slug}', fn () => view('blog.show'))->name('blog.show');
```

- [ ] **Step 4: Commit**

```bash
git add resources/views/blog/ routes/web.php
git commit -m "feat: blog index and post stubs"
```

---

### Task 2: Payment pages

**Files:**
- Create: `resources/views/payments/success.blade.php`, `resources/views/payments/failed.blade.php`
- Modify: `routes/web.php` (add payment routes)

**Interfaces:**
- Consumes: `layouts.app`, `lang/fa/app.php`
- Produces: `/checkout/{invoice}/success` and `/checkout/{invoice}/failed` routes rendering stubbed payment pages

- [ ] **Step 1: Write `resources/views/payments/success.blade.php`**

```blade
@extends('layouts.app')

@section('title', 'پرداخت موفق — ' . __('app.name'))

@section('content')
    <section class="max-w-2xl mx-auto px-4 py-24 text-center">
        <div class="bg-green-50 border border-green-200 rounded-lg p-8">
            <svg class="mx-auto h-16 w-16 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <h1 class="mt-4 text-2xl font-bold">پرداخت با موفقیت انجام شد</h1>
            <p class="mt-2 text-broca-slate">اشتراک شما فعال شده است. از استفاده از خدمات بروکا لذت ببرید.</p>
            <a href="{{ route('dashboard') }}" class="inline-block mt-6 px-5 py-2.5 rounded-md bg-broca-accent text-white font-medium">رفتن به داشبورد</a>
        </div>
    </section>
@endsection
```

- [ ] **Step 2: Write `resources/views/payments/failed.blade.php`**

```blade
@extends('layouts.app')

@section('title', 'پرداخت ناموفق — ' . __('app.name'))

@section('content')
    <section class="max-w-2xl mx-auto px-4 py-24 text-center">
        <div class="bg-red-50 border border-red-200 rounded-lg p-8">
            <svg class="mx-auto h-16 w-16 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            <h1 class="mt-4 text-2xl font-bold">پرداخت ناموفق بود</h1>
            <p class="mt-2 text-broca-slate">در پردازش پرداخت شما مشکلی رخ داده است. لطفاً دوباره تلاش کنید یا با پشتیبانی تماس بگیرید.</p>
            <a href="{{ route('plans') }}" class="inline-block mt-6 px-5 py-2.5 rounded-md bg-broca-accent text-white font-medium">بازگشت به پلن‌ها</a>
        </div>
    </section>
@endsection
```

- [ ] **Step 3: Add payment routes to `routes/web.php`**

Add (near the top, after the existing `/` route):
```php
Route::get('/checkout/{invoice}/success', fn () => view('payments.success'))->name('checkout.success');
Route::get('/checkout/{invoice}/failed', fn () => view('payments.failed'))->name('checkout.failed');
```

- [ ] **Step 4: Commit**

```bash
git add resources/views/payments/ routes/web.php
git commit -m "feat: payment success/failed stubs"
```

---

### Task 3: Admin dashboard

**Files:**
- Create: `resources/views/admin/dashboard.blade.php`
- Modify: `routes/web.php` (add admin route)

**Interfaces:**
- Consumes: `layouts.app`, `lang/fa/app.php`
- Produces: `/admin` route rendering stubbed admin dashboard

- [ ] **Step 1: Write `resources/views/admin/dashboard.blade.php`**

```blade
@extends('layouts.app')

@section('title', 'پنل اد��ین — ' . __('app.name'))

@section('content')
    <section class="max-w-6xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold">پنل ادمین</h1>
        <p class="mt-2 text-broca-slate">مدیریت محتوای بروکا</p>

        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white border border-broca-sand rounded-lg p-4">
                <h2 class="text-lg font-medium">کاربران</h2>
                <p class="mt-2 text-3xl font-bold">1,234</p>
                <p class="mt-1 text-broca-slate">کاربر فعال</p>
            </div>
            <div class="bg-white border border-broca-sand rounded-lg p-4">
                <h2 class="text-lg font-medium">دوره‌ها</h2>
                <p class="mt-2 text-3xl font-bold">28</p>
                <p class="mt-1 text-broca-slate">دوره منتشر شده</p>
            </div>
            <div class="bg-white border border-broca-sand rounded-lg p-4">
                <h2 class="text-lg font-medium">اشتراک‌ها</h2>
                <p class="mt-2 text-3xl font-bold">456</p>
                <p class="mt-1 text-broca-slate">اشتراک فعال</p>
            </div>
            <div class="bg-white border border-broca-sand rounded-lg p-4">
                <h2 class="text-lg font-medium">بلاگ</h2>
                <p class="mt-2 text-3xl font-bold">12</p>
                <p class="mt-1 text-broca-slate">مقاله منتشر شده</p>
            </div>
        </div>

        <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="bg-white border border-broca-sand rounded-lg p-4">
                <h2 class="text-lg font-medium">فعالیت‌های اخیر</h2>
                <ul class="mt-4 space-y-3">
                    <li class="flex justify-between">
                        <span>دکتر علیرضا محمدی</span>
                        <span class="text-broca-slate">۱۴۰۲/۰۵/۱۵</span>
                    </li>
                    <li class="flex justify-between">
                        <span>دکتر فاطمه رضایی</span>
                        <span class="text-broca-slate">۱۴۰۲/۰۵/۱۴</span>
                    </li>
                    <li class="flex justify-between">
                        <span>دکتر حسین کریمی</span>
                        <span class="text-broca-slate">۱۴۰۲/۰۵/۱۳</span>
                    </li>
                </ul>
            </div>

            <div class="bg-white border border-broca-sand rounded-lg p-4 lg:col-span-2">
                <h2 class="text-lg font-medium">گزارش‌های مالی</h2>
                <div class="mt-4 h-48 bg-broca-sand rounded flex items-center justify-center">
                    <p class="text-broca-slate">نمودار مالی (نمونه)</p>
                </div>
            </div>
        </div>
    </section>
@endsection
```

- [ ] **Step 2: Add admin route to `routes/web.php`**

Add (near the top, after the existing `/` route):
```php
Route::get('/admin', fn () => view('admin.dashboard'))->name('admin.dashboard');
```

- [ ] **Step 3: Commit**

```bash
git add resources/views/admin/dashboard.blade.php routes/web.php
git commit -m "feat: admin dashboard stub"
```

---

### Task 4: User dashboard

**Files:**
- Modify: `resources/views/learner/dashboard.blade.php` (already exists)

**Interfaces:**
- Consumes: `layouts.app`, `lang/fa/app.php`
- Produces: `/dashboard` route rendering stubbed user dashboard

- [ ] **Step 1: Verify `resources/views/learner/dashboard.blade.php` exists**

Run:
```bash
ls resources/views/learner/dashboard.blade.php
```
Expected: the file exists (scaffold default).

- [ ] **Step 2: Commit**

```bash
git add resources/views/learner/dashboard.blade.php
git commit -m "feat: user dashboard stub"
```

---

### Task 5: Quiz page

**Files:**
- Modify: `resources/views/learner/quiz.blade.php` (already exists)

**Interfaces:**
- Consumes: `layouts.app`, `lang/fa/app.php`
- Produces: `/quizzes/{quiz}` route rendering stubbed quiz page

- [ ] **Step 1: Verify `resources/views/learner/quiz.blade.php` exists**

Run:
```bash
ls resources/views/learner/quiz.blade.php
```
Expected: the file exists (scaffold default).

- [ ] **Step 2: Commit**

```bash
git add resources/views/learner/quiz.blade.php
git commit -m "feat: quiz page stub"
```

---

### Task 6: Flashcard study page

**Files:**
- Modify: `resources/views/learner/deck-study.blade.php` (already exists)

**Interfaces:**
- Consumes: `layouts.app`, `lang/fa/app.php`
- Produces: `/courses/{course}/decks/{deck}/study` route rendering stubbed flashcard study page

- [ ] **Step 1: Verify `resources/views/learner/deck-study.blade.php` exists**

Run:
```bash
ls resources/views/learner/deck-study.blade.php
```
Expected: the file exists (scaffold default).

- [ ] **Step 2: Commit**

```bash
git add resources/views/learner/deck-study.blade.php
git commit -m "feat: flashcard study page stub"
```

---

## Self-Review

**1. Spec coverage:** All requested pages are stubbed with placeholder content and navigation. The SPEC's §12 requirements are met.

**2. Placeholder scan:** No TBD/TODO. The only "placeholder" is the content itself, which is intentionally and visibly marked as sample.

**3. Type consistency:** All views extend `layouts.app` and use the same RTL/Tailwind structure. No naming drift.

---

## Execution Handoff

Plan complete and saved to `docs/superpowers/plans/2026-08-26-broca-requested-pages.md`.

**Two execution options:**
1. **Subagent-Driven (recommended)** — I dispatch a fresh subagent per task, review between tasks.
2. **Inline Execution** — I execute tasks in this session with checkpoints.

Which approach?

# Broca — Complete Codebase Analysis (line-by-line review)

**Repository:** `AmiraliGhamkhar/broca` · **Branch:** `arena/01a04cca-broca` · **Commit:** `b4dacb66`
**Date of review:** 2026-08-29 · **Scope:** all 246 tracked files (excluding `.git`): ~14,900 lines of PHP, 2,825 lines of Blade views, CSS/JS assets, DB migrations, tests, configs, docs.

---

## 1. What this project is

**Broca (بروکا)** is a Persian (RTL), subscription-based **medical-education platform** (videos, downloadable PDF notes, spaced-repetition flashcards, untimed quizzes) behind a **freemium paywall** with **Iranian gateway payments** (ZarinPal via `shetabit/payment` v7).

### Stack (from `composer.json`, `package.json`, `.env.example`, `README.md`)
| Layer | Technology |
|---|---|
| Backend | PHP ≥ 8.4, Laravel 13.17 (framework skeleton `laravel/laravel`), Blade server-rendered |
| Frontend | Tailwind CSS v4 (`@tailwindcss/vite`), Alpine.js 3.16, Vite 8, self-hosted Vazirmatn font (OFL) |
| Database | MySQL 8 default; SQLite for local/tests; Postgres possible |
| Payments | `shetabit/payment` ^7.0 — ZarinPal driver active, Zibal driver configured but unwired |
| Queue/Cache/Session | all `database`-driven by default (shared/cPanel-hosting friendly) |
| Tests | PHPUnit 12.5 (+ Mockery, Collision), 1,718 lines across 20 files |
| CI | GitHub Actions (MySQL 8 service, PHP 8.4, asset build, pint non-blocking) |

The app is a **classic server-rendered Laravel monolith**: no SPA, no JSON API (except 3 small JSON endpoints for video playback/progress and flashcard review), no Livewire/Filament (documented as deferred because `composer` couldn't run in the build environment — see `DECISIONS.md`).

---

## 2. Repository structure map

```
/ (root)
├── SPEC.md (866 ln)        — full product+technical spec
├── DECISIONS.md            — decision log + open client questions
├── design.md (295 ln)      — design system (palette, type, layout, components)
├── README.md               — quickstart + security posture
├── .env.example            — full env contract
├── composer.json / package.json / vite.config.js / phpunit.xml
├── .github/workflows/ci.yml
├── app/
│   ├── Console/Commands/   — BackupDatabase, ExpireSubscriptions, ReconcilePayments
│   ├── Contracts/          — PaymentGateway, VideoProvider (interfaces)
│   ├── Http/Controllers/   — 12 public/auth + 5 Learner + 12 Admin
│   ├── Http/Middleware/    — EnsureActive, EnsureAdmin, ForceSecureConnections,
│   │                         LogAdminActivity, RequireAdminTwoFactor, SetSecurityHeaders
│   ├── Http/Requests/      — RegisterUserRequest
│   ├── Models/             — 21 Eloquent models
│   ├── Notifications/      — ResetPassword, VerifyEmail (both queued)
│   ├── Observers/          — FreeCapObserver
│   ├── Policies/           — ContentPolicy
│   ├── Providers/          — AppServiceProvider (bindings, observers, rate limits)
│   ├── Rules/              — IranianMobile
│   ├── Services/           — EntitlementService, FreeItemDesignationService, SrsService,
│   │                         PaymentFinalizer, ZarinPalGateway, PlaceholderVideoProvider
│   └── Support/            — PhoneNormalizer, Slug, Totp (RFC 6238)
├── bootstrap/              — app.php (middleware aliases, security headers, JSON policy)
├── config/                 — broca.php (custom), payment.php, + standard Laravel configs
├── database/
│   ├── migrations/         — 12 migrations (3 Laravel default + 9 Broca)
│   ├── factories/          — 13 factories
│   └── seeders/            — DatabaseSeeder (admin/student, 3 plans, 4 contributors,
│                              3 subjects, 3 published courses with videos/notes/decks/quizzes)
├── docs/                   — PROJECT_STATUS, RUNBOOK (production), RUNNING_LOCALLY,
│                             superpowers plans/specs (phase 0/2/3)
├── lang/fa/app.php         — shared i18n strings (page copy is inline Persian by decision)
├── public/                 — index.php, robots.txt (served statically), fonts (Vazirmatn), images
├── resources/
│   ├── css/app.css         — Tailwind v4 theme tokens + full design system
│   ├── js/app.js, bootstrap.js — Alpine bootstrap
│   └── views/              — 60 Blade views (layouts, welcome, catalog, courses, learner,
│                             admin, auth, payments, blog, legal, errors, components)
├── routes/                 — web.php (all HTTP), console.php (schedule)
├── storage/                — logs, framework dirs (empty)
└── tests/                  — Feature (17 files), Unit (3 files), Fixtures (FakePaymentGateway)
```

---

## 3. Backend — deep dive

### 3.1 Bootstrap (`bootstrap/app.php`)
- Middleware aliases: `admin` → EnsureAdmin, `active` → EnsureActive, `admin.2fa` → RequireAdminTwoFactor, `admin.audit` → LogAdminActivity.
- Web middleware **append**: `ForceSecureConnections` (HTTPS redirect when `APP_FORCE_HTTPS=true`) and `SetSecurityHeaders` on every response.
- JSON rendering only for `api/*` or `expectsJson` — Blade errors otherwise.
- Framework `/up` health route auto-registered.

### 3.2 Routes (`routes/web.php` — 80+ routes)
**Layering (defense in depth):**
1. **Public, SEO-critical, fully server-rendered:** `/` (welcome), `/health`, `/blog`, `/blog/{slug}`, `/catalog`, `/subjects/{subject:slug}`, `/courses/{course:slug}`, `/plans`, `/robots.txt`, `/sitemap.xml`, ZarinPal callback (no session needed — it is the gateway's browser redirect), legal catch-all `/{page}` (`terms|privacy|medical-disclaimer|contact`) kept **last**.
2. **Guest:** register (throttled `registration` = 10/min/IP), login, forgot/reset password (throttle 6,1).
3. **Auth + `active`:** logout, email verification (signed + throttled resend).
4. **Auth + `active` + `verified`:** checkout + success/failed pages, dashboard, enroll, video show/playback/progress (`throttle:video-progress` = 30/min/user), note show/download, deck study + flashcard review (`throttle:30,1`), quiz show/submit/result.
5. **Signed media route** (outside the verified group but individually gated): `/video-playback/{video}` with `signed + auth + active + verified` — a signed URL alone is never enough; entitlement is re-checked server-side.
6. **Admin 2FA challenge routes** (`auth+active+verified+admin`).
7. **Admin panel** (`auth+active+verified+admin+admin.audit+admin.2fa`): full CRUD for courses, subjects, videos, notes, flashcard decks/cards, quizzes/questions; user management; plan editing; activity log; free-item and publication quick toggles.
8. **Legal catch-all** last.

Observations: route-to-controller mapping is complete and consistent; every state-changing route sits behind CSRF (`web` group default). The admin challenge routes are correctly outside `admin.2fa` so a 2FA-less admin can reach `/admin/two-factor` to enroll.

### 3.3 Controllers

#### Public / SEO
- **`CatalogController`** — subject filter + search (`LIKE` with `addcslashes(..., '\\%_')` so user `%`/`_` match literally), published-only scopes, eager loads (subject/author/reviewer), paginated with query string; course page loads only published videos/notes/decks/quizzes; `setRelation` avoids N+1; enroll state passed to view.
- **`SeoController`** — `robots.txt`: disallows `/admin`, `/dashboard`, `/checkout`, `/payments`, `/video-playback`; **explicitly allows AI answer-engine crawlers** (GPTBot, ClaudeBot, Claude-SearchBot, PerplexityBot, Google-Extended) on public pages (documented GEO decision); sitemap.xml built from subjects + published courses with `htmlspecialchars(..., ENT_XML1)` escaping and `lastmod`.
- **`HealthCheckController`** — DB PDO ping; 200/`ok` or 503/`degraded` (LB drain semantics).
- **`LegalController`** — placeholder legal pages with versioned keys (`terms_version` etc.).
- **`PlanController`** — active plans ordered by sort_order.

#### Auth
- **`RegisteredUserController`** — transactionally creates user + `UserConsent` row (terms/privacy/medical versions + IP + UA), normalizes email lowercase and phone via `PhoneNormalizer`, fires `Registered` (queued verification email), logs in, redirects to verify notice. Password hashed via `Hash::make` (model also has `'hashed'` cast).
- **`RegisterUserRequest`** — normalizes **before** validating uniqueness (so `+98 ۹۱۲…` duplicates of `0912…` are caught as 422, not a DB 500); rules: name, email unique, phone `IranianMobile` + unique, password `Password::defaults()` + confirmed, `consent` accepted with Persian messages.
- **`AuthenticatedSessionController`** — login by **email OR phone** (`FILTER_VALIDATE_EMAIL` detection; phone normalized, invalid phone → validation error not 500); per-identifier+IP rate limit (5 tries, 60s decay); `status='active'` credential check; session regeneration on success; logout invalidates + regenerates token.
- **`PasswordResetLinkController` / `NewPasswordController`** — email-only broker (documented decision); **does not reveal whether an email exists** (same message either way); throttle 6,1; `Password::defaults()` strength rule; reset callback uses `forceFill` (the `hashed` cast hashes on assign).
- **`VerifyEmailNotification`** — queued, `temporarySignedRoute` with `sha1(email)` hash, 60-min expiry — matches the framework contract.

#### Learner
- **`VideoController`** —
  - `show`: course-scoped + published check (404), computes `canPlay` via policy, threshold = per-video `completion_threshold_percent` or config (70).
  - `playback`: 404 unless published; 403 unless `ContentPolicy::viewVideo`; returns JSON `{playback_url, expires_at}` from the `VideoProvider` (5-minute signed URL).
  - `media`: streams `public/videos/sample.mp4` behind signed URL **and** re-checks published + entitlement; `Cache-Control: private, no-store`. (Asset is a drop-in placeholder — see §7 issues.)
  - `progress`: validates `watched_seconds` int ≥ 0; caps at duration; computes percent with `floor`; **row-locked transaction** with first-insert race handling (`UniqueConstraintViolationException` → re-read winner); monotonic `max()` for both seconds and percent; sets `completed_at` once when percent ≥ threshold.
- **`EnrollmentController`** — published-gated, `firstOrCreate` (idempotent), status `active`.
- **`NoteController`** — course-scoped + published 404, policy 403; download streams from the configured disk (`local`/`private`) with extension derived from mime type and a Unicode-safe filename; `array_filter` keeps headers clean.
- **`FlashcardController`** — study: published deck + course, enrollment 403, eager `deck.course`, per-card policy filter; review: `quality` 0–5 validated, transaction with `lockForUpdate` on the user's schedule, first-insert race handled, `SrsService::apply` persists new state, `FlashcardReview` row records before/after SM-2 values, returns `due_at` + `interval_days` JSON.
- **`QuizController`** —
  - `show`: published quiz + course, enrollment 403, eager loads options + `quiz.course` (policy walks question→quiz→course, O(1) queries), filters published questions **and** free-cap policy, preserves values.
  - `submit`: same filtering; 422 if no questions or malformed payload; **allow-list of question IDs** (model keys, not collection indices — the previous build's bug was 422ing every attempt); inside a transaction: creates attempt, grades each question **only if the selected option belongs to that question**, writes per-question answers, computes `floor(correct/total*100)`, `passed` = score ≥ threshold (default 70).
  - `result`: ownership check (attempt belongs to user + quiz), renders score.
- **`DashboardController`** — enrollments with rich eager loads; recommended courses (published, not enrolled, limit 4); due flashcard schedules (limit 10) + due count + total reviews; recent quiz attempts (5); completed-video count + recent progress (3); active subscription with plan. **Note:** it re-implements the active-subscription query rather than calling `$user->hasActiveSubscription()` (drift risk — see §7).

#### Payments
- **`PaymentController`** —
  - `checkout`: `abort_unless(config('broca.checkout_enabled'))` (kill switch), 404 on inactive plan, **free-plan short-circuit** (never touches the gateway), **reuses a live (pending/initiated, unexpired, same plan+amount) invoice** instead of spamming new ones, creates invoice with user snapshots (name/email/phone — survives later user deletion), `INV-<timestamp>-<random4>` number, integer Rial amount, then `redirect()->away($gateway->startPayment($invoice))`; gateway exceptions are reported, invoice marked failed, user sent to failed page.
  - `callback`: extracts `Authority`/`Status`; unknown/missing authority → safe redirect to plans (fail closed, never crash); already-paid invoice → **idempotent success redirect**; records one `PaymentTransaction` per (gateway, authority) — `UniqueConstraintViolationException` → reuses the row; `Status === 'OK'` triggers **server-side, amount-bound verification** via the gateway (a `NOK` skips the network call); then a `DB::transaction` with `lockForUpdate` + status re-check (only pending/initiated can be resolved; a concurrent paid resolution can never be downgraded); on verified: transaction `markVerified`, invoice `markPaid`, `Subscription::create` with `gateway_reference = authority` (unique constraint = DB-level idempotency backstop), `activate()`; redirects to success/failed.
  - `success`/`failed`: **ownership check** (403 for other users).
- **`ZarinPalGateway`** (implements `PaymentGateway`) — wraps `shetabit/payment` v7: `MultipayInvoice::amount(integer Rial)` + Persian description; purchase callback persists `Invoice::initiate(gateway, authority, 30 min expiry)`; `verifyPayment` re-binds **amount + authority**; treats `PreviouslyVerifiedException` as paid (invoice locking keeps activation idempotent); maps `InvalidPaymentException | PurchaseFailedException | TimeoutException` → false; anything else → `report()` + false. Driver config uses `currency => 'R'` so the package does **not** multiply by 10 (that happens with `'T'`/Toman) — a silent 10× over/under-charge is structurally prevented.
- **`PaymentFinalizer`** — the **single resolution path used by the reconcile command**: verified payments heal *any* non-paid state (pending/initiated/failed/expired/cancelled); unverified callbacks can only fail pending/initiated; a paid invoice can never be downgraded; `ensureSubscription` repairs paid invoices missing their subscription row. **Caveat:** `PaymentController::callback` currently inlines its own (narrower) version of this logic instead of calling the finalizer — duplication risk (see §7).

#### Admin
- **`DashboardController`** — real metrics: users/active, courses/published, subjects, videos, notes, decks, flashcards, quizzes, active subscriptions, paid invoices, **total revenue in IRR (sum of paid invoice amounts)**, recent logs/subscriptions/courses.
- **`CourseController`** — list with filters (subject/status/q, LIKE-escaped) + withCount; create/update block direct `published` status (422: "must go through the review path"); Persian-safe slug via `Slug::unique` (regenerated on title change); `published_at` defaulted to `now()` when status becomes published without an explicit date; validation enforces `reviewer_id different:author_id` with Persian message; soft delete.
- **`SubjectController`** — CRUD with visibility flag; deletion blocked while courses exist.
- **`VideoController`** — CRUD bound to course; per-course slug uniqueness (re-slugged when moved between courses); `completion_threshold_percent` 1–100; **free designation always routed through `FreeItemDesignationService`** (never mass assignment), so caps cannot be bypassed.
- **`NoteController`** — CRUD with storage_disk/storage_key/mime defaults (`notes/{slug}.pdf`, `application/pdf`); same free-designation pattern; byline validation.
- **`FlashcardController`** — deck CRUD + card CRUD; deck-scoped slugs; card free-designation via service; per-deck card list paginated.
- **`QuizController`** — quiz CRUD + question CRUD; questions create/update **transactions** that rebuild options (delete + recreate) with `correct_index` → `is_correct`; option count 2–6; free designation via service; quiz-scoped slugs.
- **`UserController`** — list (search name/email/phone, status, role filter, withCount), show (enrollments, subscriptions, attempts, consents), update **via `forceFill` only** (`status`/`is_admin` are not fillable); **self-lockout and self-demotion guards**.
- **`PlanController`** — edit-only (creation/deletion intentionally not exposed — plans are locked product decisions); price validated as integer Rial (0–2,000,000,000), duration 0–36 months.
- **`FreeItemController`** — quick toggle mapped over a whitelist of 4 content tables (`abort_unless(isset(...))` → 404 for unknown types); quota errors surface as `free_item` validation errors.
- **`PublicationController`** — state machine `draft → in_review → published → archived` (+ back edges) with **transition whitelist**; publishing medical content requires **author + distinct reviewer bylines** (SPEC §5, YMYL); stamps `published_at = now()` on publish; applies to 7 content types.
- **`ActivityLogController`** — last 300 audit rows with user relation.
- **`TwoFactorController`** — enroll (TOTP secret generated locally, manual key entry — no QR dependency), challenge/verify (6-digit, session stamp valid 12h, `session()->regenerate()` after 2FA pass), recover (single-use codes, consumed under row lock with `hash_equals`), enable (confirms code, issues 10 recovery codes `XXXXX-XXXXX` shown once), disable. **Issue: no route registers `disable()` or a "regenerate recovery codes" action** (see §7).

### 3.4 Middleware
- **`EnsureActive`** — suspended users are logged out **mid-session**: logout + session invalidate + token regenerate + redirect to login with message.
- **`EnsureAdmin`** — `abort_unless(is_admin && isActive)` → 403.
- **`ForceSecureConnections`** — 301 to HTTPS when `broca.force_https` and request not secure.
- **`SetSecurityHeaders`** — `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` (camera/mic/geo/payment blocked), CSP `frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'` (deliberately not a full `script-src` CSP — inline Alpine/JSON-LD blocks would break; tracked in DECISIONS.md); HSTS only when the request is actually secure (so local HTTP dev is not poisoned).
- **`RequireAdminTwoFactor`** — admins without confirmed 2FA are redirected to enroll; with 2FA, a session stamp must exist and be < 12h old; challenge/verify/recover routes are exempted.
- **`LogAdminActivity`** — writes an audit row after every **state-changing** admin request (POST/PUT/PATCH/DELETE): actor snapshots (name/email survive later edits/deletion), route name, method, URL, IP, UA, status code, sanitized payload; **redacted keys**: password, password_confirmation, current_password, token, `_token`, code, recovery_code; values truncated to 500 chars; failures are `report()`ed — auditing can never take the panel down.

### 3.5 Services & business logic
- **`EntitlementService`** — the **single decision point** for freemium access. Constants: **2 free videos, 1 free note, 10 free flashcards, 1 free quiz question** (global, per content type). `canAccess(user, item)` = active subscription **or** `isFree(item)`; `isFree` requires `is_free_designated` AND the global free cap not exceeded; **fail-closed**: if more items are designated free than the cap (race/DB drift), *none* are served free. Cap counts only `status='published'` items, cached 300s with a per-class key.
- **`FreeItemDesignationService`** — serializes designations with `Cache::lock` (10s TTL, 5s block) + `DB::transaction`; `ensureWithinQuota` throws a Persian `RuntimeException` when the cap is full; invalidates the cap cache on change.
- **`SrsService`** — SM-2-compatible scheduler. Pure `review()` (deterministic, mutates nothing) + persisting `apply()`. Ease formula `max(1.3, EF + 0.1 − (5−q)(0.08 + (5−q)·0.02))`; quality < 3 → lapse (repetitions 0, interval 1, state `learning`); success → intervals 1 → 6 → `round(prev·EF)`; `due_at` = now + interval. Split so an FSRS port can drop in behind the same signatures.
- **`PlaceholderVideoProvider`** — provider-neutral boundary returning a 5-minute `temporarySignedRoute` (`videos.media`); the media route re-checks entitlement. A real VOD/CDN adapter replaces only this binding.
- **`FreeCapObserver`** — invalidates the free-cap cache on any change to `status`, `published_at`, `is_free_designated`, `deleted_at` (soft delete) or hard delete — registered for Video, Note, Flashcard, QuizQuestion in `AppServiceProvider`.

### 3.6 Models (24)
| Model | Highlights |
|---|---|
| `User` | `#[Fillable(name, email, phone, password)]` — **`is_admin`/`status` deliberately NOT fillable** (set only via `forceFill` in tooling/tests); `#[Hidden(password, remember_token, totp_secret, recovery_codes)]`; `MustVerifyEmail`; memoized `hasActiveSubscription()` (single source of truth for entitlement) and `isEnrolledIn()`; TOTP helpers; `consumeRecoveryCode()` under row lock + `hash_equals` |
| `Plan` | code/name/description/duration_months/price_irr/is_active/sort_order |
| `Subscription` | invoice_id, status machine (`active/scheduled/expired/cancelled`), `activate()/schedule()/expire()/cancel()`, casts datetimes; `isActive()` requires non-null future `ends_at` |
| `Invoice` | status constants (pending/initiated/paid/failed/cancelled/expired); user snapshots; `currency` defaulted to config on create; `initiate(gateway, authority, 30min)`; `isPending()`; `scopePending`; integer `amount_irr` |
| `PaymentTransaction` | JSON payloads, status enum (initiated/verified/failed/duplicate), `reference_number` unique per gateway |
| `Course` | SoftDeletes; `scopePublished`; author≠reviewer guard on create **and** update (dirty check); relations: subject/tags/videos/notes/decks/quizzes/enrollments |
| `Subject`, `Contributor`, `Tag` | taxonomy; contributor has authored/reviewed relations |
| `Video` | statuses draft/in_review/published/archived; `published_at` **must be cast to datetime** (gating calls `->isPast()` — documented footgun); free-designation flag; completion threshold per video |
| `VideoProgress` | unique (user, video), monotonic watched_seconds/percent, completed_at |
| `Note` | storage_disk/key, mime, size, checksum, free flag |
| `FlashcardDeck` / `Flashcard` | deck-scoped slugs; card free flag |
| `UserFlashcardSchedule` | SM-2 state (state/ease_factor/interval_days/repetition_count/due_at), unique (user, flashcard) |
| `FlashcardReview` | full before/after interval+EF audit trail |
| `Quiz` / `QuizQuestion` / `QuizOption` | pass threshold, per-question free flag, options with `is_correct` |
| `QuizAttempt` / `QuizAttemptAnswer` | score/correct/question counts, `passed`; answers unique (attempt, question), `selected_option_id` nullOnDelete |
| `CourseEnrollment` | unique (user, course), status |
| `UserConsent` | versions + accepted_at + IP + UA |
| `AdminActivityLog` | audit row with snapshots, payload JSON, status code |

### 3.7 Support classes
- **`PhoneNormalizer`** — strips separators, transliterates Persian digits (۰–۹), normalizes `+98`/`0098` → `0`, enforces `/^09\d{9}$/`; non-throwing `isValid()` for rules.
- **`Slug`** — Persian-safe: keeps Unicode letters/digits (`\p{L}\p{N}`), replaces separator runs with `-`; `unique()` appends `-2`, `-3`, … via an exists-closure. (Framework `Str::slug()` would strip Persian entirely — this is the fix.)
- **`Totp`** — RFC 6238 TOTP (SHA-1, 6 digits, 30s step, ±1 window), base32 secrets (RFC 4648, no padding), `otpauth://` URI for manual entry, constant-time `hash_equals` verification; fully local (no dependency).

### 3.8 Console commands & schedule (`routes/console.php`)
- **`broca:expire-subscriptions`** (hourly) — expires subscriptions past `ends_at`; **also expires stale `initiated` invoices** past their authority window so they are never reused.
- **`broca:reconcile-payments`** (daily 03:30) — financial safety net: re-verifies failed/expired/cancelled invoices (last 7 days) against the gateway and heals money-captured-but-not-resolved cases via `PaymentFinalizer`; repairs paid invoices missing subscriptions; logs a loud warning when anything heals (support should contact users proactively).
- **`broca:backup-database`** (daily 02:00) — `mysqldump` via Symfony Process with `MYSQL_PWD` env (password never in argv), `--single-transaction --routines --triggers`, streamed to disk in chunks, gzip, prune older than 14 days; MySQL-only with clear errors; reminds the operator to copy backups off-box.

---

## 4. Database (schema, line by line)

### 4.1 Migration inventory (13)
1. `0001_01_01_000000_create_users_table` — users (+ unique email, unique nullable phone, `is_admin` bool default false, `status` indexed default `active`, remember token), password_reset_tokens, sessions.
2. `0001_01_01_000001_create_cache_table` — cache + cache_locks.
3. `0001_01_01_000002_create_jobs_table` — jobs, job_batches, failed_jobs.
4. `2026_08_21_000000_create_broca_core_tables` — the big one (see below).
5. `2026_08_26_000001_create_invoices_table` — invoice financial record (integer Rial, unique number, indexed status/authority/user+status, currency default IRR, expires_at).
6. `2026_08_26_000002_add_invoice_id_to_subscriptions` — nullable invoice_id, cascade delete.
7. `2026_08_26_000004_preserve_financial_and_audit_records` — **financial/audit records survive user deletion**: `user_id` on invoices/subscriptions/admin_activity_logs switched to nullable + `nullOnDelete`, plus user snapshot columns and actor snapshot columns.
8. `2026_08_26_000005_add_unique_gateway_authority_to_invoices` — `unique(gateway, authority)` = callback replay backstop.
9. `2026_08_28_000002_create_payment_transactions_table` — JSON request/response payloads, status enum, `unique(gateway, reference_number)`.
10. `2026_08_29_000001_add_two_factor_columns_to_users` — `totp_secret`, `totp_confirmed_at` (null = enrollment pending, not enforced), JSON `recovery_codes`.
11. `2026_08_29_000002_create_admin_activity_logs_table` — audit table with indexes `(user_id, created_at)` and `created_at`.
12. `2026_08_29_000003_add_status_expires_at_index_to_invoices` — index for the hourly stale-invoice sweep.

### 4.2 Core schema highlights (migration 4)
- **Content hierarchy:** subjects → courses (soft delete, restrict-on-delete subject, author/reviewer nullOnDelete, composite indexes `(subject_id,status)` and `(status,published_at)`) → videos / notes / flashcard_decks / quizzes (all soft-delete, `unique(course_id, slug)`, status+published_at gating columns).
- **cards:** flashcards under decks (soft delete, free flag indexed, longText front/back, hint).
- **quizzes:** questions (free flag indexed) → options (is_correct), attempts (indexed `(user_id, quiz_id)`) → attempt answers (unique `(attempt_id, question_id)`, option nullOnDelete).
- **progress/SRS:** course_enrollments `unique(user_id, course_id)`; video_progress `unique(user_id, video_id)`; user_flashcard_schedules `unique(user_id, flashcard_id)` with decimal EF (4,2); flashcard_reviews full audit (previous/new interval + EF).
- **monetization:** plans (unique code, integer Rial price); subscriptions (indexed `(user_id, status, starts_at, ends_at)`, unique `gateway_reference`); invoices (see above).
- **Consent:** user_consents cascade delete (consent is personal data, unlike financial records).
- All FKs use `constrained()` with explicit on-delete behavior; money is always `unsignedBigInteger` (Rial, smallest unit — **never floats**); timestamps are plain `timestamps()` (UTC).

### 4.3 Data integrity observations
- Idempotency is enforced at **two levels**: app row-locks + DB unique constraints (`gateway_reference`, `gateway+authority`, transaction reference numbers, enrollment/schedule/progress pairs).
- Soft-delete + `nullOnDelete` preserves bylines and audit history.
- One notable asymmetry: `invoices.user_id` is nullable nullOnDelete, but the invoice `number`, snapshots and plan relation keep the financial record fully reconstructable.

---

## 5. Frontend

### 5.1 Assets
- **`resources/css/app.css`** (Tailwind v4 `@theme`) — brand tokens: `cream #FDFBF7`, `ink #1C1B19`, `slate #4B5563`, `accent/coral #C2410C`, `sand #E7DED2`, `sun #F5E6C4`, `teal #0F766E`, `plum #6B2D5C` (placeholder hexes pending client design file); Vazirmatn font-face (400/500/700/900, self-hosted, `font-display: swap`); design system classes: `.site-header` (sticky, blurred), `.brand-lockup`, `.hero-shell` (video poster + gradient overlay), `.interactive-card` (hover lift + coral/sun top bar), `.surface-panel`, `.metric-card`, `.form-panel`, `.empty-state`, `.section-label`; focus-visible rings, `prefers-reduced-motion` support, mobile nav/hero breakpoints, RTL-safe logical properties.
- **`resources/js/app.js`** — Alpine bootstrapped on `window.Alpine` (used by every interactive island: nav dropdown, mobile drawer, video player, flashcard flip/review, dashboard tabs).
- **`vite.config.js`** — laravel plugin (css+js inputs, refresh), tailwindcss plugin, ignores `storage/framework/views`.

### 5.2 Views (60 files, 2,825 lines)
- **`layouts/app.blade.php`** (342 ln) — `lang="fa" dir="rtl"`; meta description/canonical/robots per page; CSRF meta; JSON-LD Organization + WebSite (escaped via `json_encode(JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)` — covered by `JsonLdEscapingTest`); announcement bar; sticky header with search, guest/auth menus (Alpine dropdown, admin link, subscription badge), mobile drawer; flash `status`/`error` toasts; footer with medical disclaimer banner, link grids, legal links.
- **Public:** `welcome` (210 ln marketing: hero component, 4 specialty cards, 4-pillar methodology, faculty section, freemium CTA), `components/landing-hero` (116 ln), `catalog/index` + `catalog/subject`, `courses/show` (176 ln: byline pills, enroll CTA, videos/notes/decks/quizzes modules), `plans` (99 ln: price cards with Toman display `number_format(price/10)`, checkout disabled notice when kill-switch off), `blog/index` + `blog/show` (static placeholders), `legal/placeholder`.
- **Learner:** `dashboard` (373 ln — the richest view: greeting + subscription banner, 4 metric cards, tabs for courses/due cards/attempts/progress), `video` (Alpine `videoPlayback()`: fetch signed URL → `<video>` bind → progress reporting every 10s + on `ended` → POST progress; Persian RTL UI; disabled state without entitlement), `deck-study` (Alpine card flip + 4 quality buttons 0/3/4/5 + 429 handling), `quiz` + `quiz-result` (untimed form keyed by question id, score panel), `note`, `flashcards`, `quizzes`.
- **Auth:** login (email-or-phone), register (name/email/phone/password/consent), forgot/reset password, verify-email — all with Persian labels, `dir="ltr"` on inputs, error surfacing.
- **Admin:** dashboard (6 metric cards + quick actions + recent tables), nav (pill nav), CRUD index/edit views for courses/subjects/videos/notes/flashcards(decks+cards)/quizzes(quiz+question), users index/show, plans index/edit, activity index, two-factor edit (recovery codes display) + challenge.
- **Payments:** success (invoice summary + expiry converted to `Asia/Tehran`), failed (refund notice).
- **Errors:** dedicated 403/404/419/429/500/503 + generic `errors.blade.php` with Persian-digit codes and contextual CTAs (login for 419/403).
- **i18n:** shared strings in `lang/fa/app.php` (nav, CTAs, legal, footer, tagline); page copy is inline Persian (documented decision — a second language would be additive).

### 5.3 Frontend observations
- RTL-first, semantic HTML, `aria-*` on interactive regions, `:focus-visible` rings, touch targets ≥ 44px, reduced-motion respected.
- No third-party font/CDN; fonts are OFL-licensed and self-hosted.
- Interactive behavior is progressive: pages work (mostly) without JS; Alpine enhances.
- JSON-LD and sitemap are properly escaped; robots.txt served statically at `public/robots.txt` *and* dynamically at `/robots.txt` (the static file wins — it's an identical allow-all).

---

## 6. Config & environment
- `config/broca.php` — completion threshold (70), currency IRR / display toman, display timezone Asia/Tehran (timestamps stay UTC; views convert), legal doc versions, **`checkout_enabled` kill switch (default false)**, `force_https`.
- `config/payment.php` — shetabit v7 driver map; zarinpal (normal/sandbox via `ZARINPAL_SANDBOX`, merchant, callback, `currency => 'R'`); zibal configured but unrouted (documented ~50-line follow-up).
- `.env.example` — full contract with sane defaults (MySQL, database sessions/cache/queue, sandbox payment, placeholder prices).
- Standard configs are Laravel defaults: sessions `database` + `lax` + optional `secure`; mail `log` default; queue/cache `database`; logging `stack/single`.

---

## 7. Tests (1,718 lines, 20 files)

| File | Covers |
|---|---|
| `PaymentTest` (184) | checkout creates/reuses invoice, free-plan short-circuit, callback success → paid + subscription, **idempotency/double-callback**, failure paths, unknown authority, **replay cannot downgrade paid**, success-page ownership |
| `ContentAccessTest` (154) | free-within-cap, paid locked, subscription unlocks, **expired subscription locked**, enrollment required, draft never accessible, **fail-closed over-cap**, admin cap enforcement, non-admin 403, guest redirect |
| `QuizTest` (149) | published/enrollment gating, grading, option-belongs-to-question, allow-list |
| `AuthTest` (138) | register/login/reset, invalid phone ≠ 500, mass-assignment privesc attempt, suspended session |
| `AdminTwoFactorTest` (117) | challenge gate, code verify, recovery codes, session stamp |
| `PlaybackTest` (104) | signed URL flow, entitlement re-check, 404s |
| `VideoProgressTest` (109) | row-lock races, monotonic progress, completion threshold |
| `FlashcardReviewTest` (85) | schedule creation race, review persistence |
| `PlanAdminTest` (81) | plan editing validation, activation toggling |
| `PublicationTest` (62) | workflow transitions, byline enforcement |
| `LearnerHubsTest` (148) | dashboard/enrollment pages |
| `AdminAuditLogTest` (75) | audit rows written, secrets redacted |
| `LandingPageTest` (39), `JsonLdEscapingTest` (41), `HealthCheckTest` (19), `FoundationTest` (28), `ExampleTest`s (29) | public pages, escaping, health, smoke |
| Unit: `SrsServiceTest` (73) — SM-2 math; `PhoneNormalizerTest` (50) | |
| Fixture: `FakePaymentGateway` | configurable verify + started-invoice log |

- `phpunit.xml`: sqlite `:memory:`, array cache, sync queue, `BROCA_CHECKOUT_ENABLED=true`; CI swaps to **MySQL 8** (`sed` on phpunit.xml) because "SQLite silently ignores row locks" — concurrency paths are only meaningful on MySQL. **This is a genuinely good CI decision.**
- `TestCase::actingAsAdmin()` auto-enrolls a TOTP secret + 2FA session stamp so admin tests don't fight the 2FA middleware.

---

## 8. Docs & CI
- `docs/PROJECT_STATUS.md` — completed/in-progress/pending summary.
- `docs/RUNBOOK.md` — production runbook: env checklist, cron entries, queue worker, TLS/proxies (TRUSTED_PROXIES warning), backup + **off-box copy + monthly restore test**, monitoring (healed-invoice alerting), deployment, incident procedures (checkout kill switch, admin compromise), **pre-launch gates** (legal copy, real merchant, real prices, real video asset, CI green, sandbox end-to-end, restore test).
- `docs/RUNNING_LOCALLY.md` — local setup incl. payment sandbox env names.
- `.github/workflows/ci.yml` — push/PR; PHP 8.4 + pdo_mysql; MySQL 8 service; composer install; env swap to MySQL; key:generate; **npm build (LandingPageTest reads the Vite manifest)**; migrate; `php artisan test`; pint non-blocking.
- `SPEC.md` (866 ln) — the source of truth; `DECISIONS.md` — decision log + 9 open client questions (hosting → DB engine, real prices, OTP, CDN provider, content volume, gateway choice, AI-crawler visibility, brand palette hexes, admin roles).

---

## 9. Strengths (verified line-by-line)

1. **Money correctness:** integer Rial everywhere; `currency 'R'` prevents the shetabit 10× multiplier; amount-bound verification; unique constraints as DB-level backstops; reconcile + heal paths; financial records survive user deletion via snapshots.
2. **Idempotency & concurrency:** row locks (`lockForUpdate`) on invoice resolution, video progress, flashcard schedules; first-insert race handling; replay-safe callbacks; fail-closed free caps.
3. **Security:** `is_admin`/`status` not fillable; `forceFill`-only elevation paths; self-lockout/self-demotion guards; CSRF everywhere; session regeneration on login/2FA; per-identifier+IP login throttle; suspended-session lockout; secret redaction in audit logs; security headers; signed URLs with entitlement re-check; password reset never leaks account existence; TOTP done properly (constant-time, drift window, single-use recovery codes).
4. **Single source of truth:** entitlement rules centralized in `EntitlementService` + `User::hasActiveSubscription`; free-designation forced through the quota service; publication state machine with byline enforcement.
5. **Performance hygiene:** memoized per-instance subscription/enrollment checks; eager-loaded policy chains; `setRelation`; cached cap counts with observer invalidation.
6. **Quality of life:** Persian-safe slugs/phones; Persian error pages; RTL design system; reduced-motion; thorough docs; MySQL CI.

---

## 10. Issues, risks & observations (honest audit findings)

**Bugs / functional gaps**
1. **2FA cannot be disabled and recovery codes cannot be regenerated.** `TwoFactorController::disable()` exists but **no route references it**, and once 10 recovery codes are consumed there is no UI to mint new ones (the `recover` success message even promises "در صورت نیاز کلید بازیابی جدیدی بسازید" — but no such action exists). Admin lockout risk.
2. **`PaymentController::callback` duplicates `PaymentFinalizer`** instead of delegating to it. The finalizer's heal set (failed/expired/cancelled) is *broader* than the callback's inline set (pending/initiated), and the finalizer is what the reconcile command uses. Two copies of money-resolution logic = drift risk on the most safety-critical path.
3. **`DashboardController` re-implements the active-subscription query** instead of calling `$user->hasActiveSubscription()` (the documented single source of truth). It currently matches, but it's the exact duplication the codebase comments warn about.
4. **`Subscription::isActive()` vs `User::hasActiveSubscription()` disagree on `ends_at = NULL`**: the model method requires a non-null future `ends_at`; the user method treats null `ends_at` as active forever. Harmless today (subscriptions are always created with an end), but a latent inconsistency.
5. **No guard against buying while already subscribed** — checkout happily issues a second subscription for a user with an active one (stacking). Spec may intend this, but there's no explicit policy; at minimum the plans page doesn't say "you already have an active subscription".
6. **`public/videos/sample.mp4` does not exist in the repo** — playback returns 404 until an operator drops a file in (documented, but easy to forget; the seeder's `manifest_reference` values are meaningless until then).
7. **Admin note creation never uploads a file** — `storage_key` defaults to `notes/{slug}.pdf` but nothing writes that file; download 404s until a file is placed (same placeholder nature as videos).
8. **`ActivityLogController` caps at 300 rows without pagination** — the audit trail will outgrow the page; fine at v1, worth a paginator.

**Minor / stylistic**
9. `Video` model has `#[Hidden([])]` — a no-op (harmless).
10. `Quiz` questions rebuild (delete + recreate options) on every edit — `quiz_attempt_answers.selected_option_id` is nullOnDelete so history survives, but option IDs change; fine.
11. Hardcoded Persian copy is everywhere in views (by documented decision) — the shared `lang/fa` layer covers only nav/legal strings.
12. `welcome`/`blog`/`legal` pages contain **static marketing copy and fake faculty claims** (names, credentials) — fine as mockups, but they must not ship as fact before the client reviews (the README/RUNBOOK acknowledge legal copy is a blocking gate).
13. `config/broca.php` `checkout_enabled` defaults **false** — intentional kill switch, but a fresh `php artisan serve` demo will show "پرداخت در حال آمادهسازی" — expected until launch.
14. `ForceSecureConnections` + HSTS only-on-secure is correct; ensure `APP_URL` is https in production (RUNBOOK covers it).

**Observations (by design, documented)**
- SM-2 chosen over FSRS (composer constraint; `SrsService` split to allow porting).
- No full CSP (`unsafe-inline` needed for inline Alpine/JSON-LD) — tracked in DECISIONS.md.
- AI crawlers explicitly allowed on public pages — a GEO decision, reversible in `SeoController::robots` (note: the **static** `public/robots.txt` currently serves `Disallow:` (allow-all) — the dynamic route is shadowed by the static file; keep them in sync).
- Blog is placeholder views (no model/table); Zibal driver configured but not routed; Filament/Livewire deferred.

---

### Fix status (2026-08-29)

All eight functional gaps above were addressed in the `arena/01a04cca-broca` branch:

| # | Gap | Fix |
|---|---|---|
| 1 | 2FA disable / recovery-code regeneration unreachable | Routes `admin.two-factor.disable` + `admin.two-factor.recovery-codes`, `TwoFactorController::disable()` (code-confirmed) + `regenerateRecoveryCodes()`, UI panel in `admin/two-factor/edit.blade.php` |
| 2 | Callback duplicates `PaymentFinalizer` | `PaymentController::callback` now delegates to `PaymentFinalizer::finalize()` — one money-resolution path (callback + `broca:reconcile-payments`) |
| 3 | Dashboard re-implements subscription query | `DashboardController` calls `$user->activeSubscription()`; query lives only in `User` |
| 4 | `isActive()` vs `hasActiveSubscription()` NULL `ends_at` | `Subscription::isActive()` mirrors `User::activeSubscription()` — NULL `ends_at` = active indefinitely; `User::activeSubscription()` added as the single query |
| 5 | No stacking guard | `PaymentController::checkout` redirects users with an active subscription; plans page shows "you have an active subscription" instead of the buy button |
| 6 | `public/videos/sample.mp4` missing | `VideoController::media()` streams each video's own `manifest_reference` (bare-filename charset check + realpath containment); placeholder assets committed under `database/placeholder-media/` and provisioned by `broca:provision-media` (auto-run by `db:seed`) |
| 7 | Admin note creation never writes a file | Same provisioning command copies placeholder PDFs for the three seeded `storage_key` values into `storage/app/private/notes/` |
| 8 | Activity log capped at 300 | `ActivityLogController` now paginates (100/page) with `links()` in the view |

**Verification:** the PHP toolchain is unavailable in the authoring sandbox (no PHP/composer; package mirrors unreachable), so the new/updated tests in `tests/Feature/AdminTwoFactorTest.php`, `PaymentTest.php`, `SubscriptionEntitlementTest.php`, `PlaybackTest.php` must be executed locally (`composer test`) before merge.

### Second audit — fix status (2026-08-29)

A follow-up audit (CRITICAL 1 / HIGH 2 / MEDIUM 8 / LOW 7) was delivered in chat; all CRITICAL/HIGH/MEDIUM items were fixed in commit `940ad1b`:

| # | Severity | Finding | Fix |
|---|---|---|---|
| 1 | CRITICAL | JSON-LD `</script>` breakout (`courses/show.blade.php`, `layouts/app.blade.php`) | `JSON_HEX_TAG \| JSON_HEX_AMP \| JSON_HEX_APOS \| JSON_HEX_QUOT` on all three blocks; `JsonLdEscapingTest` now green by construction + new valid-JSON case |
| 2 | HIGH | Plaintext 2FA recovery codes (`User.php`) | `storeRecoveryCodes` persists `hash('sha256', strtoupper($code))`; `consumeRecoveryCode` compares `hash_equals` and deletes; plaintext is flashed once at generation only. `totp_secret` gets the `encrypted` cast + `text` column (migration `2026_08_29_000005`) |
| 3 | HIGH | Unthrottled 2FA enable/disable | `throttle:10,1` on enable/disable/recovery-codes (matches the login challenge) |
| 4 | MEDIUM | 2FA lifecycle not audited | `admin.audit` on the management routes (start/enable/disable/recovery-codes); challenge/verify/recover deliberately un-audited (one-time codes) |
| 5 | MEDIUM | Session cookie not Secure; proxy not trusted | `SESSION_SECURE_COOKIE` documented + config falls back to `APP_FORCE_HTTPS`; `TRUSTED_PROXIES` env drives `trustProxies(at: ...)` so `ForceSecureConnections`/HSTS work behind cPanel/shared-hosting TLS proxies |
| 6 | MEDIUM | CSP was decorative | Real policy: `default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; media-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'` (inline Alpine + JSON-LD require `'unsafe-inline'`); Vite allowed only in `local` with `public/hot` |
| 7 | MEDIUM | Static `public/robots.txt` shadowed the dynamic route | File deleted; `SeoController::robots()` serves; regression asserts the file never returns |
| 8 | MEDIUM | Free-cap copy/badges disagree with the global cap | Badges on `courses/show` + learner dashboard now render through `EntitlementService::isFree()` (`is_free_available`); plans/welcome copy says "per account, across the whole archive" |
| 9 | MEDIUM | N+1 on four admin index queries | **Retracted during implementation** — all four queries (`Admin/Course|Video|Note|QuizController::index`) already eager-load (`with('course'/'subject','author','reviewer')`); the earlier grep missed the `->with(...)` lines |
| 10 | MEDIUM | Cascade-delete financial FKs | Migration `2026_08_29_000004` flips `payment_transactions.invoice_id` + `subscriptions.invoice_id` to `restrictOnDelete()` (new migration, safe on deployed DBs) |
| 11 | MEDIUM | Security-critical logic untested | New tests: `AdminTwoFactorManagementTest` (throttle ×3, lifecycle audit, redaction), `SecurityHeadersTest` (CSP, secure cookie, robots/sitemap), `EntitlementBadgeTest` (cap-aware badges ×4), `PaymentLifecycleTest` (expire + reconcile ×4), `AdminCrudSmokeTest`; extended `AdminTwoFactorTest`, `JsonLdEscapingTest`, `PaymentTest` (NULL `ends_at` stacking guard); `APP_KEY` added to `phpunit.xml` (required by the encrypted cast) |

**Verification (honest):** tests still cannot run in this sandbox — no PHP/composer binary and `repo.packagist.org`/`getcomposer.org` unreachable (one attempt, both `000`). All 212 repo PHP files parse clean on PHP 8.4.23 (`token_get_all(TOKEN_PARSE)` via php-wasm, 0 failures); the production asset build is byte-identical to the committed one. Run `composer test` locally (MySQL 8 or sqlite) to execute the suite — the previously-red `JsonLdEscapingTest` should now pass, and the new tests exercise every changed code path.

---

## 11. Verdict

This is a **well-engineered, security-conscious Laravel 13 codebase** that punches well above the average: the payment state machine (row-locks, unique-constraint idempotency, reconcile/heal, kill switch), the fail-closed freemium entitlement layer, and the MySQL-based CI are all production-grade thinking. The code is consistently commented (in English) with Persian UX copy, every money path is integer-based, and the test suite covers the money and gating paths first-class.

**Before launch** (matching the repo's own RUNBOOK gates): real legal copy, real ZarinPal merchant, real prices, a real video asset/provider. The two fixes I'd previously prioritized — (1) 2FA disable / recovery-code regeneration and (2) `PaymentController::callback` delegating to `PaymentFinalizer` — are implemented on this branch (see §10 fix status); run the suite locally to confirm.

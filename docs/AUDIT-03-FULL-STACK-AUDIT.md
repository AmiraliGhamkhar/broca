# Broca — Full-Stack Production Audit (Round 6)

Date: 2026-09-05 · Auditor scope: **entire repository, file-by-file**
Target: Mizbanfa shared cPanel · `brocamed.ir` · PHP 8.4 (EA-PHP84) · MySQL 8 ·
cPanel Git Version Control deploy (`.cpanel.yml`) · checkout still disabled
(`BROCA_CHECKOUT_ENABLED=false`, ZarinPal sandbox — client confirmed).

**Deliverable mode: report only.** Nothing in this document has been
implemented. Every item awaits your sign-off (§9 collects the decisions).

---

## 0. Executive summary

The codebase is in unusually good shape for a shared-hosting Laravel app. The
five previous audit rounds hold up under re-inspection: the money path is
amount-bound, row-locked and idempotent at three layers; entitlement gating is
fail-closed with cache invalidation on both the item and course sides; auth has
session regeneration, suspension lockout, post-reset session nuking, TOTP 2FA
with hashed single-use recovery codes; CI runs the full suite on PHP 8.4 +
MySQL 8 and fails on stale committed assets. **The framework is also current on
security patches**: `composer.lock` pins `laravel/framework v13.26.1`, which is
past the 13.10.0 fix for CVE-2026-48019 (CRLF injection in email validation,
published 2026-06-04/09-04) — verified, not assumed.

This round found **no launch blockers**. It found **1 correct-by-spec violation
in robots.txt (P1, five-minute fix)**, **4 P2 operational issues** (unbounded
table growth on the database session/cache stores, secret-bearing URLs that can
reach `laravel.log` via Telegram exceptions, a payment-forensics gap, a login
rate-limit blind spot) and a series of P3 consistency/perf items. It also
confirms, with 2026 sources, that the GEO/markdown-twin strategy is correctly
calibrated — and that one of its claims (FAQ rich results) needs a
reality-check note because Google retired the SERP feature entirely in May
2026.

| # | Sev | Domain | Finding |
|---|-----|--------|---------|
| 1 | **P1** | SEO | robots.txt named-agent groups drop the `Disallow` rules (groups never merge per RFC 9309) — `SeoController.php:33–74` |
| 2 | **P2** | DB | `sessions` table grows unboundedly; no `session:prune` scheduled — `routes/console.php:32–44` |
| 3 | **P2** | DB | `cache` table grows unboundedly via rate-limiter keys; database store never deletes expired rows |
| 4 | **P2** | Auth | Login has no per-IP outer bound — rotating identifiers defeats the per-(identifier\|IP) limiter — `AuthenticatedSessionController.php:24–50` |
| 5 | **P2** | Integrations | Telegram API exceptions can write the **bot token** (it is part of the URL) into `laravel.log` — `TelegramApiClient.php:86–103` |
| 6 | **P2** | Payments | Gateway verify response (RefID/receipt) is never persisted; `payment_transactions.response_payload` only stores the callback query — `PaymentController.php:133–149` |
| 7 | P3 | DB | Slug generation ignores soft-deleted rows while the DB UNIQUE index does not → rare 500 after soft-delete + recreate — `Admin/CourseController.php:48,72`, `Admin/VideoController.php:141–150`, `Admin/NoteController.php:199,228` |
| 8 | P3 | DB | Non-deterministic pagination: `orderBy('sort_order')` without an `id` tiebreaker — `CatalogController.php:82,101`, `DashboardController.php:324` |
| 9 | P3 | DB | N+1 in quiz submit policy filter (missing `with('quiz.course')`) — `Learner/QuizController.php:203–208` |
| 10 | P3 | Backend | Dead + contradictory `zarinpal` block in `config/services.php:4–13` (unused; different hosts than the live driver config) |
| 11 | P3 | Backend | Payment callback route unthrottled — `routes/web.php:81` |
| 12 | P3 | Frontend | Invalid `<div>` inside `<head>` — `layouts/app.blade.php:52–54` |
| 13 | P3 | Frontend | Landing `$specialties` hardcodes 4 subject slugs (same anti-pattern previously fixed in the footer) — `welcome.blade.php:16–21` |
| 14 | P3 | Frontend | Deck-study route ignores the `{course}` URL segment — `Learner/FlashcardController.php:58–73` vs. video/note controllers which scope-check |
| 15 | P3 | Frontend | Dashboard deck card-count includes drafts; learner hub counts published only — `DashboardController.php:298` vs `Learner/FlashcardController.php:33` |
| 16 | P3 | SEO/AEO | Noindex missing on `payments/success`, `payments/failed`, auth views; paginated canonicals collapse to page 1 |
| 17 | P3 | Copy | Unverifiable claims pre-launch: "پرداخت امن زرین‌پال" (announcement bar), "هیئت علمی دانشگاه‌های علوم پزشکی" (footer); dead `hero_placeholder_note` still references the cancelled 3D-heart video |

Verified-correct this round (no action): payment verification chain, freemium
entitlement + observers, TOTP implementation, migration graph and financial FK
restrictions, LIKE-escaping in every search path, `env()` hygiene (zero `env()`
calls outside `config/`), committed-asset deploy strategy, CI.

---

## 1. Database

### 1.1 Findings

**D-1 (P2) — `sessions` table grows forever.** `SESSION_DRIVER=database`
(`.env.example:38`) and the `web` middleware starts a session for **every**
visitor, including guests (CSRF). `routes/console.php:32–44` schedules the
queue drain, subscription expiry, reconciliation and backup — but no
`session:prune`. Rows carry `ip_address` + `user_agent`, so this is both a
disk-growth problem on a capped shared plan and an unbounded PII retention
window. New password resets delete one user's rows (`Auth/NewPasswordController.php:106–110`), but nothing removes abandoned guest rows.

**D-2 (P2) — `cache` table grows forever.** `CACHE_STORE=database`
(`.env.example:52`). The database cache store **never deletes expired rows** —
they are ignored on read and only overwritten when the same key is re-set. The
app's fixed keys (`free_cap_*`, `seo.sitemap.xml`, `seo.llms.txt`,
`footer_subjects`) are bounded, but **rate-limiter keys are not**: every
`throttle:` evaluation writes window keys per user/IP (e.g.
`throttle:video-progress` = 30/min/user → a new pair of rows per user per
minute-window of activity). Over months this is millions of dead rows
interleaved with the hot working set, degrading every cache read and burning
disk. This is documented framework behavior, not a bug — Laravel ships
`cache:prune-stale-tags` for Redis/Memcached only; the database driver has no
built-in pruner.

**D-3 (P2→decision) — `admin_activity_logs` has no retention policy.** One row
per mutating admin request (`LogAdminActivity.php:24–43`), each with a JSON
payload. Forensically valuable; unbounded. Retention is a product decision
(keep 90/180/365 days?).

**D-4 (P3, note only) — `flashcard_reviews` is an append-only audit of every
review.** At realistic launch scale (hundreds of users × dozens of
reviews/day) this is fine for years. Flagged so the growth is a known,
accepted property rather than a surprise. Optional: prune reviews older than
24 months (SM-2 state lives in `user_flashcard_schedules`, so history is not
needed for scheduling).

**D-5 (P3) — Slug uniqueness check ignores soft-deleted rows.** The exists
closures use Eloquent defaults, which apply the `SoftDeletes` global scope:
`Admin/CourseController.php:48` and `:72`, `Admin/VideoController.php:145–148`,
`Admin/NoteController.php:199` and `:228` (blog is safe — `Admin/BlogController.php:123`
uses `forceDelete()`). The DB constraint is `UNIQUE` over **all** rows
(`create_broca_core_tables.php:58,99,121,190`). So: soft-delete a course
titled «فیزیولوژی قلب», create a new course with the same title → generator
sees the slug free → `INSERT` hits the unique index → raw 500. Low
probability, but it is a crash with a confusing log line, and the fix is one
word per site.

**D-6 (P3) — Non-deterministic pagination.** `CatalogController.php:82` and
`:101`, `DashboardController.php:320–326` order by `sort_order` alone. Rows
with equal `sort_order` (the default is 0 for everything the seeder doesn't
touch) have no defined order in MySQL; across `LIMIT/OFFSET` pages the same
course can appear on two pages and another can vanish. (The admin course list
already does it right: `Admin/CourseController.php:23–24` chains
`->latest('id')` after `sort_order`.)

**D-7 (P3) — N+1 on the quiz-submit path.** `Learner/QuizController.php:203–208`
loads questions `with('options')` only, then filters each through
`ContentPolicy::viewQuizQuestion`, which walks `$question->quiz?->course`
(`ContentPolicy.php:79`). `quiz` is not loaded (questions came from
`$quiz->questions()`), so submit costs **2 extra queries per question**
(quiz + course), exactly the pattern the `show()` path one method up avoids
with `->with(['options', 'quiz.course'])` (`:188`). The docblock on
`ContentPolicy` promises eager-loaded chains — submit breaks that promise.

**D-8 (P3) — Inconsistent card counts on the dashboard.**
`DashboardController.php:298` uses `withCount('cards')` (counts drafts too),
while the learner flashcards hub correctly counts published only
(`Learner/FlashcardController.php:33`). The dashboard promises "۲۰ کارت" and
the study page serves 12 published ones.

**D-9 (P3) — `quizzes.is_free_designated` is the only free-flag column without
an index** (`create_broca_core_tables.php:183` vs `:92,:114,:147` for
videos/notes/flashcards). The free-cap queries filter on it
(`EntitlementService.php:74–80`). Quiz tables are small; harmless today — fix
only if the schema is touched for another reason, for consistency.

### 1.2 Fix

1. **D-1:** add to `routes/console.php`:
   `Schedule::command('session:prune')->dailyAt('04:10');` — the framework
   command natively prunes the database session driver. Zero dependencies.
2. **D-2:** add a 12-line artisan command (`broca:prune-cache`) that runs
   `DB::table('cache')->where('expiration', '<', now()->getTimestamp())->delete()`
   (and the same for `cache_locks`), scheduled daily. Deliberately **not** the
   community `cache:prune` package — adding a composer dependency for one
   DELETE violates the repo's minimal-dependency posture and adds an audit
   surface; cPanel compatibility is identical (it's a scheduled artisan
   command like the existing four).
3. **D-3:** extend the same command (or a sibling) with a
   `--activity-days=180` option deleting old `admin_activity_logs` rows,
   **after you pick the retention window** (§9, Q3).
4. **D-5:** add `->withTrashed()` inside every slug `exists` closure (4 sites),
   and/or catch `UniqueConstraintViolationException` and retry, mirroring
   `PaymentController::createInvoice` (`:157–179`) which already sets the
   repo's convention for exactly this class of race.
5. **D-6:** chain `->orderBy('id')` after `orderBy('sort_order')` in the three
   query sites.
6. **D-7:** `->with(['options', 'quiz.course'])` in `submit()`.
7. **D-8:** `withCount(['cards' => fn ($q) => $q->published()])` in the
   dashboard eager-load.

### 1.3 Rationale

- Session/cache pruning: the Laravel database stores are documented as
  read-time filters, not garbage collectors (framework issue #28581 traces
  this; the community `EncoreDigitalGroup/laravel-cache-prune` README states
  the database-store behavior verbatim: expired items "stay there forever, or
  until that same cache key is reused"). On a metered shared disk this is an
  outage-in-waiting measured in months, and both fixes are cron-safe artisan
  commands consistent with how this repo already does scheduled work.
- Slug/pagination/N+1 items are correctness hygiene: each is the kind of thing
  that surfaces as a "random" 500 or a support ticket about a missing course,
  and each fix is local, test-covered-able and matches an existing convention
  in the repo (admin list ordering, `createInvoice` retry, `show()` eager-load).
- Discarded advice check: generic "add indexes everywhere" and "switch cache
  to Redis" were **not** applied — Redis is unavailable on the confirmed
  hosting, and the existing composite indexes already match every hot query
  (verified: `invoices(status,expires_at)`, `user_flashcard_schedules(user_id,due_at)`,
  `subscriptions(user_id,status,starts_at,ends_at)`).

### 1.4 Flag

None — every fix above is cPanel-compatible (migrations not required except
D-9, which I recommend skipping).

---

## 2. Backend

### 2.1 Findings

**B-1 (P3) — Dead, contradictory ZarinPal config.** `config/services.php:4–13`
declares a `zarinpal` block with its own `base_url`/`payment_url` (api.zarinpal.com
/ www.zarinpal.com) that **nothing reads** (verified: zero `config('services.zarinpal')`
references in app/, tests/, resources/). The live driver config is
`config/payment.php` (shetabit/multipay driver map). Two problems: it will
mislead whoever wires go-live credentials, and its host list silently disagrees
with the package's normal-mode endpoints. Dead config in the money path is
worse than no config.

**B-2 (P3) — Payment callback unthrottled.** `routes/web.php:81` registers
`GET /payments/zarinpal/callback` with no `throttle:`. Every hit with
`Status=OK` against an *unpaid* invoice triggers an outbound gateway verify
request (`PaymentController.php:106`). Checkout got a limiter for exactly this
reason (`routes/web.php:122–129`); the callback is the endpoint a hostile
script can hit without a session.

**B-3 (P3) — Audit-log sanitizer is shallow.** `LogAdminActivity.php:70–76`
redacts `password`/`token`/etc. at the **top level** of the input bag only;
nested keys (`options[0][label]`, future nested forms) pass through. Scalar
values are truncated to 500 chars, which contains the blast radius, but the
redaction intent is stated and not fully implemented.

**B-4 (P3, verify-at-implementation) — shetabit HTTP timeout.** The Telegram
client sets an explicit timeout (`TelegramApiClient.php:102`), but the ZarinPal
purchase/verify calls go through shetabit/multipay's own HTTP client. If the
package doesn't set a transfer timeout (it historically doesn't), a hung
gateway connection occupies a PHP worker until `max_execution_time` — on
shared hosting, a few of those during a slow ZarinPal episode can stall the
whole account's worker pool. Needs a 5-minute vendor check when composer is
available (`vendor/shetabit/multipay` driver source), then either a config
override or a documented accept.

**B-5 (P3) — Hardening notes, single items:** (a) TOTP verification has no
replay guard for the ±1 step window (`Totp.php:74–86`) — acceptable with the
existing 10/min throttle; a "last used step" column is the standard upgrade if
you ever want it. (b) `HealthCheckController` (`/health`, DB-checked) coexists
with the framework's `/up` (no DB) — document in RUNBOOK which one external
monitors should use. (c) `RegisteredUserController.php:186` calls `Hash::make`
on a field with the `hashed` cast — harmless (Laravel won't double-hash an
already-hashed value) but redundant; one of the two should go.

### 2.2 Fix

1. Delete the `zarinpal` block from `config/services.php` (B-1).
2. `->middleware('throttle:30,1')` on the callback route (B-2) — generous
   enough for ZarinPal retries and double-clicks, tight enough to stop
   verify-flooding. Keyed by IP automatically (guest route).
3. Make `LogAdminActivity::sanitize()` recursive over nested arrays with the
   same REDACTED_KEYS (B-3).
4. B-4: verify vendor timeout; if absent, subclass/configure the driver with a
   15s timeout before go-live.

### 2.3 Rationale

- B-1: the go-live runbook (RUNBOOK §1) is already a credentials checklist; a
  second, wrong-looking credentials block is exactly the kind of thing a tired
  operator fills in at 1 AM. Deleting dead config is the cheap version of that
  incident not happening.
- B-2: current best practice for gateway callbacks (and Laravel's own docs on
  `throttle:`) is to rate-limit any endpoint that triggers outbound network
  calls per request. The checkout limiter (6/min/user) already encodes this
  repo's philosophy; the callback limiter extends it to the unauthenticated
  surface. ZarinPal also rate-limits merchants, so this protects merchant
  standing as much as workers.
- Discarded advice: "verify twice on callback" and "add HMAC on the callback"
  were considered and rejected — ZarinPal's v4 API has no callback signature;
  server-side amount-bound verification **is** the integrity mechanism (that
  part of the existing design is correct and current).

### 2.4 Flag

None.

---

## 3. Frontend

### 3.1 Findings

**F-1 (P3) — Invalid HTML: a `<div>` in `<head>`.**
`layouts/app.blade.php:52–54` renders the sr-only Markdown-twin hint inside
`<head>`. Browsers hoist it into `<body>` (so it renders today), but it is a
spec violation that produces inconsistent parser behavior between engines and
a validation error in every audit. The intent (an agent-parsable hint) is
sound; the placement isn't.

**F-2 (P3) — Hardcoded subject slugs on the landing page.**
`welcome.blade.php:16–21` links «قلب و عروق» → `?subject=cardiovascular-physiology`
etc. This is the exact anti-pattern DECISIONS.md records as fixed for the
footer ("every click rendered an empty catalog page") — it survives here
because the seeder happens to create those four slugs
(`DatabaseSeeder.php:96–120`). Rename a subject in admin and the landing grid
silently links to empty pages again.

**F-3 (P3) — Route context not enforced on deck study.**
`Learner/FlashcardController::study` (`:58–73`) never checks
`$deck->course_id === $course->id` (the controller doesn't even accept
`$course`), while `Learner/VideoController.php:20` and
`Learner/NoteController.php:18` both do. `/courses/{anything}/decks/{deck}/study`
works for a deck belonging to a different course. Not a privilege escalation
(enrollment in the deck's own course is still required), but it breaks URL
semantics and the page's breadcrumb context, and it's an inconsistency in the
one controller family where consistency is the security property.

**F-4 (P3) — Copy accuracy / §3 violations.**
(a) Announcement bar: «پرداخت امن زرین‌پال» (`layouts/app.blade.php:95`) —
payments are sandbox and checkout is disabled; advertising the payment rail
pre-launch is a claim the product can't back yet.
(b) Footer: «نظارت اعضای هیئت علمی دانشگاه‌های علوم پزشکی»
(`layouts/app.blade.php:375`) — an institutional-faculty claim. The repo's own
SPEC and the HomeController docblock (`HomeController.php:8–20`) treat invented
medical authority as a trust/liability violation; a blanket university-faculty
claim with zero named contributors falls in the same category.
(c) `lang/fa/app.php:13` — `hero_placeholder_note` («تصویر نهایی قلب سه‌بعدی…»)
is dead code referencing the video asset you have now cancelled.

**F-5 (P3) — Missing noindex on utility views.** Learner and error views all
set `noindex` correctly; `payments/success`, `payments/failed`, and the four
auth views do not (default `index, follow` via the layout). The payment pages
are owner-gated so exposure is minimal, but there is no scenario where a
transaction receipt page belongs in an index.

**F-6 (P3, SEO nit) — Paginated canonicals.** `catalog/index.blade.php:16`
canonicalizes every page of results to the base `/catalog`
(`CatalogController` passes no page parameter), so `?page=2` claims to be a
duplicate of page 1. Google treats conflicting canonicals as a soft signal and
usually ignores them — but the correct convention is self-canonical
paginated URLs.

**F-7 (P3) — `.htaccess` docroot tripwire could go one step further.** The
dotfile block (`.env`, `.git`…) is good insurance against document-root
misconfiguration. If the docroot is ever pointed at the repo root,
`storage/logs/laravel.log` (stack traces) and `composer.json` become
web-readable without tripping it.

### 3.2 Fix — the hero photo (your decision requested, §9 Q1)

You've replaced the 3D-heart video with a **photo**. The design system already
contains every hook this needs — this is a composition change, not a new
system:

- **Composition.** Keep the existing asymmetric 7/5 RTL split
  (`landing-hero.blade.php:3–4`). Replace the right-column dark "profile"
  card with a full-bleed **editorial photograph** of the study/anatomy
  subject, sitting under the existing `hero-shell::after` scrim
  (`app.css:241–249`) — that gradient was built for a media background and is
  currently sitting unused. Headline stays Lalezar on the text column; the
  photo column gets a single caption strip (alt text real, not decorative).
  This is the Airbnb-style "one big real image, zero invented UI" hero: it
  removes the busiest card stack on the page instead of adding anything.
- **Asset spec.** ≥ 2400px wide, 4:3 or 3:2, delivered as WebP + JPEG fallback,
  **≤ 300 KB WebP target** (shared-hosting bandwidth), no baked-in text, no
  faces of identifiable real people without a license. Desaturated slightly in
  CSS (the existing `filter: saturate(0.85) contrast(0.98)` rule, `app.css:252–255`).
- **Performance contract (CWV).** This image becomes the LCP element: preload
  it (`<link rel="preload" as="image" fetchpriority="high">`) alongside the
  existing font preloads (`layouts/app.blade.php:47–48`), hard-set
  `width`/`height` or `aspect-ratio` (CLS ≤ 0.1), serve ≤ 300KB.
  Current 2026 thresholds are unchanged: LCP ≤ 2.5s, INP ≤ 200ms, CLS ≤ 0.1 at
  the 75th percentile.
- **Sourcing.** Two options: (a) you license/supply the photo — best; or (b) at
  implementation time I generate candidate imagery with the image tool and you
  pick (AI-generated imagery — it would be disclosed as such in the repo per
  the asset conventions). If (b), Latin-only or no text in image, consistent
  with the og:image decision in DECISIONS.md.

### 3.3 Fix — everything else

1. **F-1:** move the sr-only Markdown hint div to the top of `<body>`.
2. **F-2:** data-drive the specialties grid: visible subjects ordered by
   `sort_order`, limit 4, cached 300s — the *exact* pattern the footer already
   uses (`layouts/app.blade.php:396–408`). Keep the four editorial blurbs
   keyed by slug with a graceful fallback to the generic card.
3. **F-3:** add `$course` to `study()` and the
   `$deck->course_id === $course->id` abort, matching video/note.
4. **F-4:** (a) reword the announcement bar to what's true today (free tier +
   scientific review) until checkout opens; (b) soften the footer to the
   process claim («بازبینی علمی دو مرحله‌ای») unless/until real faculty exist
   — this also matches the honest-empty-state philosophy already on the
   landing page; (c) delete the dead lang key.
5. **F-5:** `@section('robots', 'noindex, nofollow')` on `payments/success`,
   `payments/failed`, and (your call) `auth/*` except `register`.
6. **F-6:** include `->page` in the catalog canonical when paginated (or
   canonicalize to the unfiltered first page **and** noindex pages ≥ 2 — the
   simpler, equally valid convention; I'd do self-canonical).
7. **F-7:** extend the `.htaccess` deny block with `storage`, `database`,
   `tests` directory matches (still wrapped in `<IfModule>` guards).

### 3.4 Rationale

- F-1/F-3/F-4(c) are zero-debate correctness items (spec validity, route
  semantics, §3's explicit no-placeholder rule).
- F-2's fix is not a redesign: it reuses the footer's proven data-driven
  pattern verbatim, so the change is one source of truth for "subjects that
  exist" and the §3 traceability requirement is satisfied (existing pattern,
  cited).
- The §3 "symmetric feature-grid" audit of the landing page: the specialties
  section is the closest approach to the banned pattern (4 uniform
  icon-over-heading cards), but it is built from the documented
  `feature-card`/`icon-frame` tokens (DESIGN-airbnb.md §5), each card carries
  a real catalog link and badge, and the surrounding sections are visibly
  asymmetric (7/5 hero, 5/7 and 4/8 splits, editorial faculty cards). Verdict:
  **acceptable, not slop** — but if you want it stronger, merging the
  trust-strip (4 cards of abstract claims) into the hero badges would remove
  the most generic section on the page. Optional, flagged as §9 Q2.
- Discarded advice: dark-mode support, CSS-in-JS extraction, and any
  image-optimization pipeline (sharp/imagick on upload) were all considered
  and rejected — no dark-mode tokens exist by design (pure-white canvas is the
  client-chosen identity), and an on-upload resize pipeline adds a PHP
  extension dependency (imagick) that the confirmed host config doesn't
  guarantee. The photo asset arrives pre-optimized per the spec instead.

### 3.5 Flag

The hero photo needs a **source decision** before implementation (§9 Q1):
client-supplied license vs. AI-generated candidates. Everything else in §3 is
cPanel-safe (no build-step changes; committed assets get rebuilt in CI).

---

## 4. Auth

### 4.1 Findings

**A-1 (P2) — Login rate-limit has an identifier-rotation blind spot.**
`AuthenticatedSessionController.php:24–50` keys the limiter on
`identifier|ip` with 5 attempts / 60s decay. Per-identifier that's reasonable
(7200 guesses/day/IP/identifier). But an attacker sweeping **many identifiers
from one IP** (password-spraying a user list) generates a fresh key per
identifier and is never rate-limited in aggregate. Registration is guarded
both ways (`throttle:registration` by IP *and* validation); login's outer
boundary is missing.

**A-2 (verified-correct inventory, for the record):** session regeneration on
login and 2FA-verify (`:51`, `TwoFactorController.php:280,298`); logout
invalidate+regenerateToken (`:56–63`); suspended accounts locked out of
*existing* sessions (`EnsureActive.php:16–29`) *and* unable to log in
(credentials include `status => active`, `:43`); password reset kills every
database session of the user (`NewPasswordController.php:106–110`); recovery
codes stored as SHA-256 and consumed atomically under a row lock
(`User.php:100–134`); `is_admin`/`status` not mass-assignable and admin updates
forceFill with self-lockout + last-admin guards (`Admin/UserController.php:209–237`);
bcrypt rounds 12 — which is exactly the current baseline (PHP 8.4 raised its
own default to 12; OWASP's sub-second guidance is met); `uncompromised()` on
register + reset with documented fail-open. Verification-email resend is
throttled (6/min) and the verification link is signed.

### 4.2 Fix

Wrap the login `POST` in an outer IP-bound limiter:
`Route::post('/login', ...)->middleware('throttle:20,1')` — 20 attempts/min/IP
is far above any human's typo rate and far below a spraying tool's capacity,
and it composes with (does not replace) the existing per-identifier limiter.
Optionally raise the identifier-key decay from 60s to 300s for a matching
tightening of the per-account bound — your call, both are one-liners.

### 4.3 Rationale

Credential-stuffing/spraying guidance (OWASP Authentication Cheat Sheet;
Laravel's own rate-limiting docs) recommends **layered** limiters:
per-account, per-IP, and (for scale) global. The repo has layer one only.
The 20/min/IP figure matches the registration limiter's philosophy
(10/min/IP there, where each hit also costs an outbound email — login is
cheaper, so a slightly higher cap is right).

### 4.4 Flag

None — middleware-only, zero hosting implications.

---

## 5. API integrations (money path + Telegram)

### 5.1 Findings

**I-1 (verified-correct, re-checked against ZarinPal's current v4 docs):**
amount-bound server verification (`ZarinPalGateway.php:49–56` — the amount
sent to verify is the *invoice's stored amount*, never client input); integer
Rial end-to-end with `'currency' => 'R'` preventing multipay's ×10 Toman
behavior (`config/payment.php:15–23`); `PreviouslyVerifiedException` (ZarinPal
status 101) treated as paid — the correct idempotent reading of the v4 API;
heal-set finalizer letting a *verified* payment recover an invoice from any
non-paid state while an *unverified* one can only fail pending/initiated
(`PaymentFinalizer.php:19–67`); DB-level idempotency via
`UNIQUE(gateway, authority)` on invoices and `UNIQUE(gateway, reference_number)`
on transactions; daily reconcile command re-verifying the heal set; hourly
expiry of stale initiated invoices. Sandbox → normal switching is
config-only. **The money path remains the strongest part of this codebase.**

**I-2 (P2) — the gateway's verify receipt is never persisted.**
`PaymentController::recordTransaction` (`:133–149`) stores the *callback query*
as both `request_payload` and `response_payload` (they're identical for a GET
callback), then `verifyPayment()` returns a bare `bool`. ZarinPal's v4 verify
response carries the RefID, card PAN mask and fee — the fields a chargeback
dispute or an accounting reconciliation actually needs. The
`payment_transactions` table was designed for this and is being under-filled.

**I-3 (P2) — Telegram bot token can leak into `laravel.log`.** The token is a
URL path segment (`TelegramApiClient.php:105–111` — Telegram's API design).
`->throw()` (`:32,:90`) surfaces `ConnectException`/transport errors whose
Guzzle message **includes the full URL** (`cURL error 28: … for
https://api.telegram.org/bot<TOKEN>/sendMessage`); `report($exception)` at any
call site (e.g. `TelegramBotService`'s handlers) writes that to the log. A log
file is routinely shipped to support threads and pasted into chats.

**I-4 (P3) — webhook processes heavy work synchronously.** The backup flow
(dump + gzip + multipart upload to Telegram) runs inside the webhook request
(`routes/web.php:272`, `TelegramBotService.php:1416`). Telegram aborts at ~60s
and retries — slow backups risk duplicate runs. Bounded risk (admin-only,
allowlisted), and the alternative (queueing) has its own cPanel trade-off —
flagged below.

**I-5 (P3) — Zibal remains wired-but-unrouted** (`config/payment.php:24–31`,
no callback route), exactly as DECISIONS.md records. No action unless you want
it finished this pass (§9 Q4).

### 5.2 Fix

1. **I-2:** change `PaymentGateway::verifyPayment()` to return the
   `Shetabit\Multipay\Receipt` (or null) instead of bool — two call sites
   (`PaymentController::callback`, `ReconcilePayments`) — and merge
   `['receipt' => $receipt->toArray()]` into the transaction's
   `response_payload` inside the finalizer transaction. Keeps the ledger
   self-sufficient for disputes. Also de-duplicate `request_payload`/`response_payload`
   (store the gateway response where the response belongs).
2. **I-3:** wrap `TelegramApiClient::request`/`sendDocument` transport calls in
   a try/catch that rethrows `RuntimeException` with the URL's token redacted
   (`preg_replace('#/bot[^/]+/#', '/bot***:REDACTED/', $message)`), preserving
   the previous exception for `report()`. Alternatively sanitize at the
   `report()` call sites — one choke point in the client is cleaner.
3. **I-4:** dispatch the backup to the database queue (drained every minute by
   the existing cron worker) and have the bot reply «در حال تهیه…». This uses
   infrastructure that already exists — no daemon needed. If you'd rather keep
   it synchronous, accept the duplicate-run risk explicitly in RUNBOOK.

### 5.3 Rationale

- I-2: the repo's own audit standard (AUDIT-01: "the payment ledger is
  forensic evidence") sets the bar; the current schema anticipates gateway
  payloads and isn't receiving the most important one. Current ZarinPal v4
  practice is to persist code/RefID/fee at verification time.
- I-3: secret-in-logs is a standing OWASP logging item (A09:2021). The fix is
  one choke point, no behavioral change.
- Discarded advice: "verify then re-verify on success page" (double-charge
  risk on some gateways) and "store card PAN" (PCI scope, and ZarinPal only
  returns a mask) were both rejected deliberately.

### 5.4 Flag

I-4's queue option assumes the every-minute cron worker is installed (RUNBOOK
§2/§3 — it's on your post-deploy checklist). Synchronous-forever is also
acceptable; both fit cPanel. No other flags.

---

## 6. SEO + AEO

### 6.1 Findings

**S-1 (P1) — robots.txt group semantics violate RFC 9309.** Per the robots
spec (and Google's published interpretation, updated 2026-07): *only one group
applies to a given crawler — the most specific `User-agent` match — and
"user agent specific groups and global groups (*) are not combined."* The
current file (`SeoController.php:33–74`) puts `Disallow: /admin`, `/dashboard`,
`/checkout`, `/payments`, `/video-playback` **and** the `Content-Signal` line
in the `User-agent: *` group, then gives GPTBot, ClaudeBot, OAI-SearchBot,
PerplexityBot et al. their own groups containing only `Allow: /`. For those
nine named agents the Disallow rules and the Content-Signal declaration
**cease to exist**. Practical impact is limited (those paths 403/redirect for
anonymous crawlers anyway), but the file currently documents a policy it does
not enforce, and crawl-budget-wise the named bots are invited to wallow in
login redirects.

**S-2 (verified-current, no change): the GEO stack is correctly calibrated for
2026.** The layered approach — Markdown twins on the same URLs + `llms.txt`
index + Accept-header negotiation + explicit AI-crawler permissions — matches
what the evidence actually supports: server-log studies through mid-2026 show
major retrieval crawlers (GPTBot, ClaudeBot, PerplexityBot) barely fetch
`/llms.txt` at all (one 12-week, 83-site study: OpenAI fetched robots.txt
3,990× vs llms.txt 7×; Perplexity 775× vs **0**), Google's May 15, 2026 AI-optimization
guide formally lists llms.txt as ignorable, and **no major vendor has committed
to consuming it** — while the *markdown twins themselves* (clean,
extractable content at the same URL) are exactly what retrieval agents do
consume. DECISIONS.md's Round-4 framing ("robots/llms.txt alone are low-yield
but free; the markdown twin is the high-value signal") is precisely the
current consensus. The documented anti-patterns (UA sniffing/cloaking,
`meta ai-content-url`, `.well-known/ai.txt`) match the discard list. **Keep
all of it; expect nothing from the file itself.**

**S-3 (verified-current, expectation note): FAQ rich results are gone.**
Google stopped showing FAQ rich results on **May 7, 2026** (Search Console
report removed June, API August) — including the former government/health
exception. The FAQPage JSON-LD on `/` remains valid and parsed-for-understanding;
keep it (removal has no upside, other engines/AI retrieval still parse it),
but nothing should be *claimed* for it. Any doc that promises FAQ rich
results should be corrected — I found no such claim in the repo's own docs,
so this is a note, not a fix.

**S-4 (verified-correct inventory):** sitemap cached 1h with observer-based
invalidation and `Cache-Control: public, max-age=3600` (`SeoController.php:79–94`);
lastmod/priority present; filtered catalog pages `noindex, follow` with clean
canonicals (`catalog/index.blade.php:16–17`); learner + admin + error pages
noindexed; JSON-LD graph is Organization + WebSite(+SearchAction) site-wide,
Course + BreadcrumbList + ItemList on catalog/subject/course pages, with
`JSON_HEX_TAG` escaping and the `'@' . 'context'` Blade workaround; fonts
preloaded with `crossorigin` + immutable caching + deflate in `.htaccess`.
Structured data is used only where visible content exists — the rule Google
states explicitly.

**S-5 (P3) — covered images have no dimension contract.** Course/blog covers
are rendered where admins provide them; without enforced width/height or
aspect-ratio attributes they can contribute CLS. Minor today (no live media),
structural once real covers arrive — fold into the hero-photo fix as a
convention: every content image ships with dimensions.

### 6.2 Fix (S-1)

Build each named-agent group as a full group — the Disallow set plus the
Content-Signal line, then `Allow: /`:

```
User-agent: GPTBot
Content-Signal: search=yes, ai-input=yes, ai-train=yes
Disallow: /admin
Disallow: /dashboard
Disallow: /checkout
Disallow: /payments
Disallow: /video-playback
```

…repeated per named agent. In `SeoController` this is one refactor (a
`$protected = [...]` array + a group builder) rather than nine copy-pastes,
and `GeoTest` should gain an assertion that **every** named-agent group
contains the Disallow set (the current test only checks presence of agents,
which is how this slipped through).

### 6.3 Rationale

RFC 9309 §2.2.1 and Google's robots.txt spec page both state the
most-specific-group rule and the no-merging rule in exactly these words; the
fix makes the file do what its comments already claim. The S-2/S-3 items are
recorded so the client holds correct expectations: the current implementation
follows the 2026 evidence, and the one thing 2026 changed (FAQ SERP feature)
affects expectations, not code.

### 6.4 Flag

None.

---

## 7. Micro-interactions (audit of what exists; proposals are §7.3)

### 7.1 Existing system (verified)

The motion inventory is small, tokenized and purposeful: `interactive-card`
hover float (transform/box-shadow only, 180ms, `app.css:294–308`), button
hover color shifts (160ms), Alpine dropdown/toast transitions (100–300ms),
flash-toast auto-dismiss with fade, submit-button loading state with spinner
dot (`app.js:36–55`), single error shake on `[role=alert]` (`app.js:101–107`),
one-time scroll reveal with stagger (`app.js:63–95`, gated on
`prefers-reduced-motion` + JS opt-in so content is never hidden without JS),
deck-study reveal transition, and the video progress bar scale transform.
All timing sits in the 100–320ms band; a single global reduced-motion rule
collapses everything (`app.css:492–509`). **This system complies with §3's
purposeful-only rule as written.**

### 7.2 Findings

- **M-1 (P3):** the video player has no explicit *loading* state between
  "user pressed play" and "signed URL arrived" (`learner/video.blade.php:139–163`
  fetches asynchronously). On a slow shared-host round trip the player just
  sits there — the one place where feedback is genuinely missing.
- **M-2 (P3):** the payment success page shows the invoice number as plain
  text; there is no copy affordance for the one string users are asked to read
  back to support.

### 7.3 Proposals (all optional; all purposeful-only)

1. **Player loading state:** a `x-show` spinner/skeleton over the player until
   `manifest` resolves, with an error state on failure (fetch already handles
   failure — it currently fails silently). Clarifies state; reuses the
   existing `spinner-dot` component; 0 new dependencies.
2. **Invoice-number copy button:** click-to-copy with a 1.5s "کپی شد" tick
   confirmation. Action confirmation, the exact category §3 permits.
3. That's the whole list. Deliberately **not** proposed: parallax, animated
   gradients, scroll-jacking, page-transition animations, card tilt effects —
   all decorative under §3's definition, and the existing design's strength is
   its restraint.

### 7.4 Rationale

Both proposals close a feedback gap (loading clarity, action confirmation) —
the two categories the motion policy names as legitimate. Timing/easing reuse
existing tokens (160/180ms, ease-out), so no new motion vocabulary is
introduced.

### 7.5 Flag

None.

---

## 8. Research ledger (§4 compliance — what was applied, what was discarded)

| Topic | Source (dated) | Applied | Discarded & why |
|---|---|---|---|
| Laravel security advisories | CVE-2026-48019 (CRLF injection in mail validation; fixed 13.10.0/12.60.0) — published Jun 2026, picked up by trackers Sep 2026 | Verified lock file (13.26.1) is patched; recommend adding `composer audit` to CI (absent today) | — |
| Password hashing | PHP 8.4 raised bcrypt default to 12 (Securing Laravel / PHP RFC) | `BCRYPT_ROUNDS=12` confirmed current; no change | Argon2id migration — unnecessary churn for this threat model; bcrypt12 is the 2026 baseline |
| Robots/AI crawler control | Google robots.txt spec (updated 2026-07-08); RFC 9309 | Finding S-1 + fix | — |
| AI crawler tokens | 2026 UA landscape references (GPTBot/OAI-SearchBot/ChatGPT-User, ClaudeBot/Claude-SearchBot/Claude-User, PerplexityBot/-User, Google-Extended all current) | Repo list verified current; optional additions (CCBot, Applebot-Extended, Meta-ExternalAgent) are policy, listed in §9 Q5 | Blocking unnamed training bots via the `*` group — rejected: your stated policy is allow, and `*` already encodes it |
| llms.txt / GEO | 12-week server-log study (ezy.ai, Jul 2026); Google AI-optimization guide (May 2026); UA landscape (nohacks.co, Aug 2026) | Confirmed the existing strategy; recorded expectations | `llms-full.txt`, UA-sniffed markdown, `ai.txt`, meta hints — all remain correctly discarded by Round 4 |
| Structured data | Google FAQ rich-result retirement (May 7, 2026, TheHoth/wildnet reports of the docs change) | S-3 expectation note; keep markup | Removing FAQPage — no upside, still parsed |
| Core Web Vitals | Thresholds unchanged for 2026: LCP ≤ 2.5s, INP ≤ 200ms, CLS ≤ 0.1 @p75 (web.dev-derived benchmarks, Jun–Jul 2026) | Hero-photo performance contract (§3.2) | — |
| Query hygiene | Laravel Eloquent docs; framework injection history (parameterization is safe; raw-string interpolation is not) | Verified: zero user-input `whereRaw`/`DB::raw` in the codebase; all LIKE inputs escaped (`addcslashes` at 5 sites) | Generic SQL advice — not applicable to Eloquent usage found |
| DB store behavior | Laravel framework issue #28581; database-store pruner package docs | Findings D-1/D-2 + fixes | Third-party pruner package — rejected (one DELETE doesn't justify a dependency) |
| ZarinPal API | ZarinPal v4 official docs (request/verify endpoints, Rial amounts, status 100/101) | I-1 verification; I-2 receipt persistence | — |

---

## 9. Decisions requested from you

1. **Hero photo source** — you supply a licensed image (send it and the spec
   in §3.2 applies), or I generate candidate options with the image tool for
   you to pick (disclosed as AI imagery)?
2. **Landing trust-strip** — keep the 4-card trust section, or fold it into
   the hero badges (removes the most template-like section, §3.4)?
3. **`admin_activity_logs` retention** — keep 90 / 180 / 365 days / forever?
4. **Zibal** — finish the driver + callback this pass, or keep deferred?
5. **robots.txt additions** — add CCBot / Applebot-Extended /
   Meta-ExternalAgent named groups (allow, same as everything else), or leave
   them to the `*` group? (Both work once S-1 is fixed.)
6. **Login tightening** — outer `throttle:20,1` by IP only (recommended), or
   also raise the per-identifier decay 60s → 300s?
7. **Auth pages indexing** — keep `register` indexable and noindex the rest of
   `auth/*` (recommended), or noindex all?
8. **Announcement/footer copy** (§3.4) — approve the truthful rewordings, or
   supply your own pre-launch wording?

Approve items and I'll implement in a second pass, test-by-test, with CI green
on the arena branch before anything is marked done.

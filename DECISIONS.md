# DECISIONS.md

Running log of decisions made that were not explicit in the brief, per the
working-process rules. Newest first.

## 2026-09-05 — Round-4 audit: LLM/answer-engine visibility, correctness & motion

### Resolved

| Decision | Rationale |
|---|---|
| **GEO: `.md` twins + `/llms.txt` + `Accept: text/markdown` negotiation + robots `Content-Signal`** | 2026 practice (llmstxt.org convention; Evil Martians' tested ranking): the markdown twin is the only high-value, zero-dependency signal for agent retrieval; robots/llms.txt alone are low-yield but free. Everything is static-safe on cPanel: same routes + one middleware, no queue, no new package. **Explicitly NOT done** (documented anti-patterns): User-Agent sniffing to serve markdown (cloaking — search-engine penalty), `meta ai-content-url` / `<meta name="llms">` (ignored, pollutes HTML), `/.well-known/ai.txt` (no consumer), AI toggle buttons, HTML-comment hints (stripped by parsers). |
| **AI crawlers stay allow-all on public pages (open question #7 kept open)** | Policy now lives in one place (`SeoController::robots`) with retrieval vs training agents separated by comment, and the explicit `Content-Signal: search=yes, ai-input=yes, ai-train=yes` line makes the permission machine-readable. Flipping training agents to `Disallow` later is a one-line change per bot. Gating is still hard: paywalled routes are auth/entitlement-middleware protected, so robots.txt is a courtesy, not the fence. |
| **Markdown twins share one builder (`SiteMarkdown`) with the HTML pages** | Same Eloquent rows → no drift between `/courses/x` and `/courses/x.md`. Legal body copy and plans FAQ extracted to `App\Support\LegalContent` / `App\Support\PlanFaq` so HTML, JSON-LD and markdown all render from one array (the old FAQ item 4 was meta copy about the page's own design, not a customer question — replaced with a real one; flagged for client review). |
| **Route order: `.md` routes registered before parameterized HTML routes** | Laravel matches in registration order; `/blog/{slug}` would otherwise capture `post.md` as a slug and 404. The `{page}.md` legal twin additionally uses `whereIn` so the catch-all can't shadow it. |
| **Free-cap counting now scoped to *published* courses** | Correctness fix, not a product change: the "2 free videos globally" cap consumed quota by items on archived/soft-deleted courses (fail-closed denial of legitimate free views + blocked admin designations). `CourseVisibility::onPublishedCourses()` applies the `Course::published()` scope per item relation; `CourseFreeCapObserver` invalidates the 300 s cap cache when a course's status/publish-date/deletion changes. |
| **Video media cache: `private, max-age=290` (was `no-store`)** | The signed URL lives exactly 300 s and entitlement is re-checked on every server request, so the browser may keep bytes just under the signature window — range-seeks and replays within the window no longer re-stream. `no-store` gave zero benefit (the cache would never have been shared anyway: `private`) and forced full re-downloads. |
| **Password reset deletes every stored session of the user** | Reset assumes a compromised account; the database session driver makes this a plain `DELETE ... WHERE user_id = ?` inside the reset transaction. No effect on array/file drivers (dev). |
| **`Password::uncompromised()` on register + reset** | HaveIBeenPwned range lookup; the framework rule fails open on network errors, so a flaky cPanel connection can never block signups. Test passwords updated because the old fixture (`password123`) is in the breach corpus. **(Superseded by round 8: the rule does not fail open, and the check is now opt-in and off by default.)** |
| **Collation default `utf8mb4_unicode_ci` → `utf8mb4_0900_ai_ci` (mysql connection only)** | 0900 is the MySQL 8 default (Unicode 9.0, faster than the 4.1.0 legacy). Affects only tables created by fresh migrations; existing deployments keep their collation until intentionally converted (a whole-DB conversion is a downtime operation the client must choose). MariaDB connection keeps `unicode_ci` (no 0900 family there). |
| **og:image committed as a binary asset (`public/images/og-default.png`)** | Consistent with the existing convention (placeholder media is committed under `public/` and `database/placeholder-media/`); per-page overrides via the `og_image` section. Latin-only artwork deliberately — no Persian text rendering in generated images. |
| **Motion system: transform/opacity only, 150–320 ms, reduced-motion gated in both CSS and JS** | Matches the existing `DESIGN-airbnb.md` reduced-motion rule; JS scroll-reveal is progressive enhancement (content is visible without JS or with reduced motion). Replaced the width-animating `focus:w-72` search input (layout animation) with border/shadow feedback. No new JS dependencies. |
| **Footer subject links now data-driven (visible subjects, 5-min cache)** | The old hard-coded `?subject=cardio|neuro|anatomy|physiology` links matched no database slug — every click rendered an empty catalog page. Now lists real subjects with their real names and links to `subjects.show`. |

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
2. ~~Real Toman prices for 1-month / 3-month plans (seeder uses
   placeholders).~~ **Resolved 2026-09-07** — client confirmed the lineup:
   رایگان / یک‌ماهه ۲۷۰ تومان / سه‌ماهه ۶۰۰ تومان (see "Round 7").
3. ~~OTP SMS verification required at launch?~~ **Resolved 2026-09-07** —
   registration now sends a one-time code to the mobile number *in addition*
   to the emailed link, and either one activates the account (see "Round 9").
   The SMS panel itself is still an open choice: the driver is generic HTTP
   and defaults to `log` until the client names a provider.
4. Video hosting/CDN provider preference.
5. Content volume at launch (sizes the admin workflow).
6. ZarinPal and/or Zibal — primary or user-choice at checkout?
7. AI-crawler visibility for free content (currently **allowed**).
8. The 5-color brand palette design file — needed to replace placeholder hexes.
9. Admin roles: single admin (current) or multiple permission levels?

## Round 5 decisions (2026-09-05, first full-suite execution)

1. **Signed routes own their model resolution.** Laravel runs implicit
   route-model binding *before* route middleware, so a resource id in a
   `signed` URL must be resolved manually in the controller
   (`findOrFail`) — otherwise a tampered signature 404s instead of 403 and
   leaks resource existence. Applied in `VideoController::media`; rule for
   any future signed endpoint.
2. **`subscriptions.status` is always written explicitly** ('scheduled' at
   insert, `activate()` flips to 'active'). The column is NOT NULL with no
   default; relying on model events/defaults here crashed every verified
   payment.
3. **Never write a bare `'@context'` literal in Blade.** Laravel 13 compiles
   `@context` as the context-passing directive even inside
   `{!! json_encode([...]) !!}`. Convention in this repo: `'@' . 'context'`
   in every schema block.
4. **Blog deletion from the admin panel is permanent** (`forceDelete`).
   There is no restore UI, so soft deletes would only accumulate orphan
   rows. The `SoftDeletes` trait stays on the model as a guard for
   programmatic deletes.
5. **The free tier is a product constant, not DB content.** If no
   zero-price plan row exists, `PlanController` prepends a synthetic one —
   the plans page must never hide the free tier, mirroring how
   `EntitlementService` constants define the quota. Seeded wording states
   the cap is global (whole archive), not per course.
6. **Untrusted URLs are validated on every hop and pinned to the validated
   IP.** The Telegram bot's URL-import path refuses relative redirects,
   follows at most 3 absolute http(s) hops (each re-checked against public
   IP space), and connects via `CURLOPT_RESOLVE` to close DNS-rebinding.
   IPv6-only targets fail closed — acceptable trade-off for an admin-only
   import path on cPanel shared hosting.

## Round 6 decisions (2026-09-05, AUDIT-03 implementation)

Client answered all §9 questions of `docs/AUDIT-03-FULL-STACK-AUDIT.md` on
2026-09-05; item IDs below refer to that report.

1. **Hero is a photograph, not the cancelled 3D-heart video.** Three
   AI-generated candidates were produced (client informed the asset is
   AI-generated, disclosed here as required): `heart-model` (shipped
   default), `hero-atlas`, `hero-study` — previewed at
   `/hero-candidates.html` during the working session; the final pick is a
   one-file swap (`public/images/hero/hero.{webp,jpg}` + alt text).
2. **The 4-card trust strip stays as-is.** No decorative or animated
   replacement; copy kept honest under decision 3.
3. **`admin_activity_logs` retention is forever.** The prune command added
   this round (`broca:prune-cache`) deliberately does NOT touch activity
   logs or sessions beyond the framework's `session:prune`; the audit trail
   is a compliance asset, not cache.
4. **Zibal is finished and inert.** Driver, callback route
   (`/payments/zibal/callback`) and ledger receipt flow are implemented and
   tested; checkout remains disabled behind `BROCA_CHECKOUT_ENABLED=false`.
   Activating Zibal is an env change (`PAYMENT_GATEWAY=zibal` +
   `ZIBAL_MERCHANT_ID`), not a code change. Per-user gateway choice at
   checkout stays a future product decision.
5. **Robots: three more training agents get named groups** (CCBot,
   Applebot-Extended, Meta-ExternalAgent) — allow-everything policy
   unchanged; every named group now repeats the full Disallow set per RFC
   9309 §2.2.1 (a crawler follows only its most-specific group).
6. **Login throttling is IP-only at the outer layer** (`throttle:20,1`).
   The identifier-decay idea from the audit was rejected as over-engineering
   for current traffic; the inner identifier lock stays as shipped.
7. **`/register` stays indexable; every other auth page and both payment
   result pages are `noindex, nofollow`.** Login/forgot/reset are
   transactional pages with zero search value; register is the acquisition
   page.
8. **Truthful-copy rewordings** (pre-launch payment claims removed):
   announcement bar, footer trust paragraph, hero badge, and trust-strip
   card no longer mention "secure payment" or "faculty oversight" — both
   claims were unverifiable before go-live. The payment-trust card now
   speaks about transparent pricing, which the product does control.
9. **Backup runs on the queue, not in the webhook** (`RunDatabaseBackup`
   job): a dump + upload can outlive Telegram's webhook patience and get
   retried into a duplicate run. The database queue is drained by the
   cron worker every minute — no daemon required on cPanel.
10. **Micro-interactions shipped only where they answer a real action:**
    invoice-number copy button (support asks users to read this string
    back). The proposed video-player loading state was DROPPED — the
    existing authorize button already carries loading/error feedback; a
    second indicator would be decorative.

## Round 7 decisions (2026-09-07, pricing lineup + presentation)

1. **The pricing lineup is three cards, and it is now the client's confirmed
   answer to open question 2:** رایگان (price 0) / اشتراک یک‌ماهه ۲۷۰ تومان
   (`price_irr` 2700) / اشتراک سه‌ماهه ۶۰۰ تومان (`price_irr` 6000). Prices
   stay DB content — `plans.price_irr` is Rial, the card renders Toman — so
   the seeder and `PlanController`'s synthetic free tier are the only sources;
   nothing is hardcoded in the view.
2. **Presentation is the CodeFronts "Scale-Up Focused Plan Hover" table
   (MIT), scoped under `.prc-05` and fully recolored to the Broca palette**
   — rausch accent for checkmarks, CTA hover and the featured glow;
   hairline/hairline-soft borders; ink/body/muted type; rausch-tint wash on
   the featured card; teal for the duration and per-month accents. The stock
   `#f2f0f7` section background, `Segoe UI` font and `oklch(0.6 0.2 300)`
   accent were dropped rather than overridden; the CSS reset is limited to
   descendants so the root keeps Tailwind utilities (`mt-12`).
3. **Three columns are explicit from `md` up, single column below.** The
   upstream `repeat(auto-fit, minmax(220px,1fr))` orphaned the third card into
   a second row around 900px; the grid is also capped (`min(100%, 73.5rem)`)
   so a trimmed lineup still reads as cards, not full-width panels.
4. **The free tier is labelled, not zeroed:** the price slot renders «رایگان»
   in teal instead of `0 تومان`, and the duration pill / per-month line use
   Persian numerals to match the surrounding copy.
5. **Multi-month tiers quote an honest per-month equivalent** (600/3 = 200
   Toman, i.e. ~26٪ below the 1-month tier), computed from the data against
   the *priciest* per-month paid tier as baseline — a `min()` baseline made the
   1-month plan its own reference and silently dropped the only informative
   line. If the lineup ever flattens, the line disappears instead of lying. No
   invented "تخفیف ویژه" badges. The featured tier
   remains data-driven (`duration_months === 3`) rather than a new admin flag:
   with a fixed three-plan lineup an extra column is not worth a migration.

## Round 8 decisions (2026-09-07, register/login to production level)

1. **The password policy is one object, not three copies.** `App\Support\PasswordPolicy`
   defines it, `/register`, `/reset-password` and the app-wide
   `Password::defaults()` binding all resolve to it. It also caps length at
   bcrypt's 72-byte read limit: an un-capped 90-char password is *stored
   truncated*, which means someone could sign in with its first 72 bytes.
2. **The HaveIBeenPwned `uncompromised()` check is opt-in
   (`BROCA_PASSWORD_LEAK_CHECK`, default off).** The old code claimed it
   "fails open on network errors"; it does not — the HTTP call happens inside
   validation, so on a restricted or slow host (Iranian shared hosting
   included) it either stalls the POST or rejects a perfectly good password.
   Blocking every signup because a third party is unreachable is the worse
   failure mode. The only cost is one weak-password class we also catch via
   case/digit requirements.
3. **Every credential path normalizes identically, before validation.**
   Register lowercases+trims the email and canonicalizes the phone
   (`PhoneNormalizer` handles Persian digits, `+98`, separators) — and login
   now does the *same* to the identifier. Previously login only lowercased
   emails, so an address autofilled as `Ali@Example.com ` could be stored
   trimmed and then never found. Normalization before the `unique` rule is
   what turns duplicates into 422s instead of DB-level 500s; a residual race
   (two POSTs, one row) is translated in the controller for the same reason.
4. **Input shapes must not 500 either.** `(string) ['x']` throws in PHP 8, so a
   `name[]=x` payload used to be a server error at `prepareForValidation()`.
   Non-strings now normalize to `''` and fail as ordinary `required`/`string`
   validation errors.
5. **Throttle answers are named limiters with Persian copy, not positional
   `throttle:20,1`.** A named limiter can render a custom response; positional
   ones can only produce the framework's "Too Many Attempts." For a form post,
   the visitor is bounced back with a readable notice (`session('error')`)
   instead of a raw 429 page. Budgets: register 6/min/IP, login 10/min/IP
   (composing with the controller's 5/min per identifier·IP), password reset
   6/min per address, verification resend 3/min per user, admin 2FA 5/min per
   admin.
6. **Login deliberately says one thing for both failure modes.** "Account not
   found" vs "wrong password" is an enumeration oracle on a public form; the
   friendlier-but-split wording was considered and rejected. (Registration keeps
   its `unique` messages — an accepted trade-off every Laravel app makes, and
   the phone+email pairing is what an attacker would need.)
7. **Admin 2FA got the two fixes that actually matter:** the 5-attempts/min
   bound (a 6-digit code over a ~90 s drift window was guessable at
   10/min/admin) and a replay guard — an accepted code is remembered per
   session so it cannot be re-submitted while the window is still open. A
   passing challenge also regenerates the session id, so a pre-2FA session
   never becomes the admin one.
8. **Suspension kills sessions immediately instead of at the next request.**
   `admin/users` update deletes the user's `sessions` rows (best-effort: no-op
   on a non-database driver, where `EnsureActive` still locks them out),
   mirroring what password reset already did.
9. **Email verification stays *not* required for login, but required for
   money.** Enrolling / watching / quizzing works unverified; checkout and
   media playback keep `verified`. Gating the whole learner area behind mail
   that a misconfigured SMTP host may never deliver would turn an infra risk
   into a total lockout; the notice page says plainly that purchasing unlocks
   after verification.
10. **`BROCA_MAIL_TO` (staging-only `Mail::alwaysTo`) was added** so a staging
    run can exercise real SMTP without mailing students; it is ignored in
    production by design. Operational detail moved to `docs/RUNBOOK.md` §11.
11. **`npm run build` now deletes `public/build` before invoking Vite.** CI's
    "committed assets must match a fresh build" gate failed on a PR that touched
    no frontend source: Tailwind's source detection scans every file in the repo,
    *including its own previous compiled stylesheet in `public/build`*, so a
    rebuild performed on top of the committed artifacts re-emits utility-shaped
    text found there — a one-word filter utility has been riding along in every
    build since `main`, which is what kept changing the content hash with nobody
    editing anything. Measured and rejected: `@source not "public/build"` (ignored
    by the Vite plugin), `build.emptyOutDir` in config and via `--emptyOutDir`
    (empties after the scan), wiping from `vite.config.js` at import time (the
    plugin builds its scanner during config loading, so also too late), and
    `source(none)` + explicit `@source` globs (dropped 470 real utilities,
    reverted). Only a pre-build shell wipe runs early enough; from a polluted tree
    it restores exactly the committed artifacts, so the build is idempotent and
    the CI gate is meaningful. Side effect of scanning everything: a bare utility
    name quoted in docs or comments becomes a "used" class, so prose must describe
    classes instead of writing selectors (this is how the issue was first
    reproduced, three times, while documenting it).
12. **The admin 2FA budget is one shared limiter for the four code-checking
    endpoints, and recovery-code regeneration got its own** (`admin-2fa-verify`
    5/min and `admin-2fa-codes` 10/min, both keyed by admin user id). Putting a
    single `admin-2fa` limiter on all five endpoints was the wrong shape: the
    code-verification limit is anti-guessing, and re-rolling recovery codes
    verifies nothing, so five typos in a neighbouring form could not block a
    deliberate action — and the pre-existing 2FA management tests, written
    against the old per-route `5,1`/`10,1` budgets, failed. The controller's
    manual `RateLimiter::hit()`/`clear()` now uses the same key the middleware
    builds, so wrong codes and route hits drain one budget instead of two
    parallel counters. `tests/TestCase.php` additionally clears the cache store
    in `setUp()`: limiters are cache state and `RefreshDatabase` truncates only
    tables, so a shared store turns ordinary auth tests into 429s.
13. **One counter per request, not two: the 2FA route limiter delegates counting
    to the controller.** Adding `throttle:admin-2fa-verify` alongside the
    controller's own `RateLimiter::hit()` on a wrong code made a single failed
    attempt consume two units of the same 5/min budget, so an admin who typed
    the code correctly on the sixth try was still refused — and the pre-existing
    management tests (written for the old per-route `throttle:10,1`/`5,1`)
    disagreed with the new arithmetic. The limiter now uses
    `Limit::after(fn ($response) => $request->routeIs('admin.two-factor.recover'))`:
    routes whose controller records failures itself are *checked* but not
    auto-incremented, while `/recover` (whose controller has nothing to clear on
    success) is counted by the middleware as usual. Clearing on a correct code
    stays in the controller, which is what makes honest typos harmless.
14. **Rate limiters are cache state, so the test case isolates them: `Cache::clear()`
    plus a unique `REMOTE_ADDR` per test.** `RefreshDatabase` truncates tables, not
    the cache, and the registration/login limiters are keyed by IP — every test in
    the suite shares 127.0.0.1, so one drained bucket turns an unrelated assertion
    into "Expected [201,301,302,303,307,308] but received 429". The isolation makes
    the limiters invisible to tests that are not testing them while leaving
    throttle behaviour fully testable inside a single test (constant address). The
    2FA limiter now delegates *all* counting to the controllers
    (`Limit::after(fn () => false)`), which is what makes "five wrong codes are
    answered, the sixth is refused, a correct sixth after honest typos succeeds"
    simultaneously true — and the `remember_web_` cookie test asks
    `Auth::guard()->getRecallerName()` instead of rebuilding the name, because
    Laravel 13 appends `sha1(SessionGuard::class)` to it.
15. **The admin 2FA admission test is the route limiter alone** (`5/min` per
    admin, counted on every request to challenge/recover/enable/disable);
    `Limit::after()` is *not* used. Two attempts to make the controllers own the
    bucket instead both failed in CI: sharing one cache key between middleware and
    controller made a single wrong code cost two units (so a correct sixth code
    was refused), and delegating with `after()` did not restore the promised
    arithmetic either. The controller's own counter stays on the sign-in
    challenge only, keyed separately, where its job is the human message and the
    clear-on-success — an honest typo storm gets a friendly countdown, while the
    route limiter is what actually refuses a scripted guesser. Lesson recorded
    because the code comment is the only place a reader would learn it: do not
    let two layers decide admission on one budget.
## Round 9 decisions (2026-09-07, delivery, admin access, plan lineup)

Driven by four reports from the live site: *registration reached nobody*,
*the admin could not sign in with correct credentials*, *two of the three
pricing cards were missing*, and *the operator had no way to see any of it
without SSH*.

1. **Registration now delivers two independent proofs of contact: the emailed
   link AND an SMS one-time code — either one activates the account.** This
   resolves open question 3 ("OTP SMS verification required at launch?") in the
   form that survives a mail outage: the signup funnel used to have exactly one
   way in, so every failure of that one path (queue not drained, SMTP blocked,
   provider dropping mail, a spam folder) produced the same dead account. The
   gate is `App\Http\Middleware\EnsureVerifiedContact` (aliased
   `verified.contact`) — email **or** mobile — and it is wired into the routes
   explicitly rather than by overriding the framework's `verified` alias, so no
   future alias-merge order can silently change what guards `/checkout`.
2. **Transactional mail and SMS are delivered inline by default
   (`BROCA_NOTIFICATIONS_QUEUE=sync`).** They are the only messages a user must
   receive *during* the request that creates the account. With `database`, a
   host whose cron worker is missing, misconfigured or silently dead keeps the
   mail in `jobs` forever: the account is created and nobody can ever verify
   it. Inline costs a few hundred milliseconds of SMTP once per signup and
   removes that dependency; a host with a monitored worker opts back in with
   one env var. Backups and media jobs still use `QUEUE_CONNECTION`.
3. **A failed send never fails the signup.** Both channels are dispatched
   inside their own `try/catch` after the session exists, both are `report()`ed,
   and the notice page offers a resend for each. When *neither* channel could
   leave the building the app logs `critical` — the one case an on-call
   engineer must see next to the account rather than buried in a transport
   exception.
4. **SMS is driver-based with a `log` default.** No panel had been chosen, and
   hard-coding a vendor we cannot test would have shipped a credential nobody
   verified. `log` writes the message (and the code, outside production) to
   `laravel.log`, so the whole flow is exercisable with zero spend;
   `null` discards; `http` describes the request entirely in env — URL,
   method, JSON body template with `:to :message :from :code :reference`
   placeholders, success status, and an optional `success_contains` substring
   gate, because most panels answer HTTP 200 with an error body. Switching
   vendor is an env change, not a code change.
5. **The mobile code is stored bcrypt-hashed, expires in 10 minutes, allows 5
   attempts and is cleared on use/expiry/lockout.** A 6-digit code is 10⁶
   possibilities, so the window and the attempt budget — not the hash alone —
   are what make a table dump unprofitable.
6. **Login resolves the account tolerantly and says why it refused.**
   `App\Support\UserLookup` matches the canonical form first (indexed) and
   then the historical spellings a row may still hold (`Admin@Example.com `,
   `+98912…`, Persian digits) — one implementation shared with the operator
   commands, so "the command says the account is fine but the form says the
   password is wrong" cannot happen. A found-but-inactive account now gets its
   own message: showing it costs nothing (the visitor already knows the account
   exists) and it ends the worst support case the app had — an operator typing
   a correct password into a suspended account and being told the password is
   wrong. Unknown identifiers keep the single neutral message.
7. **The canonical plan lineup lives in code (`App\Support\PlanCatalog`) and
   is reconciled into the database by `broca:sync-plans`, which the deploy hook
   now runs.** The pipeline migrates but never seeds, so on a host whose
   `plans` table was never populated `/plans` rendered the controller's
   synthetic free tier and nothing else — one card where the client expects
   three. The command is idempotent and, without `--reset`, never overwrites a
   price an operator edited. The seeder reads the same array, so there is no
   second copy of the numbers; the admin panel and the bot both surface a
   missing lineup instead of leaving it silent.
8. **The Telegram bot refuses to suspend or demote the last active admin.**
   The web panel always refused; the bot did not, so one tap could leave the
   site with no administrator able to sign in — which is itself one of the ways
   "the admin cannot log in" happens. The bot also gained an operations menu
   (health report, plan-lineup restore, queue status + manual drain, SMS test)
   and per-account email/mobile verification, rendered from the same
   `App\Services\OpsHealthReport` as `php artisan broca:ops:health` so the
   phone and the shell never disagree.
9. **Four operator commands, because you cannot fix a broken login from inside
   a session that requires the login to work:** `broca:user:diagnose`
   (read-only: finds the account, then names which of the four blockers is in
   the way), `broca:user:repair` (`--activate --verify-email --verify-phone
   --normalize --promote --demote --password --logout`; the last-admin guard is
   absolute, not a prompt), `broca:identifiers:normalize` (rewrites every
   stored email/phone into the canonical spelling) and `broca:sms:test`.

### Residual open items (not blocking)

- Final hero pick from the three candidates (swap = copy 2 files + alt
  text) — user to choose via the live preview.
- `llms.txt` is kept (built, tested, near-zero cost) but its value is
  unproven: no major vendor has committed to reading it (2026 studies).
  The robots named-group fix is the actual control for AI crawler access.

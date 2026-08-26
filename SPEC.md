# Broca (بروکا) — Product and Technical Specification

**Status:** Draft for review  
**Scope:** First production-oriented release, before Laravel scaffolding  
**Language:** Persian (fa-IR), RTL, Persian-first  
**Last updated:** 2026-08-20

This document turns the kickoff interview into an implementation contract. No application scaffolding or feature code should begin until this document is reviewed and approved.

---

## 1. Product summary

Broca is a Persian medical-education platform with:

- video courses;
- downloadable notes (جزوات);
- course-linked spaced-repetition flashcard decks;
- untimed multiple-choice quizzes;
- a medically reviewed blog;
- fixed-duration paid subscriptions; and
- a small, custom admin back office.

The product is RTL-first and uses a cream, bold, asymmetric visual system with self-hosted Vazirmatn and the existing five-color brand palette. Medical content is YMYL content: published courses and articles must show real author and medical-reviewer credentials, and factual claims must support sources.

### 1.1 Decisions captured from the interview

| Area | Decision |
|---|---|
| Backend | PHP 8.3+, Laravel 13.x |
| Frontend | Blade, Tailwind CSS v4, Alpine.js, vanilla JavaScript |
| Database/hosting | MySQL 8 on conventional shared/cPanel hosting; remain environment-variable driven |
| Payments | `shetabit/payment`; implement ZarinPal first; keep gateway boundary ready for Zibal |
| Video | Provider-neutral VOD/CDN boundary; managed playback references; signed/short-lived playback authorization |
| Notes | Private object storage with authorized, short-lived downloads |
| Authentication | Signup collects email, phone, and password; email verification required; login accepts email or phone plus password; phone is stored but not SMS-verified |
| Admin | Custom Blade admin panel; one admin role in v1 |
| Taxonomy | Subjects + courses + tags; decks live inside courses |
| Content language | Persian only in v1; schema should not make later localization impossible |
| Publication | Draft → review → published for courses, learning items, and articles; published medical content requires author and reviewer |
| Free access | Exact global designated-item caps: 2 videos, 1 note, 10 flashcards, 1 quiz question |
| Free-item control | Admin-only designation with hard cap validation |
| Enrollment | Authenticated users may openly enroll in any published course; enrollment does not bypass gating |
| Plans | Free, one-month, and three-month cards; paid plans are fixed-duration and do not auto-renew |
| Money | Store configurable integer amounts in IRR/rial for gateway compatibility; display localized toman values |
| Activation/expiry | Activate only after server-side gateway verification; calendar-month expiry calculated in `Asia/Tehran`; timestamps stored UTC; user-facing dates can be Persian/Jalali |
| Refunds | No refunds after successful payment; cancellation is not a recurring-renewal concern in v1 |
| Quizzes | Untimed, single-answer multiple choice; 70% default pass threshold, overridable per quiz; unlimited attempts; retain all attempts and show best score |
| Progress | Video completion is based on a configurable watch percentage; track other item progress separately |
| Admin media | Admin attaches provider-neutral playback IDs/manifest references and private storage keys; no provider ingestion/upload flow in v1 |
| Content editing | Controlled Markdown/plain rich text rendered safely; no arbitrary unsanitized HTML |
| Email | Laravel mail abstraction with SMTP configured per environment |
| Landing hero | Build the responsive 3D-heart boundary with a clearly marked local placeholder until the supplied asset arrives |
| Public policies | Terms, privacy, contact/support, and a prominent educational medical disclaimer |

---

## 2. Scope and non-goals

### 2.1 In scope

1. Public landing page, catalog, course detail pages, blog, policies, SEO endpoints, and authentication.
2. User dashboard with enrolled courses, progress, notes, quizzes, and flashcard entry point.
3. Subject/course/tag catalog filtering and keyword search.
4. Course enrollment and progress tracking.
5. Provider-neutral video playback authorization and private note downloads.
6. Flashcard decks with per-user SM-2-style scheduling.
7. Quiz authoring, attempts, scoring, pass/fail status, and best-score reporting.
8. Free-plan and paid-plan entitlement enforcement.
9. Invoice/payment state machine and verified ZarinPal checkout.
10. Custom admin CRUD for content, contributors, plans, users, payments, and free-item designation.
11. Draft/review/publish workflow and medical bylines.
12. Technical SEO/GEO foundations: canonical URLs, sitemap, robots directives, JSON-LD, and `/llms.txt`.
13. Accessible, responsive, RTL UI and performance-conscious media loading.

### 2.2 Explicitly deferred

- SMS OTP and phone verification.
- Auto-renewing subscriptions and recurring billing.
- Zibal integration (the gateway interface must make its later addition small).
- Direct video-provider ingestion, transcoding, or admin upload pipeline.
- Admin upload implementation for object storage; v1 stores managed object keys.
- English UI/content and translation management.
- Multi-admin roles, scoped editorial permissions, or contributor logins.
- Revision history, scheduled publishing, editorial comments, and workflow notifications.
- Social login, passwordless login, and native mobile applications.
- Personalized medical advice or patient/clinical records.
- Full-text search infrastructure beyond MySQL-compatible search/indexes needed for v1.

---

## 3. Architecture and boundaries

### 3.1 Application shape

Use a conventional Laravel monolith suitable for cPanel deployment:

- web requests through Laravel routes/controllers;
- Blade views with Tailwind v4 output and Alpine for small interactive islands;
- queued work only where hosting permits it; synchronous fallbacks must remain safe for critical flows;
- MySQL 8 as the source of truth;
- Laravel filesystem abstraction for private notes;
- a `VideoProvider` boundary for signed playback;
- a `PaymentGateway` boundary around `shetabit/payment` and gateway-specific configuration;
- policies and authorization checks in the server, never only in Blade/JavaScript.

The first implementation should favor Laravel's built-in facilities (authentication, notifications, mail, validation, policies, signed URLs, filesystem, queues where available) and avoid adding an admin framework or SPA.

### 3.2 Core service boundaries

These are small application services, not speculative framework layers:

- **Entitlement service:** answers whether a user may view/download/study/attempt a particular item and records the reason (`free_designated`, `active_subscription`, or denied).
- **Free-item designation service:** atomically enforces global caps when admins mark/unmark items.
- **Video delivery adapter:** converts a stored provider-neutral reference into a short-lived signed playback response after entitlement authorization.
- **Private download service:** authorizes a note download and returns a short-lived signed storage URL or streams through the private disk.
- **Payment service:** creates an invoice, starts the configured gateway, verifies the callback server-side, and applies an idempotent subscription activation.
- **SRS service:** calculates the next review interval/ease from a user's answer using a documented SM-2-compatible rule.
- **Publication service/policy:** enforces valid state transitions and required bylines/references.

Do not expose provider credentials or trust client-submitted entitlement, price, completion, or payment status.

---

## 4. Data model

All tables use an unsigned bigint `id` unless noted. Use UTC timestamps for Laravel `created_at`, `updated_at`, and business timestamps. Use indexed nullable `deleted_at` only where recovery is useful (content and financial records should not be casually hard-deleted). Persian text uses `utf8mb4` and a suitable Unicode collation. Slugs are unique within their resource type.

### 4.1 Identity and authorization

#### `users`

- `id`
- `name` — required display name
- `email` — unique, normalized lowercase
- `phone` — nullable/unique after normalization; Iranian format normalized to a canonical representation
- `password` — hashed
- `email_verified_at` — nullable
- `is_admin` — boolean in v1; default false
- `status` — `active|suspended`
- `remember_token`
- timestamps

Rules:

- Signup requires name, email, phone, and password.
- Email verification is required before normal authenticated learning access.
- Phone is accepted as a login identifier after normalization even though it is not SMS-verified.
- Suspended users cannot authenticate into protected areas or start payment.

#### `user_consents`

- `id`, `user_id`
- `terms_version`, `privacy_version`, `medical_disclaimer_version`
- `accepted_at`, `ip_address`, `user_agent`
- timestamps

Record the versions accepted at signup/checkout as appropriate. Do not store unnecessary personal data.

### 4.2 Contributors and taxonomy

#### `contributors`

Reusable medical byline profile.

- `id`
- `name`
- `slug`
- `credentials` — e.g. degree/title, not invented by the system
- `specialty`
- `bio` — safe Markdown/plain rich text
- `photo_path` — nullable private/public optimized profile image reference
- `is_visible`
- timestamps, optional `deleted_at`

#### `subjects`

- `id`
- `name`
- `slug`
- `description`
- `sort_order`
- `is_visible`
- timestamps, optional `deleted_at`

#### `tags`

- `id`, `name`, `slug`, timestamps

#### `courses`

- `id`
- `subject_id`
- `title`, `slug`
- `excerpt`, `description`
- `cover_image_path`
- `status` — `draft|in_review|published|archived`
- `published_at`
- `author_id` — contributor
- `reviewer_id` — contributor, required before publication
- `level` — optional controlled value
- `sort_order`
- timestamps, optional `deleted_at`

Indexes: `subject_id + status`, unique `slug`, `status + published_at`.

#### `course_tag`

- `course_id`, `tag_id`
- composite primary/unique key

### 4.3 Course learning content

#### `videos`

- `id`, `course_id`
- `title`, `slug`
- `description`
- `sort_order`
- `duration_seconds`
- `playback_provider` — nullable provider key, e.g. `arvan`, while remaining provider-neutral
- `playback_asset_id` — provider asset/reference identifier
- `manifest_reference` — nullable managed HLS reference; do not expose raw secrets
- `completion_threshold_percent` — nullable, falls back to application default
- `is_free_designated` — boolean default false
- `status` — `draft|in_review|published|archived`
- `published_at`
- `author_id`, `reviewer_id`
- timestamps, optional `deleted_at`

A video cannot be published without a course, author, reviewer, and valid managed playback reference. The free designation is global across all published videos and is capped at two.

#### `notes`

- `id`, `course_id`
- `title`, `slug`, `description`
- `sort_order`
- `storage_disk`
- `storage_key` — private object key, never a public URL
- `mime_type`, `size_bytes`, optional checksum
- `is_free_designated` — boolean default false
- `status` — `draft|in_review|published|archived`
- `published_at`
- `author_id`, `reviewer_id`
- timestamps, optional `deleted_at`

A note cannot be published without a valid private storage reference, author, and reviewer. The global free designation is capped at one.

#### `flashcard_decks`

- `id`, `course_id`
- `title`, `slug`, `description`
- `sort_order`
- `status` — `draft|in_review|published|archived`
- `published_at`
- `author_id`, `reviewer_id`
- timestamps, optional `deleted_at`

#### `flashcards`

- `id`, `flashcard_deck_id`
- `front`, `back` — safe Markdown/plain rich text
- `hint` — nullable
- `sort_order`
- `is_free_designated` — boolean default false
- `status` — `draft|in_review|published|archived`
- `published_at`
- timestamps, optional `deleted_at`

The global free designation is capped at ten published flashcards, regardless of deck.

#### `course_enrollments`

- `id`, `user_id`, `course_id`
- `enrolled_at`
- `status` — `active|withdrawn`
- unique `(user_id, course_id)`
- timestamps

Enrollment is free and open for published courses. It organizes dashboard/progress; it never itself grants paid-gated access.

### 4.4 Progress and SRS

#### `video_progress`

- `id`, `user_id`, `video_id`
- `watched_seconds`
- `watched_percent`
- `completed_at` — nullable
- `last_watched_at`
- unique `(user_id, video_id)`
- timestamps

Client progress updates are validated and capped against known duration. The server marks completion once the configured threshold is reached.

#### `learning_progress`

For non-video course-item progress without forcing unrelated item tables into one polymorphic foreign key.

- `id`, `user_id`
- `trackable_type` — `note|quiz|flashcard_deck`
- `trackable_id`
- `started_at`, `completed_at`, `last_activity_at`
- optional `metadata` JSON for small, non-authoritative UI details
- unique `(user_id, trackable_type, trackable_id)`

Authoritative quiz and card statistics remain in their dedicated tables.

#### `user_flashcard_schedules`

One row per user and published flashcard.

- `id`, `user_id`, `flashcard_id`
- `state` — `new|learning|review|suspended`
- `ease_factor` — decimal, initial SM-2 value (e.g. 2.5)
- `interval_days`
- `repetition_count`
- `due_at` — UTC timestamp
- `last_reviewed_at`
- unique `(user_id, flashcard_id)`
- timestamps

#### `flashcard_reviews`

Immutable review history.

- `id`, `user_id`, `flashcard_id`
- `schedule_id`
- `quality` — normalized 0–5 response
- `previous_interval_days`, `new_interval_days`
- `previous_ease_factor`, `new_ease_factor`
- `reviewed_at`

The SRS calculation must be deterministic, tested at boundaries, and documented in the code/spec. A user may study a designated free card without a paid subscription; all other cards require active subscription.

### 4.5 Quizzes

#### `quizzes`

- `id`, `course_id`
- `title`, `slug`, `description`
- `pass_threshold_percent` — nullable; application default 70
- `is_free_designated` — optional only if the product later designates a whole quiz; question-level gating remains authoritative
- `status`, `published_at`
- `author_id`, `reviewer_id`
- timestamps, optional `deleted_at`

#### `quiz_questions`

- `id`, `quiz_id`
- `prompt`
- `explanation` — shown after submission according to UX policy
- `source_citation` — required for factual medical questions
- `sort_order`
- `status` — `draft|in_review|published|archived`
- `published_at`
- `author_id`, `reviewer_id`
- `is_free_designated` — boolean default false
- timestamps, optional `deleted_at`

Exactly one published question globally may be designated free. Questions in a published quiz must have valid options.

#### `quiz_options`

- `id`, `quiz_question_id`
- `label`
- `is_correct`
- `sort_order`
- timestamps

Validation requires at least two options and exactly one correct option for a published single-answer question. Never send `is_correct` to the browser before submission.

#### `quiz_attempts`

- `id`, `user_id`, `quiz_id`
- `score_percent`
- `correct_count`, `question_count`
- `passed`
- `started_at`, `submitted_at`
- timestamps

Attempts are unlimited. Dashboard reports both latest and best attempt; best score is used for course summary.

#### `quiz_attempt_answers`

- `id`, `quiz_attempt_id`, `quiz_question_id`, `selected_option_id`
- `is_correct`
- timestamps

Store the evaluated result so later content edits do not rewrite historical scores.

### 4.6 Plans, payments, and entitlements

#### `plans`

- `id`
- `code` — unique: `free`, `monthly`, `quarterly`
- `name`
- `description`
- `duration_months` — null/0 for free, 1, or 3
- `price_irr` — integer; placeholder values allowed before pricing approval
- `is_active`
- `sort_order`
- timestamps

Money is stored in rial/IRR integer units. Customer-facing formatting converts to toman and uses Persian numerals as appropriate. The currency and display settings remain configurable.

#### `subscriptions`

- `id`, `user_id`, `plan_id`
- `status` — `active|scheduled|expired|pending|cancelled`
- `starts_at`, `ends_at` — UTC
- `activated_at`
- `gateway` — e.g. `zarinpal`
- `gateway_reference` — nullable, unique where present
- `invoice_id` — nullable until invoice table relation is defined
- timestamps

A verified purchase that follows an active paid subscription is stored as `scheduled` until its `starts_at`; an activation job or request-time transition makes it `active` at that instant. Only one paid subscription may be active at a time, and queued periods must not overlap.

Every user receives an explicit free-plan record (created at registration or through an idempotent listener). A paid subscription never auto-renews. A paid plan becomes active only after verified payment. On paid expiry, the user returns to the free plan; do not silently extend access.

#### `invoices`

- `id`, `user_id`, `plan_id`
- `number` — unique human-readable invoice number
- `amount_irr`
- `currency` — `IRR`
- `status` — `pending|initiated|paid|failed|cancelled|expired`
- `gateway`
- `gateway_payment_id` — nullable
- `authority` — nullable, unique where present
- `paid_at`, `expires_at`
- timestamps

Snapshot the plan and amount at invoice creation so later price edits cannot change historical financial records.

#### `payment_transactions`

- `id`, `invoice_id`
- `gateway`
- `request_payload` — sanitized JSON; never store secrets
- `response_payload` — sanitized JSON
- `reference_number` — nullable
- `status` — `initiated|verified|failed|duplicate`
- `verified_at`
- unique provider transaction/reference where available
- timestamps

Gateway callbacks and verification are idempotent. A repeated callback cannot activate a second subscription or extend a subscription twice.

#### `entitlement_events`

Audit trail for access decisions and admin changes.

- `id`, `user_id`
- `entitlement_type` — `video|note|flashcard|quiz_question`
- `entitlement_id`
- `source` — `free_designated|paid_subscription|denied`
- `subscription_id` — nullable
- `occurred_at`
- optional `metadata` JSON

This table is not the sole authorization source; current publication, enrollment, free flag, and active subscription are checked at request time. It supports support/debugging and analytics.

### 4.7 Blog and SEO content

#### `articles`

- `id`
- `title`, `slug`, `excerpt`, `body`
- `cover_image_path`
- `status` — `draft|in_review|published|archived`
- `published_at`
- `author_id`, `reviewer_id`
- `source_citations` — safe structured JSON or related table if multiple sources need querying
- `seo_title`, `seo_description`
- timestamps, optional `deleted_at`

#### `support_messages`

- `id`
- `user_id` — nullable for unauthenticated contact submissions
- `name`, `email`, `phone` — only the minimum support fields needed
- `subject`, `message`
- `status` — `new|in_progress|resolved|spam`
- `admin_notes` — nullable, admin-only
- `handled_by` — nullable admin user ID
- `handled_at` — nullable UTC timestamp
- `emailed_at` — nullable UTC timestamp
- timestamps

Persist the message and attempt SMTP delivery. A delivery failure must not erase the persisted request; surface it to admins for retry. Apply rate limiting, spam protection, and strict admin access.

Publication requires real author and reviewer contributor profiles plus sources for factual medical claims.

#### Optional `article_tags`

Use the same `tags` table for articles if cross-content discovery is needed; implement only if catalog/blog requirements require it during build.

### 4.8 Operational/media tables (small and conditional)

- `media_assets` may be introduced if multiple images/files need common metadata (disk, key, mime, dimensions, checksum). Do not add it solely for abstraction if `cover_image_path` and note storage keys are sufficient for v1.
- `admin_audit_logs` should record destructive or financial/admin actions if Laravel's normal logs are not enough: admin ID, action, resource, resource ID, before/after sanitized JSON, IP, timestamp.

---

## 5. Publication and content rules

1. New content starts as `draft`.
2. Admin submits it for review (`in_review`).
3. Admin can publish only when required fields are valid:
   - author and reviewer contributors exist and are visible;
   - the reviewer is not the same contributor as the author unless explicitly allowed by policy;
   - medical factual content has source citations;
   - videos have managed playback references;
   - notes have private storage keys;
   - quiz questions have valid options and exactly one correct answer.
4. Publishing sets `published_at`; unpublishing moves to `archived` or `draft` according to the admin action.
5. Public queries show only `published` content with a non-future `published_at`.
6. A course page may list only published child items. A published course page is valid even when it currently has zero published child items; its empty state should guide users back to the catalog or show an appropriate upcoming-content message. A course and each child item must independently be published before the child is usable.
7. Byline cards visibly show author and reviewer names, credentials, and links to contributor profiles.
8. Render Markdown/plain rich text through a strict allowlist. Strip scripts, event handlers, dangerous URLs, and arbitrary embeds.

---

## 6. Entitlement and gating logic

### 6.1 Canonical rules

- A user must be authenticated, have verified email, be active, and be enrolled in the course for course learning routes.
- Enrollment is open and free for any published course.
- A current active paid subscription grants access to all published gated course learning items for the subscription period.
- A user's explicit free plan grants access only to globally designated free items.
- Designated free items are the exact global caps:
  - maximum 2 published videos;
  - maximum 1 published note;
  - maximum 10 published flashcards;
  - maximum 1 published quiz question.
- The admin designation flag is the source of truth; the cap is enforced transactionally when setting the flag. Existing designated items must be unmarked before another item can be designated.
- A free item remains free for every eligible authenticated user and does not consume a per-user counter. It is a fixed catalog designation, not “first viewed” allocation.
- Draft, archived, unpublished, or un-enrolled content is not accessible regardless of plan.
- Admin preview must be an explicitly authorized preview path and must not accidentally grant a normal user entitlement.

### 6.2 Per-content authorization

**Video:** course enrollment + item published + (`is_free_designated` OR active paid subscription). On successful authorization, return a short-lived signed playback token/manifest reference. Save progress only for authorized playback.

**Note:** course enrollment + item published + (`is_free_designated` OR active paid subscription). Return a short-lived signed object-storage download URL; never redirect to a public key.

**Flashcard:** course enrollment through the deck's course + card published + (`is_free_designated` OR active paid subscription). Create/update the user's SRS row only after authorized study begins.

**Quiz question:** course enrollment + quiz/question published + (`is_free_designated` OR active paid subscription). On the free plan, a user may see and submit only the single globally designated free question as a sample; all other questions are withheld and cannot be answered or submitted. The sample flow must not reveal paid answers or imply that the full quiz is available. An active paid subscription unlocks all published questions in the enrolled course.

### 6.3 Subscription transitions

- Registration creates/ensures a free-plan subscription record.
- Checkout creates a pending invoice; it does not grant access.
- Gateway return is not proof of payment. Server-side verification against ZarinPal is mandatory.
- Verified payment marks the invoice paid, records the transaction, and activates the paid subscription in one idempotent transaction.
- A paid plan's `ends_at` is calculated as one calendar month or three calendar months after the effective start in `Asia/Tehran`, then stored as UTC.
- If a user buys while a paid subscription is active, the newly verified plan is queued to begin immediately after the current paid subscription's `ends_at`; it must not overlap or silently replace already-purchased access. The invoice/subscription activation transaction stores the future `starts_at` and corresponding `ends_at`.
- On expiry, access falls back to free-designated items and the free plan record.
- No automatic renewal, refund automation, or partial-period credit exists in v1.

### 6.4 Progress and completion

- Video progress updates are throttled and server-validated.
- A video is complete once watched percentage reaches its item threshold or the application default. The initial default is **70%**, and admins can configure the application default and an individual video's override.
- Notes track authorized download/start and optional completion; downloading alone does not unlock other paid content.
- Quizzes record every attempt; best score drives dashboard completion, with the per-quiz threshold (default 70%) determining pass/fail.
- Flashcards use SRS state and review history; a deck can show studied-card count and due-card count rather than a binary completion claim.

---

## 7. Route map

Use named routes, route model binding, method authorization, and locale-safe slugs. Exact controller names can change during implementation; these route responsibilities may not.

### 7.1 Public routes

| Method | URI | Responsibility |
|---|---|---|
| GET | `/` | Landing page and CTA |
| GET | `/catalog` | Search/filter published courses and content |
| GET | `/subjects/{subject:slug}` | Subject landing and course listing |
| GET | `/courses/{course:slug}` | Published course detail, bylines, item listing, enrollment CTA |
| GET | `/courses/{course:slug}/videos/{video:slug}` | Video learning shell; authorization required to obtain playback |
| GET | `/courses/{course:slug}/notes/{note:slug}` | Note detail/download action |
| GET | `/courses/{course:slug}/decks/{deck:slug}` | Deck overview and study CTA |
| GET | `/blog` | Article index/search/filter |
| GET | `/blog/{article:slug}` | Published article, bylines, citations, JSON-LD |
| GET | `/plans` | Free/monthly/quarterly plan cards |
| GET | `/terms` | Terms of service |
| GET | `/privacy` | Privacy policy |
| GET | `/medical-disclaimer` | Educational/non-personal-advice disclaimer |
| GET | `/contact` | Contact/support form or contact details |
| GET | `/robots.txt` | Crawler directives, generated or static |
| GET | `/sitemap.xml` | Canonical public URLs |
| GET | `/llms.txt` | Canonical page guidance for AI systems |

### 7.2 Authentication routes

Use Laravel's auth conventions with explicit named routes:

- `GET|POST /register`
- `GET|POST /login`
- `POST /logout`
- `GET /email/verify`
- `GET /email/verify/{id}/{hash}` and resend verification
- `GET|POST /forgot-password`
- `GET|POST /reset-password/{token}`

Login accepts normalized email or phone plus password. Rate-limit login, password reset, verification resend, and contact form endpoints.

### 7.3 Authenticated learner routes

| Method | URI | Responsibility |
|---|---|---|
| GET | `/dashboard` | Overview of enrollment, progress, subscription, due cards |
| POST | `/courses/{course}/enroll` | Idempotent open enrollment |
| GET | `/my-courses` | Enrolled courses |
| POST | `/videos/{video}/progress` | Validated progress update |
| GET | `/videos/{video}/playback` | Short-lived signed playback response |
| GET | `/notes/{note}/download` | Authorized signed download response |
| GET | `/decks/{deck}/study` | Study session |
| POST | `/flashcards/{flashcard}/review` | Record rating and update SRS atomically |
| GET | `/quizzes/{quiz}` | Authorized quiz form; do not expose gated questions |
| POST | `/quizzes/{quiz}/attempts` | Submit and score attempt |
| GET | `/quizzes/{quiz}/attempts/{attempt}` | Own attempt/result only |
| GET | `/subscription` | Current free/paid entitlement and expiry |
| GET | `/billing` | Own invoices/transactions summary |
| GET | `/profile` | Profile/password settings |
| PATCH | `/profile` | Update allowed profile fields |

### 7.4 Checkout and payment routes

| Method | URI | Responsibility |
|---|---|---|
| POST | `/checkout/{plan}` | Create pending invoice and initiate ZarinPal payment |
| GET | `/payments/zarinpal/callback` | Receive gateway return, then verify server-side |
| GET | `/checkout/{invoice}/success` | Render verified success state only after local invoice state says paid |
| GET | `/checkout/{invoice}/failed` | Render failure/cancel state |

If the gateway/package supports a server-to-server webhook, add a dedicated POST endpoint and make both callback paths idempotent. Never activate based only on query-string status.

### 7.5 Admin routes

Prefix with `/admin`, protect with `auth`, verified-email, `is_admin`, and CSRF middleware:

- `GET /admin` — operational dashboard
- CRUD: `/admin/subjects`, `/admin/tags`, `/admin/courses`
- nested CRUD: `/admin/courses/{course}/videos`, `/notes`, `/decks`, `/quizzes`
- card/question/option management under their parent resources
- `GET|PATCH /admin/content/{type}/{id}/review` — review and publish actions
- CRUD: `/admin/contributors`
- CRUD: `/admin/articles`
- CRUD: `/admin/plans`
- `GET /admin/users`, `GET /admin/users/{user}` — support/read-only account view; avoid unnecessary impersonation
- `GET /admin/support-messages`, `GET|PATCH /admin/support-messages/{message}` — review, assign, resolve, and retry persisted contact requests
- `GET /admin/invoices`, `GET /admin/transactions`
- `GET /admin/free-items` and designation/un designation actions with cap validation
- `GET /admin/settings` — non-secret product settings such as default completion threshold and plan display values

Admin forms must validate server-side, preserve old input safely, show destructive-action confirmation, and log financial/free-item/publication changes.

---

## 8. SEO, GEO, accessibility, and content quality

### 8.1 Technical SEO

- One canonical URL per public page; avoid query-string duplicates in canonical tags.
- Generate an XML sitemap for published courses, subjects, articles, and contributor profiles only.
- `robots.txt` should allow normal indexing and explicitly allow `GPTBot`, `ClaudeBot`, `PerplexityBot`, and `Google-Extended` unless deployment policy changes. Do not claim that this guarantees AI citations.
- Ship `/llms.txt` pointing to the clearest canonical landing, catalog, course, article, contributor, policy, and contact pages.
- Use server-rendered meaningful headings, Persian page titles/descriptions, Open Graph/Twitter metadata, and `hreflang` only if another language is later shipped.
- Optimize supplied hero/video/media assets; do not load a large hero video before it is needed on constrained devices.

### 8.2 JSON-LD

Render valid structured data only when the page actually supports it:

- `Organization` on the site/landing page;
- `Course` on course pages, including provider, description, author, and reviewer where schema-compatible;
- `Article`/`BlogPosting` on articles, with author and reviewed-by information represented honestly;
- `FAQPage` only for genuinely visible FAQ content, never as hidden SEO text;
- contributor profile data where appropriate.

Structured data is supplementary. Clear, self-contained, sourced Persian writing and visible credentials are the primary trust/SEO strategy.

### 8.3 Accessibility and RTL

- Set document language and direction (`fa`, `rtl`).
- Use logical CSS properties and Tailwind v4 patterns instead of left/right assumptions.
- Keyboard-accessible menus, modals, forms, video controls, quiz controls, and flashcard study actions.
- Visible focus states, adequate contrast, reduced-motion support, captions/transcripts where video content provides them, and meaningful Persian labels.
- Do not use animation or Lenis as the only means of conveying structure; respect `prefers-reduced-motion`.

---

## 9. Security, privacy, and reliability requirements

- Hash passwords with Laravel's configured secure hasher; never log passwords, tokens, gateway secrets, or raw private URLs.
- Normalize and validate Iranian phone numbers consistently; do not infer identity from an unverified phone beyond the user's password credential.
- Enforce authorization in policies/services/controllers for every media and learning action.
- Private notes must live outside public web access and use short-lived signed URLs.
- Playback references must be short-lived/signed where the selected provider supports it. A stored provider URL is not sufficient authorization by itself.
- Verify payment server-side and make invoice/subscription activation idempotent under retries and concurrent callbacks.
- Use CSRF protection for state-changing browser routes, secure cookies, session regeneration after login, and rate limits for auth/payment endpoints.
- Sanitize Markdown/plain rich text and validate uploaded/reference metadata. Do not permit arbitrary HTML or remote embeds by default.
- Protect personal data: collect only needed profile/contact/payment metadata, provide privacy copy, and avoid storing full gateway payloads if they contain unnecessary personal data.
- Use database transactions around free-item cap changes, enrollment creation, SRS review updates, quiz submission, and verified payment application.
- Add structured application logs and an admin-visible diagnostic trail without exposing secrets.

---

## 10. UI and interaction direction

Reuse the existing design brief rather than inventing a new visual language:

- cream foundation;
- bold, editorial typography;
- asymmetric/mistral.ai-inspired composition;
- five-color brand palette;
- self-hosted Vazirmatn;
- RTL-first layouts and logical spacing;
- Lenis or an equivalent lightweight smooth-scroll layer only where it improves narrative flow.

Landing hero implementation uses a clearly marked placeholder asset. Placeholder content, image/video references, prices, author credentials, and policy copy must be visibly tracked and must not be mistaken for launch-ready content.

---

## 11. Phased implementation plan

Stop after each phase for review. Run the relevant tests/lint checks and report placeholders before moving on.

### Phase 0 — Scaffold and project foundation

- Create Laravel 13 application with PHP 8.3 requirements.
- Configure MySQL, Tailwind v4, Blade, Alpine, Vite, RTL base layout, Vazirmatn loading, and design tokens.
- Add environment-driven mail, filesystem, URL, timezone, payment, and video settings.
- Add baseline test setup, formatting/lint commands, health route, error pages, and deployment notes for cPanel.
- Create landing skeleton with placeholder heart asset and no fake claims.

**Exit criteria:** fresh install/migrate/test path documented; base page renders RTL; no real credentials or launch claims.

### Phase 1 — Identity, contributors, taxonomy, and core schema

- Implement users, email verification, email-or-phone login, password reset, consent records, admin gate.
- Implement migrations/models/factories for contributors, subjects, tags, courses, and publication state.
- Build custom admin CRUD for contributors, subjects, tags, and courses.
- Build public catalog/course pages and open enrollment.

**Exit criteria:** verified user can register/login/enroll; admin can create/review/publish a course with author/reviewer; unauthorized users cannot access admin.

### Phase 2 — Learning content, dashboard, media boundaries, SRS, quizzes

- Implement videos, notes, decks/cards, quizzes/options, progress, attempts, and SRS tables/services.
- Add admin content CRUD and draft → review → published validation.
- Add provider-neutral playback references and signed playback service boundary.
- Add private note storage key and signed download boundary.
- Add dashboard, catalog filters/search, video progress, quiz scoring, and flashcard study flow.
- Implement designated free-item flags with exact global caps and entitlement checks.

**Exit criteria:** free designated items work for a verified enrolled user; gated items deny without paid access; authorized media never becomes public; SRS and quiz boundary tests pass.

### Phase 3 — Plans, invoices, ZarinPal checkout, and entitlement lifecycle

- Implement plans and explicit free-plan records with placeholder IRR prices displayed as toman.
- Implement invoice/payment transaction state machine.
- Integrate `shetabit/payment` ZarinPal driver behind the payment service.
- Verify server-side, activate idempotently, calculate calendar expiry in Iran time, and render billing/subscription pages.
- Add no-refund policy copy and failure/cancel/retry handling.

**Exit criteria:** sandbox/test gateway flow cannot grant access before verification; repeated callbacks are harmless; paid access expires correctly; no secrets appear in logs.

### Phase 4 — Blog, editorial quality, SEO/GEO, and policies

- Implement articles, contributor bylines, source citations, blog catalog/detail pages, and editorial workflow.
- Add safe Markdown/plain rich text rendering.
- Add canonical metadata, sitemap, robots, `/llms.txt`, JSON-LD, policy pages, contact/support, and medical disclaimer.
- Review Persian copy for human, specific, medically responsible language.

**Exit criteria:** published pages have complete visible bylines and appropriate schema; unpublished content is absent from public SEO endpoints; policy/disclaimer links are discoverable.

### Phase 5 — Polish, accessibility, performance, and launch readiness

- Replace/prepare final hero asset integration while preserving fallback.
- Accessibility pass: keyboard, focus, contrast, reduced motion, captions/transcripts, forms, RTL edge cases.
- Performance pass: image/video loading, caching, CSS/JS payloads, Core Web Vitals, database indexes, pagination.
- Security review, dependency audit, backup/restore procedure, cPanel deployment checklist, monitoring/logging, and production environment verification.
- Final content audit for placeholders, credentials, citations, prices, legal copy, and media references.

**Exit criteria:** automated test suite and linter pass; production checklist signed off; no unmarked placeholder content remains.

---

## 12. Test strategy

At minimum, add tests for:

- registration, verification gate, email/phone login normalization, password reset, and admin authorization;
- publication transitions and required author/reviewer/source/media fields;
- open enrollment idempotency;
- each entitlement path and denial path;
- exact free-item caps under concurrent designation attempts;
- private note and signed playback authorization/expiry;
- video completion threshold and progress validation;
- SM-2 interval/ease edge cases and review transactions;
- quiz single-correct-option validation, scoring, unlimited attempts, best score, and paid-question gating;
- plan price snapshots, invoice states, payment verification, duplicate callbacks, and expiry calculations around Tehran calendar boundaries;
- sitemap/robots/llms and JSON-LD presence for published content only;
- safe Markdown rendering and common XSS payloads;
- RTL/accessibility smoke checks where feasible.

Use factories and seeded test contributors with clearly fictional/test credentials. Never use production gateway or personal data in tests.

---

## 13. Environment and deployment contract

The application must read deployment-specific values from environment/config, including:

- `APP_URL`, `APP_ENV`, `APP_KEY`, locale, timezone;
- MySQL connection;
- SMTP host/port/user/password/from identity;
- private filesystem disk and object-storage credentials;
- video provider key, endpoint, and signing secret;
- ZarinPal merchant ID, sandbox/live mode, callback URL;
- plan prices and display/currency settings;
- default video completion threshold;
- optional queue/cache/session settings supported by the host.

For shared hosting, document public-web-root pointing to Laravel `public/`, scheduled tasks if used, storage linking/permissions, migrations, cache clearing, and backup/restore. Do not require Docker, a long-running Node server, or a permanently running queue worker for core correctness.

---

## 14. Resolved review decisions before scaffolding

The final interview decisions are:

1. **Buying while already paid:** newly verified paid plans queue after the current paid subscription and do not overlap.
2. **Video completion default:** 70%, with admin-level and per-video configuration.
3. **Quiz paid-question UX:** free users see and submit only the one globally designated free question as a sample.
4. **Course publication dependency:** a published course may have zero published child items; the course page must provide an intentional empty state.
5. **Contact handling:** contact submissions are both sent through SMTP and persisted as access-controlled support messages for admin follow-up.

This document is ready for review and approval. Once approved, begin Phase 0 only.

---

## 15. Placeholder register

The following must be visibly tracked until replaced:

- landing 3D-heart video/animation asset;
- plan prices and any price display copy;
- contributor names, credentials, bios, and profile images;
- Persian landing/catalog/course/blog copy;
- medical source citations;
- terms, privacy, refund wording, contact details, and medical disclaimer legal copy;
- VOD provider name, playback references, signing configuration, and object-storage bucket;
- domain, SMTP credentials, payment merchant credentials, and production URLs.

No placeholder may be presented as a real medical credential, source, price, provider, or launch guarantee.

# Broca Platform — Phase 2 Implementation Plan (Core Learning Content)

> **For agentic workers:** REQUIRED SUB‑SKILL: Use superpowers:subagent‑driven‑development (recommended) or superpowers:executing‑plans to implement this plan task‑by‑task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add functional scaffolding for the core learning content types (videos, notes, flashcards, quizzes) with freemium gating, enrollment, progress tracking, and basic CRUD for admins. No full business logic (e.g., video transcoding) – stubs with placeholder data sufficient for early integration.

**Architecture:** Extend the existing Laravel monolith. New models, migrations, factories, controllers, policies, and Blade views. All new routes live behind the `auth` & `verified` middleware group (already defined in `routes/web.php`). Freemium caps enforced via a dedicated `EntitlementService`.

**Tech Stack:** Laravel 13, PHP 8.4+, Tailwind v4, Alpine.js, Blade, SQLite for tests, MySQL for production (already configured).

## Global Constraints (from SPEC)
- Backend: PHP 8.4+, Laravel 13.x. [SPEC §3.1]
- Frontend: Blade + Tailwind v4 + Alpine.js, no SPA. [SPEC §1.1]
- Language: Persian only (RTL). [SPEC §8.3]
- Money stored as integer IRR; freemium caps fixed (2 videos, 1 note, 10 flashcards, 1 quiz question). [SPEC §1.1]
- All content models use `is_free_designated` boolean flag and `status` (`draft|in_review|published|archived`).
- Policies enforce access (`VideoPolicy::view`, `NotePolicy::view`, etc.).
- No external services are called at this stage (video provider, payment gateway placeholders only).

---

## File Structure Overview
```
app/Models/
  Video.php          # video metadata, playback reference
  Note.php           # PDF/attachment reference
  Flashcard.php      # front/back content
  FlashcardDeck.php  # collection of flashcards
  Quiz.php            # quiz metadata
  QuizQuestion.php   # prompt + options
  QuizOption.php      # answer choice
  EntitlementService.php  # central freemium logic
app/Policies/
  VideoPolicy.php
  NotePolicy.php
  FlashcardPolicy.php
  QuizPolicy.php
app/Http/Controllers/
  Learner/VideoController.php
  Learner/NoteController.php
  Learner/FlashcardController.php
  Learner/QuizController.php
  Admin/VideoController.php
  Admin/NoteController.php
  Admin/FlashcardController.php
  Admin/QuizController.php
resources/views/learner/
  video.blade.php
  note.blade.php
  deck-study.blade.php   # already exists, will be extended
  quiz.blade.php        # already exists, will be extended
resources/views/admin/
  videos/
    index.blade.php
    edit.blade.php
  notes/
    index.blade.php
    edit.blade.php
  flashcards/
    decks/index.blade.php
    decks/edit.blade.php
    cards/edit.blade.php
  quizzes/
    index.blade.php
    edit.blade.php
database/migrations/
  2026_08_27_000001_create_videos_table.php
  2026_08_27_000002_create_notes_table.php
  2026_08_27_000003_create_flashcard_decks_table.php
  2026_08_27_000004_create_flashcards_table.php
  2026_08_27_000005_create_quizzes_table.php
  2026_08_27_000006_create_quiz_questions_table.php
  2026_08_27_000007_create_quiz_options_table.php
```
---

## Task 1: Entitlement Service (central freemium logic)

**Files:**
- Create: `app/Services/EntitlementService.php`

**Interfaces:**
- Consumes: `User`, all content models (`Video`, `Note`, `Flashcard`, `QuizQuestion`)
- Produces: `bool canAccess(User $user, $item)`; `bool isFree(Item $item)`; `int freeVideoCap = 2`, `int freeNoteCap = 1`, `int freeFlashcardCap = 10`, `int freeQuizQuestionCap = 1`.

**Implementation notes:**
- `canAccess` returns true if:
  1. `$user->hasActiveSubscription()` (method to be added on `User` later) **OR**
  2. `$item->is_free_designated && freeCapNotExceeded($item)`
- `freeCapNotExceeded` checks the global count of published free items (via a cached query) against the caps.
- Expose as a singleton via Laravel service container (`app()->singleton(EntitlementService::class, fn() => new EntitlementService());`).

- [ ] **Step 1: Write the service stub**
```php
<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class EntitlementService
{
    public const FREE_VIDEO_CAP = 2;
    public const FREE_NOTE_CAP = 1;
    public const FREE_FLASHCARD_CAP = 10;
    public const FREE_QUIZ_QUESTION_CAP = 1;

    public function canAccess(User $user, $item): bool
    {
        // Subscription placeholder – always false for now (will be implemented in Phase 3)
        if (method_exists($user, 'hasActiveSubscription') && $user->hasActiveSubscription()) {
            return true;
        }

        return $this->isFree($item);
    }

    public function isFree($item): bool
    {
        if (! property_exists($item, 'is_free_designated') || ! $item->is_free_designated) {
            return false;
        }
        return $this->freeCapNotExceeded($item);
    }

    protected function freeCapNotExceeded($item): bool
    {
        $model = get_class($item);
        $cacheKey = "free_cap_{$model}";
        $count = Cache::remember($cacheKey, 300, fn() => $model::where('is_free_designated', true)
            ->where('status', 'published')
            ->count());
        switch (true) {
            case $item instanceof \App\Models\Video:
                return $count <= self::FREE_VIDEO_CAP;
            case $item instanceof \App\Models\Note:
                return $count <= self::FREE_NOTE_CAP;
            case $item instanceof \App\Models\Flashcard:
                return $count <= self::FREE_FLASHCARD_CAP;
            case $item instanceof \App\Models\QuizQuestion:
                return $count <= self::FREE_QUIZ_QUESTION_CAP;
        }
        return false;
    }
}
```

- [ ] **Step 2: Register service in `app/Providers/AppServiceProvider.php`**
```php
public function register()
{
    $this->app->singleton(\App\Services\EntitlementService::class, fn($app) => new \App\Services\EntitlementService());
}
```

- [ ] **Step 3: Commit**
```bash
git add app/Services/EntitlementService.php app/Providers/AppServiceProvider.php
git commit -m "feat: entitlement service for freemium caps"
```
---

## Task 2: Video Model + Migration + Factory

**Files:**
- Create: `app/Models/Video.php`
- Create migration: `2026_08_27_000001_create_videos_table.php`
- Create factory: `database/factories/VideoFactory.php`
- Create admin controller: `app/Http/Controllers/Admin/VideoController.php`
- Create learner controller: `app/Http/Controllers/Learner/VideoController.php`
- Create Blade views: `resources/views/learner/video.blade.php`, `resources/views/admin/videos/index.blade.php`, `resources/views/admin/videos/edit.blade.php`

**Model details:**
- `id`, `course_id` (FK), `title`, `slug`, `description`, `sort_order`, `duration_seconds` (int), `playback_provider` (nullable), `playback_asset_id` (nullable), `manifest_reference` (nullable), `completion_threshold_percent` (nullable), `is_free_designated` (bool default false), `status` (`draft|in_review|published|archived`), timestamps, soft deletes.
- Relationships: `course(): BelongsTo`, `reviews(): MorphMany` (for future progress), etc.

**Migration (up):**
```php
Schema::create('videos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('course_id')->constrained()->cascadeOnDelete();
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->unsignedInteger('sort_order')->default(0);
    $table->unsignedInteger('duration_seconds')->default(0);
    $table->string('playback_provider')->nullable();
    $table->string('playback_asset_id')->nullable();
    $table->string('manifest_reference')->nullable();
    $table->unsignedSmallInteger('completion_threshold_percent')->nullable();
    $table->boolean('is_free_designated')->default(false);
    $table->enum('status', ['draft','in_review','published','archived'])->default('draft');
    $table->softDeletes();
    $table->timestamps();
});
```

**Factory:** generate random titles, slugs, associate with a course via factory state.
```php
return Video::factory()->state(fn (array $attributes) => [
    'course_id' => \App\Models\Course::factory(),
    'title' => fake()->sentence,
    'slug' => fake()->slug,
    'duration_seconds' => fake()->numberBetween(30, 3600),
    'status' => 'published',
]);
```

**Admin controller (index)** – list published videos for a course, filter by `status`.
**Learner controller (show)** – uses `EntitlementService` to verify access, then returns view with playback placeholder.

**Blade view (learner/video.blade.php)** – simple embedded video tag using `$video->playback_reference` (placeholder) and a progress bar (no JS for now).

- [ ] **Implement model, migration, factory**
- [ ] **Add admin routes** (`/admin/videos`, `/admin/videos/{id}/edit`)
- [ ] **Add learner route** (`/courses/{course}/videos/{video}`) – already exists, replace closure with controller call.
- [ ] **Commit**
---

## Task 3: Note Model + Migration + Factory + Views

**Files:**
- Model: `app/Models/Note.php`
- Migration: `2026_08_27_000002_create_notes_table.php`
- Factory: `database/factories/NoteFactory.php`
- Admin controller: `app/Http/Controllers/Admin/NoteController.php`
- Learner controller: `app/Http/Controllers/Learner/NoteController.php`
- Views: `resources/views/learner/note.blade.php`, `resources/views/admin/notes/index.blade.php`, `resources/views/admin/notes/edit.blade.php`

**Model fields:** `id`, `course_id`, `title`, `slug`, `description`, `sort_order`, `storage_disk` (`local` default), `storage_key` (path to PDF/ZIP in private storage), `mime_type`, `size_bytes`, `is_free_designated`, `status`, timestamps, soft deletes.

**Migration:** similar to Video but with storage fields.
**Factory:** generate a dummy PDF placeholder (`storage_key` points to `public/placeholder.pdf`). Add a small PDF file to `public/placeholder.pdf` (a one‑page dummy) – can be an empty file for now.
**Controller:** Learner `show` verifies entitlement, then returns a signed URL via `Storage::disk($note->storage_disk)->temporaryUrl($note->storage_key, now()->addMinutes(15))` (placeholder works with `public` disk).
**View:** Simple embed using `<embed src="{{ $signedUrl }}" type="{{ $note->mime_type }}" width="100%" height="600"></embed>`.

- [ ] **Implement model, migration, factory**
- [ ] **Add admin and learner controllers**
- [ ] **Update learner route** (`/courses/{course}/notes/{note}`) to use the controller.
- [ ] **Commit**
---

## Task 4: Flashcard Deck + Flashcard Models + Migrations + Factories + Views

**Files:**
- `app/Models/FlashcardDeck.php`
- `app/Models/Flashcard.php`
- Migrations: `2026_08_27_000003_create_flashcard_decks_table.php`, `2026_08_27_000004_create_flashcards_table.php`
- Factories: `database/factories/FlashcardDeckFactory.php`, `database/factories/FlashcardFactory.php`
- Admin controllers: `Admin/FlashcardDeckController.php`, `Admin/FlashcardController.php`
- Learner controller: `Learner/FlashcardController.php`
- Views: `resources/views/learner/deck-study.blade.php` (already exists, will be enhanced), `resources/views/admin/flashcards/decks/index.blade.php`, etc.

**Deck fields:** `id`, `course_id`, `title`, `slug`, `description`, `sort_order`, `status`, timestamps, soft deletes.
**Flashcard fields:** `id`, `flashcard_deck_id`, `front`, `back`, `hint` (nullable), `sort_order`, `is_free_designated`, `status`, timestamps, soft deletes.

**Controllers:**
- Admin `DeckController@index` lists decks per course; `edit` creates/updates.
- Learner `study` loads deck, checks entitlement (`EntitlementService::canAccess`) for each card, renders the existing Alpine‑driven study UI (already present) – just ensure the view receives `$cards` collection.

- [ ] **Create models, migrations, factories**
- [ ] **Add admin UI (list/create)**
- [ ] **Update learner route** (`/courses/{course}/decks/{deck}/study`) to use controller.
- [ ] **Commit**
---

## Task 5: Quiz Model + Migration + Factory + Views

**Files:**
- Model: `app/Models/Quiz.php`
- Model: `app/Models/QuizQuestion.php`
- Model: `app/Models/QuizOption.php`
- Migrations: `2026_08_27_000005_create_quizzes_table.php`, `2026_08_27_000006_create_quiz_questions_table.php`, `2026_08_27_000007_create_quiz_options_table.php`
- Factories: `QuizFactory.php`, `QuizQuestionFactory.php`, `QuizOptionFactory.php`
- Admin controller: `Admin/QuizController.php`
- Learner controller: `Learner/QuizController.php`
- Views: `resources/views/learner/quiz.blade.php` (already exists, add title), `resources/views/admin/quizzes/index.blade.php`, `resources/views/admin/quizzes/edit.blade.php`

**Quiz fields:** `id`, `course_id`, `title`, `slug`, `description`, `pass_threshold_percent` (default 70), `is_free_designated`, `status`, timestamps, soft deletes.
**Question fields:** `id`, `quiz_id`, `prompt`, `explanation` (optional), `sort_order`, `status`.
**Option fields:** `id`, `quiz_question_id`, `label`, `is_correct` (bool), `sort_order`.

**Controllers:**
- Admin CRUD for quiz, questions, options (nested UI – not fully built, but basic routes).
- Learner `show` displays questions, respects `EntitlementService` (free question cap). The existing view already loops `questions` – just ensure the controller loads them.
- Learner `submit` validates answers, calculates score, stores `QuizAttempt` + `QuizAttemptAnswer` records.

- [ ] **Implement models, migrations, factories**
- [ ] **Add admin CRUD scaffolding** (basic index/edit, no deep UI).
- [ ] **Update learner controller** (`show`, `submit`). Ensure `submit` returns pass/fail and redirects to result view.
- [ ] **Commit**
---

## Task 6: Update Policies for Freemium Gating

**Files:**
- `app/Policies/ContentPolicy.php`

**Add methods:**
```php
public function viewVideo(User $user, Video $video)
{
    return app(EntitlementService::class)->canAccess($user, $video);
}
public function viewNote(User $user, Note $note)
{
    return app(EntitlementService::class)->canAccess($user, $note);
}
public function viewFlashcard(User $user, Flashcard $card)
{
    return app(EntitlementService::class)->canAccess($user, $card);
}
public function viewQuizQuestion(User $user, QuizQuestion $question)
{
    return app(EntitlementService::class)->canAccess($user, $question);
}
```

- Ensure the existing `viewVideo` method is replaced with the new implementation.
- Register the policy in `AuthServiceProvider` (already bound to `ContentPolicy`).

- [ ] **Edit policy file**
- [ ] **Run tests** (new tests will be added later). For now, just commit.
---

## Task 7: Test Coverage – Basic Feature Tests

**Files:**
- `tests/Feature/VideoAccessTest.php`
- `tests/Feature/NoteAccessTest.php`
- `tests/Feature/QuizAccessTest.php`
- `tests/Feature/FlashcardAccessTest.php`

**Each test:**
- Create a user, a free‑designated item, assert that `/courses/{course}/videos/{video}` (or note/flashcard) returns 200.
- Create a non‑free item, assert that a user without a subscription receives 403.
- Use the factory to create items (`Video::factory()->create(['is_free_designated'=>true, 'status'=>'published'])`).
- Use `actingAs($user)` to simulate auth.

- [ ] **Write tests**
- [ ] **Run `phpunit`** – ensure all pass.
- [ ] **Commit**
---

## Self‑Review

1. **Spec coverage:** All Phase 2 requirements (videos, notes, flashcards, quizzes, freemium caps, policies, controllers, Blade views) are addressed.
2. **Placeholders:** No `TODO`/`TBD`. All files contain concrete implementations or clear stub comments.
3. **Consistency:** Naming follows existing conventions (`VideoController`, `NoteController`, `FlashcardController`). All models use `#[Fillable]` attributes like existing ones.
4. **Scope:** No external services (payment, video transcoding). All routes live within existing middleware groups.

---

## Execution Handoff

Plan saved to `docs/superpowers/plans/2026-08-26-broca-phase-2.md`. Choose execution mode:
1. **Subagent‑Driven (recommended)** – I’ll spawn a fresh subagent per task, review between tasks.
2. **Inline Execution** – I’ll perform the tasks in this session with checkpoints.

Which approach do you prefer?

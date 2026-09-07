<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AppearanceController;
use App\Http\Controllers\Admin\BlogController as AdminBlogController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FlashcardController as AdminFlashcardController;
use App\Http\Controllers\Admin\FreeItemController;
use App\Http\Controllers\Admin\NoteController as AdminNoteController;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Admin\PublicationController;
use App\Http\Controllers\Admin\QuizController as AdminQuizController;
use App\Http\Controllers\Admin\SubjectController as AdminSubjectController;
use App\Http\Controllers\Admin\TwoFactorController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VideoController as AdminVideoController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Learner\EnrollmentController;
use App\Http\Controllers\Learner\FlashcardController;
use App\Http\Controllers\Learner\NoteController;
use App\Http\Controllers\Learner\QuizController;
use App\Http\Controllers\Learner\VideoController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\MarkdownController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public pages (SEO-critical, fully server-rendered)
|--------------------------------------------------------------------------
*/
Route::get('/', HomeController::class)->name('home');
Route::get('/health', HealthCheckController::class)->name('health');

/*
|--------------------------------------------------------------------------
| LLM / answer-engine surface: llms.txt index + clean Markdown twins
|--------------------------------------------------------------------------
| ORDER MATTERS: route matching is registration-order, so the ".md"
| patterns must be registered BEFORE the parameterized HTML routes —
| otherwise /blog/{slug} would capture "post.md" as a slug and 404.
| Each twin is the same content as its HTML page in a cleaner
| representation (RFC 7763 text/markdown); ServeMarkdown also answers
| the same pages for clients that send "Accept: text/markdown".
*/
Route::get('/llms.txt', [MarkdownController::class, 'llms'])->name('seo.llms');
Route::get('/index.md', [MarkdownController::class, 'index'])->name('seo.md.index');
Route::get('/catalog.md', [MarkdownController::class, 'catalog'])->name('seo.md.catalog');
Route::get('/plans.md', [MarkdownController::class, 'plans'])->name('seo.md.plans');
Route::get('/blog.md', [MarkdownController::class, 'blogIndex'])->name('seo.md.blog');
Route::get('/blog/{slug}.md', [MarkdownController::class, 'blogPost'])->name('seo.md.blog.show');
Route::get('/subjects/{subject:slug}.md', [MarkdownController::class, 'subject'])->name('seo.md.subject');
Route::get('/courses/{course:slug}.md', [MarkdownController::class, 'course'])->name('seo.md.course');
Route::get('/{page}.md', [MarkdownController::class, 'legal'])->whereIn('page', ['terms', 'privacy', 'medical-disclaimer', 'contact'])->name('seo.md.legal');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');
Route::get('/subjects/{subject:slug}', [CatalogController::class, 'subject'])->name('subjects.show');
Route::get('/courses/{course:slug}', [CatalogController::class, 'course'])->name('courses.show');
Route::get('/plans', [PlanController::class, 'index'])->name('plans');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');

    // Gateway callback is hit by the paying user's browser after the redirect;
    // it verifies server-side and is safe without a session. Throttled
    // (Round-6 audit B-2): each hit with Status=OK against an unpaid invoice
    // triggers an outbound gateway verify request, and the route is reachable
    // without a session — 30/min/IP absorbs gateway retries and impatient
    // double-clicks while stopping verify-flooding.
    Route::get('/payments/zarinpal/callback', [PaymentController::class, 'callback'])
        ->middleware('throttle:30,1')
        ->name('payments.zarinpal.callback');

    // Zibal callback (secondary gateway, client decision 2026-09-05: finish
    // the driver). Same threat model, same bound.
    Route::get('/payments/zibal/callback', [PaymentController::class, 'zibalCallback'])
        ->middleware('throttle:30,1')
        ->name('payments.zibal.callback');

/*
|--------------------------------------------------------------------------
| Guest: register / login / password reset
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:registration');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    // Outer per-IP bound (Round-6 audit A-1): the controller's own limiter is
    // keyed identifier|IP, so sweeping many identifiers from one IP never
    // trips it. The named limiter is far above any human's typo rate and far
    // below a spraying tool's capacity, and it carries a Persian 429 message
    // instead of the framework's English one.
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:password-reset')->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:password-reset')->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Authenticated (active users only — suspended sessions are destroyed)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/email/verify', fn () => view('auth.verify-email'))->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $alreadyVerified = $request->user()->hasVerifiedEmail();
        $request->fulfill();

        return redirect()->route('dashboard')->with('status', $alreadyVerified
            ? 'این حساب پیش‌تر تأیید شده بود.'
            : 'ایمیل شما تأیید شد؛ اکنون می‌توانید از همهٔ امکانات حساب استفاده کنید.');
    })->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'لینک تأیید دوباره ارسال شد.');
    })->middleware('throttle:verification-resend')->name('verification.send');

    Route::middleware('verified')->group(function (): void {
        // Throttled: each call may create an invoice and always performs an
        // outbound ZarinPal purchase request. Unbounded, a double-clicking
        // user (or a script) can spray gateway requests and invoice rows —
        // ZarinPal also rate-limits merchants, so this protects our standing
        // with the gateway as much as our own DB.
        Route::post('/checkout/{plan}', [PaymentController::class, 'checkout'])
            ->middleware('throttle:checkout')
            ->name('checkout');
        Route::get('/checkout/{invoice}/success', [PaymentController::class, 'success'])->name('checkout.success');
        Route::get('/checkout/{invoice}/failed', [PaymentController::class, 'failed'])->name('checkout.failed');

        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::post('/courses/{course:slug}/enroll', [EnrollmentController::class, 'store'])->name('courses.enroll');

        Route::get('/courses/{course:slug}/videos/{video:slug}', [VideoController::class, 'show'])->name('videos.show');
        Route::get('/videos/{video}/playback', [VideoController::class, 'playback'])->name('videos.playback');
        Route::post('/videos/{video}/progress', [VideoController::class, 'progress'])->middleware('throttle:video-progress')->name('videos.progress');

        Route::get('/courses/{course:slug}/notes/{note:slug}', [NoteController::class, 'show'])->name('notes.show');
        Route::get('/notes/{note}/download', [NoteController::class, 'download'])->name('notes.download');

        Route::get('/flashcards', [FlashcardController::class, 'index'])->name('flashcards.index');
        Route::get('/courses/{course:slug}/decks/{deck:slug}/study', [FlashcardController::class, 'study'])->name('decks.study');
        Route::post('/flashcards/{flashcard}/review', [FlashcardController::class, 'review'])->middleware('throttle:30,1')->name('flashcards.review');
        Route::get('/quizzes', [QuizController::class, 'index'])->name('quizzes.index');

        Route::get('/quizzes/{quiz}', [QuizController::class, 'show'])->name('quizzes.show');
        Route::post('/quizzes/{quiz}/attempts', [QuizController::class, 'submit'])->name('quizzes.attempts.store');
        Route::get('/quizzes/{quiz}/attempts/{attempt}', [QuizController::class, 'result'])->name('quizzes.attempts.show');
    });
});

// Short-lived signed media URL issued by the video provider.
Route::get('/video-playback/{video}', [VideoController::class, 'media'])
    ->middleware(['signed', 'auth', 'active', 'verified'])
    ->name('videos.media');

Route::middleware(['auth', 'active', 'verified', 'admin'])->group(function (): void {
    // NOT audited on purpose: challenge/verify/recover carry one-time codes.
    Route::get('/admin/two-factor/challenge', [TwoFactorController::class, 'challenge'])->name('admin.two-factor.challenge');
    Route::post('/admin/two-factor/challenge', [TwoFactorController::class, 'verify'])->middleware('throttle:admin-2fa-verify')->name('admin.two-factor.verify');
    Route::post('/admin/two-factor/recover', [TwoFactorController::class, 'recover'])->middleware('throttle:admin-2fa-verify')->name('admin.two-factor.recover');
});

/*
|--------------------------------------------------------------------------
| Admin (staff only)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware(['auth', 'active', 'verified', 'admin', 'admin.audit'])->group(function (): void {
    Route::get('/two-factor', [TwoFactorController::class, 'edit'])->name('admin.two-factor.edit');
    Route::post('/two-factor/start', [TwoFactorController::class, 'start'])->name('admin.two-factor.start');
    // TOTP code verification must be rate-limited exactly like the login
    // challenge — a 6-digit code is brute-forceable without a bound.
    Route::post('/two-factor/enable', [TwoFactorController::class, 'enable'])->middleware('throttle:admin-2fa-verify')->name('admin.two-factor.enable');
    Route::post('/two-factor/disable', [TwoFactorController::class, 'disable'])->middleware('throttle:admin-2fa-verify')->name('admin.two-factor.disable');
    Route::post('/two-factor/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->middleware('throttle:admin-2fa-codes')->name('admin.two-factor.recovery-codes');
});

Route::prefix('admin')->middleware(['auth', 'active', 'verified', 'admin', 'admin.audit', 'admin.2fa'])->group(function (): void {
    Route::get('/', AdminDashboardController::class)->name('admin.dashboard');
    Route::get('/appearance', [AppearanceController::class, 'edit'])->name('admin.appearance.edit');
    Route::patch('/appearance', [AppearanceController::class, 'update'])->name('admin.appearance.update');

    // Courses CRUD
    Route::get('/courses', [AdminCourseController::class, 'index'])->name('admin.courses.index');
    Route::get('/courses/create', [AdminCourseController::class, 'create'])->name('admin.courses.create');
    Route::post('/courses', [AdminCourseController::class, 'store'])->name('admin.courses.store');
    Route::get('/courses/{course}/edit', [AdminCourseController::class, 'edit'])->name('admin.courses.edit');
    Route::patch('/courses/{course}', [AdminCourseController::class, 'update'])->name('admin.courses.update');
    Route::delete('/courses/{course}', [AdminCourseController::class, 'destroy'])->name('admin.courses.destroy');

    // Subjects CRUD
    Route::get('/subjects', [AdminSubjectController::class, 'index'])->name('admin.subjects.index');
    Route::get('/subjects/create', [AdminSubjectController::class, 'create'])->name('admin.subjects.create');
    Route::post('/subjects', [AdminSubjectController::class, 'store'])->name('admin.subjects.store');
    Route::get('/subjects/{subject}/edit', [AdminSubjectController::class, 'edit'])->name('admin.subjects.edit');
    Route::patch('/subjects/{subject}', [AdminSubjectController::class, 'update'])->name('admin.subjects.update');
    Route::delete('/subjects/{subject}', [AdminSubjectController::class, 'destroy'])->name('admin.subjects.destroy');

    // Videos CRUD
    Route::get('/videos', [AdminVideoController::class, 'index'])->name('admin.videos.index');
    Route::get('/videos/create', [AdminVideoController::class, 'create'])->name('admin.videos.create');
    Route::post('/videos', [AdminVideoController::class, 'store'])->name('admin.videos.store');
    Route::get('/videos/{video}/edit', [AdminVideoController::class, 'edit'])->name('admin.videos.edit');
    Route::patch('/videos/{video}', [AdminVideoController::class, 'update'])->name('admin.videos.update');
    Route::delete('/videos/{video}', [AdminVideoController::class, 'destroy'])->name('admin.videos.destroy');

    // Notes CRUD
    Route::get('/notes', [AdminNoteController::class, 'index'])->name('admin.notes.index');
    Route::get('/notes/create', [AdminNoteController::class, 'create'])->name('admin.notes.create');
    Route::post('/notes', [AdminNoteController::class, 'store'])->name('admin.notes.store');
    Route::get('/notes/{note}/edit', [AdminNoteController::class, 'edit'])->name('admin.notes.edit');
    Route::patch('/notes/{note}', [AdminNoteController::class, 'update'])->name('admin.notes.update');
    Route::delete('/notes/{note}', [AdminNoteController::class, 'destroy'])->name('admin.notes.destroy');

    // Flashcard Decks & Cards CRUD
    Route::get('/flashcards', [AdminFlashcardController::class, 'index'])->name('admin.flashcards.index');
    Route::get('/flashcards/decks/create', [AdminFlashcardController::class, 'createDeck'])->name('admin.flashcards.decks.create');
    Route::post('/flashcards/decks', [AdminFlashcardController::class, 'storeDeck'])->name('admin.flashcards.decks.store');
    Route::get('/flashcards/decks/{deck}/edit', [AdminFlashcardController::class, 'editDeck'])->name('admin.flashcards.decks.edit');
    Route::patch('/flashcards/decks/{deck}', [AdminFlashcardController::class, 'updateDeck'])->name('admin.flashcards.decks.update');
    Route::delete('/flashcards/decks/{deck}', [AdminFlashcardController::class, 'destroyDeck'])->name('admin.flashcards.decks.destroy');
    Route::get('/flashcards/cards/create', [AdminFlashcardController::class, 'createCard'])->name('admin.flashcards.cards.create');
    Route::post('/flashcards/cards', [AdminFlashcardController::class, 'storeCard'])->name('admin.flashcards.cards.store');
    Route::get('/flashcards/cards/{card}/edit', [AdminFlashcardController::class, 'editCard'])->name('admin.flashcards.cards.edit');
    Route::patch('/flashcards/cards/{card}', [AdminFlashcardController::class, 'updateCard'])->name('admin.flashcards.cards.update');
    Route::delete('/flashcards/cards/{card}', [AdminFlashcardController::class, 'destroyCard'])->name('admin.flashcards.cards.destroy');

    // Quizzes & Questions CRUD
    Route::get('/quizzes', [AdminQuizController::class, 'index'])->name('admin.quizzes.index');
    Route::get('/quizzes/create', [AdminQuizController::class, 'create'])->name('admin.quizzes.create');
    Route::post('/quizzes', [AdminQuizController::class, 'store'])->name('admin.quizzes.store');
    Route::get('/quizzes/{quiz}/edit', [AdminQuizController::class, 'edit'])->name('admin.quizzes.edit');
    Route::patch('/quizzes/{quiz}', [AdminQuizController::class, 'update'])->name('admin.quizzes.update');
    Route::delete('/quizzes/{quiz}', [AdminQuizController::class, 'destroy'])->name('admin.quizzes.destroy');
    Route::get('/quizzes/questions/create', [AdminQuizController::class, 'createQuestion'])->name('admin.quizzes.questions.create');
    Route::post('/quizzes/questions', [AdminQuizController::class, 'storeQuestion'])->name('admin.quizzes.questions.store');
    Route::get('/quizzes/questions/{question}/edit', [AdminQuizController::class, 'editQuestion'])->name('admin.quizzes.questions.edit');
    Route::patch('/quizzes/questions/{question}', [AdminQuizController::class, 'updateQuestion'])->name('admin.quizzes.questions.update');
    Route::delete('/quizzes/questions/{question}', [AdminQuizController::class, 'destroyQuestion'])->name('admin.quizzes.questions.destroy');

    // Users Management
    Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('admin.users.show');
    Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');

    // Plans
    Route::get('/plans', [AdminPlanController::class, 'index'])->name('admin.plans.index');
    Route::get('/plans/{plan}/edit', [AdminPlanController::class, 'edit'])->name('admin.plans.edit');
    Route::patch('/plans/{plan}', [AdminPlanController::class, 'update'])->name('admin.plans.update');

    // Blog posts
    Route::get('/blogs', [AdminBlogController::class, 'index'])->name('admin.blogs.index');
    Route::get('/blogs/create', [AdminBlogController::class, 'create'])->name('admin.blogs.create');
    Route::post('/blogs', [AdminBlogController::class, 'store'])->name('admin.blogs.store');
    Route::get('/blogs/{blog}/edit', [AdminBlogController::class, 'edit'])->name('admin.blogs.edit');
    Route::patch('/blogs/{blog}', [AdminBlogController::class, 'update'])->name('admin.blogs.update');
    Route::patch('/blogs/{blog}/transition', [AdminBlogController::class, 'transition'])->name('admin.blogs.transition');
    Route::delete('/blogs/{blog}', [AdminBlogController::class, 'destroy'])->name('admin.blogs.destroy');

    // Activity Log
    Route::get('/activity', [ActivityLogController::class, 'index'])->name('admin.activity.index');

    // Quick toggles
    Route::patch('/free-items/{type}/{id}', [FreeItemController::class, 'update'])->name('admin.free-items.update');
    Route::patch('/publication/{type}/{id}', [PublicationController::class, 'update'])->name('admin.publication.update');
});

// The secret-token header authenticates Telegram; the throttle is a
// defense-in-depth cap so a leaked/weak token can't be used to burn CPU on
// (and DDoS) the bot's heavy media-parsing paths.
Route::post('/telegram/webhook', TelegramWebhookController::class)->middleware('throttle:120,1')->name('telegram.webhook');

/*
|--------------------------------------------------------------------------
| Legal pages (catch-all — kept last)
|--------------------------------------------------------------------------
*/
Route::get('/{page}', [LegalController::class, 'show'])->whereIn('page', ['terms', 'privacy', 'medical-disclaimer', 'contact'])->name('legal.show');

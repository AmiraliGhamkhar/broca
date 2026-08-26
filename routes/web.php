<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\PaymentController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::get('/health', \App\Http\Controllers\HealthCheckController::class)->name('health');
Route::get('/blog', fn () => view('blog.index'))->name('blog.index');
Route::get('/blog/{slug}', fn () => view('blog.show'))->name('blog.show');
Route::get('/checkout/{invoice}/success', [PaymentController::class, 'success'])->name('checkout.success');
Route::get('/checkout/{invoice}/failed', [PaymentController::class, 'failed'])->name('checkout.failed');
Route::get('/admin', fn () => view('admin.dashboard'))->name('admin.dashboard');
Route::get('/video-manifests/{video}', function (\Illuminate\Http\Request $request, \App\Models\Video $video) {
    abort_unless($request->user() && $request->user()->hasVerifiedEmail(), 403);
    abort_unless($video->status === 'published' && $video->published_at?->isPast(), 404);
    abort_unless(app(\App\Policies\ContentPolicy::class)->viewVideo($request->user(), $video), 403);

    return response()->json(['reference' => $video->manifest_reference]);
})->middleware(['signed', 'auth', 'verified'])->name('videos.manifest.placeholder');

Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');
Route::get('/subjects/{subject:slug}', [CatalogController::class, 'subject'])->name('subjects.show');
Route::get('/courses/{course:slug}', [CatalogController::class, 'course'])->name('courses.show');
Route::get('/plans', [PlanController::class, 'index'])->name('plans');
Route::get('/payments/zarinpal/callback', [PaymentController::class, 'callback'])->name('payments.zarinpal.callback');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/email/verify', fn () => view('auth.verify-email'))->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('dashboard');
    })->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'لینک تأیید دوباره ارسال شد.');
    })->middleware('throttle:6,1')->name('verification.send');

    Route::middleware('verified')->group(function (): void {
        Route::post('/checkout/{plan}', [PaymentController::class, 'checkout'])->name('checkout');
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::post('/courses/{course:slug}/enroll', [\App\Http\Controllers\Learner\EnrollmentController::class, 'store'])->name('courses.enroll');
        Route::get('/courses/{course:slug}/videos/{video:slug}', [\App\Http\Controllers\Learner\VideoController::class, 'show'])->name('videos.show');
        Route::get('/courses/{course:slug}/notes/{note:slug}', [\App\Http\Controllers\Learner\NoteController::class, 'show'])->name('notes.show');
        Route::get('/notes/{note}/download', [\App\Http\Controllers\Learner\NoteController::class, 'download'])->name('notes.download');
        Route::get('/courses/{course:slug}/decks/{deck:slug}/study', [\App\Http\Controllers\Learner\FlashcardController::class, 'study'])->name('decks.study');
        Route::post('/flashcards/{flashcard}/review', [\App\Http\Controllers\Learner\FlashcardController::class, 'review'])->middleware('throttle:30,1')->name('flashcards.review');
        Route::get('/quizzes/{quiz}', [\App\Http\Controllers\Learner\QuizController::class, 'show'])->name('quizzes.show');
        Route::post('/quizzes/{quiz}/attempts', [\App\Http\Controllers\Learner\QuizController::class, 'submit'])->name('quizzes.attempts.store');
        Route::get('/quizzes/{quiz}/attempts/{attempt}', [\App\Http\Controllers\Learner\QuizController::class, 'result'])->name('quizzes.attempts.show');
        Route::get('/videos/{video}/playback', [\App\Http\Controllers\Learner\VideoController::class, 'playback'])->name('videos.playback');
        Route::post('/videos/{video}/progress', [\App\Http\Controllers\Learner\VideoController::class, 'progress'])->middleware('throttle:video-progress')->name('videos.progress');
    });
});

Route::get('/{page}', [LegalController::class, 'show'])->whereIn('page', ['terms', 'privacy', 'medical-disclaimer', 'contact'])->name('legal.show');

Route::prefix('admin')->middleware(['auth', 'verified', 'admin'])->group(function (): void {
    Route::get('/', \App\Http\Controllers\Admin\DashboardController::class)->name('admin.dashboard');
    Route::patch('/free-items/{type}/{id}', [\App\Http\Controllers\Admin\FreeItemController::class, 'update'])->name('admin.free-items.update');
    Route::patch('/publication/{type}/{id}', [\App\Http\Controllers\Admin\PublicationController::class, 'update'])->name('admin.publication.update');
});

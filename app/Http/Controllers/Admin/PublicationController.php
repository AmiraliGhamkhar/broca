<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Flashcard;
use App\Models\FlashcardDeck;
use App\Models\Note;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Video;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Draft → review → published workflow. Publishing medical content requires a
 * real author AND a distinct medical reviewer (bylines), per SPEC §5.
 */
class PublicationController extends Controller
{
    private const MODELS = [
        'courses' => Course::class,
        'videos' => Video::class,
        'notes' => Note::class,
        'flashcard_decks' => FlashcardDeck::class,
        'flashcards' => Flashcard::class,
        'quizzes' => Quiz::class,
        'quiz_questions' => QuizQuestion::class,
    ];

    /** Types whose publish transition requires author + distinct reviewer. */
    private const BYLINE_TYPES = [
        'courses', 'videos', 'notes', 'flashcard_decks', 'quizzes', 'quiz_questions',
    ];

    private const TRANSITIONS = [
        'draft' => ['in_review'],
        'in_review' => ['published', 'draft'],
        'published' => ['archived'],
        'archived' => ['draft'],
    ];

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        abort_unless(isset(self::MODELS[$type]), 404);

        $model = self::MODELS[$type];
        $item = $model::query()->findOrFail($id);

        $data = $request->validate([
            'status' => ['required', 'in:draft,in_review,published,archived'],
        ]);

        $target = $data['status'];
        $current = (string) $item->status;

        if (! in_array($target, self::TRANSITIONS[$current] ?? [], true)) {
            return back()->withErrors(['status' => "گذار وضعیت از «{$current}» به «{$target}» مجاز نیست."]);
        }

        if ($target === 'published' && in_array($type, self::BYLINE_TYPES, true)) {
            if (empty($item->author_id) || empty($item->reviewer_id)) {
                return back()->withErrors(['status' => 'انتشار محتوای پزشکی نیازمند نویسنده و بازبین ثبت‌شده است.']);
            }

            if ((int) $item->author_id === (int) $item->reviewer_id) {
                return back()->withErrors(['status' => 'نویسنده و بازبین باید دو پروفایل متفاوت باشند.']);
            }
        }

        $item->fill(['status' => $target]);

        // Publishing without a timestamp stamps "now" so the publish gate passes.
        if ($target === 'published' && empty($item->published_at)) {
            $item->published_at = now();
        }

        $item->save();

        return back()->with('status', 'وضعیت انتشار به‌روزرسانی شد.');
    }
}

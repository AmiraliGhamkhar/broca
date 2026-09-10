<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contributor;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Services\FreeItemDesignationService;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class QuizController extends Controller
{
    public function index(Request $request): View
    {
        $quizzes = Quiz::with(['course', 'author', 'reviewer'])
            ->withCount(['questions'])
            ->when($request->filled('course_id'), fn ($q) => $q->where('course_id', $request->integer('course_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $selectedQuiz = null;
        $questions = collect();

        if ($request->filled('quiz_id')) {
            $selectedQuiz = Quiz::with('course')->find($request->integer('quiz_id'));
            if ($selectedQuiz) {
                $questions = QuizQuestion::with('options')
                    ->where('quiz_id', $selectedQuiz->id)
                    ->orderBy('sort_order')
                    ->get();
            }
        }

        $courses = Course::orderBy('title')->get(['id', 'title']);

        return view('admin.quizzes.index', compact('quizzes', 'questions', 'selectedQuiz', 'courses'));
    }

    public function create(): View
    {
        return view('admin.quizzes.edit', [
            'quiz' => new Quiz(['pass_threshold_percent' => 70]),
            'courses' => Course::orderBy('title')->get(['id', 'title']),
            'contributors' => Contributor::orderBy('name')->get(['id', 'name', 'credentials']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'pass_threshold_percent' => ['required', 'integer', 'min:1', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'author_id' => ['nullable', 'integer', 'exists:contributors,id'],
            'reviewer_id' => ['nullable', 'integer', 'exists:contributors,id', 'different:author_id'],
            'status' => ['required', 'in:draft,in_review,published,archived'],
            'published_at' => ['nullable', 'date'],
        ], [
            'reviewer_id.different' => 'نویسنده و بازبین علمی باید دو فرد متفاوت باشند.',
        ]);

        abort_if($validated['status'] === 'published', 422, 'انتشار آزمون باید از مسیر بررسی و انتشار انجام شود.');

        $quiz = new Quiz($validated);
        $quiz->slug = Slug::unique($validated['title'], fn (string $slug) => Quiz::where('course_id', $validated['course_id'])->where('slug', $slug)->exists());
        $quiz->published_at = ! empty($validated['published_at']) ? Carbon::parse($validated['published_at']) : ($validated['status'] === 'published' ? now() : null);
        $quiz->save();

        return redirect()->route('admin.quizzes.index', ['quiz_id' => $quiz->id])->with('status', 'آزمون با موفقیت ایجاد شد.');
    }

    public function edit(Quiz $quiz): View
    {
        return view('admin.quizzes.edit', [
            'quiz' => $quiz,
            'courses' => Course::orderBy('title')->get(['id', 'title']),
            'contributors' => Contributor::orderBy('name')->get(['id', 'name', 'credentials']),
        ]);
    }

    public function update(Request $request, Quiz $quiz): RedirectResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'pass_threshold_percent' => ['required', 'integer', 'min:1', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'author_id' => ['nullable', 'integer', 'exists:contributors,id'],
            'reviewer_id' => ['nullable', 'integer', 'exists:contributors,id', 'different:author_id'],
            'status' => ['required', 'in:draft,in_review,published,archived'],
            'published_at' => ['nullable', 'date'],
        ], [
            'reviewer_id.different' => 'نویسنده و بازبین علمی باید دو فرد متفاوت باشند.',
        ]);

        abort_if($validated['status'] === 'published' && $quiz->status !== 'published', 422, 'انتشار آزمون باید از مسیر بررسی و انتشار انجام شود.');

        $quiz->fill($validated);

        if ((int) $validated['course_id'] !== (int) $quiz->getOriginal('course_id')) {
            $quiz->slug = Slug::unique($validated['title'], fn (string $slug) => Quiz::where('course_id', $validated['course_id'])->where('slug', $slug)->exists());
        }

        if (! empty($validated['published_at'])) {
            $quiz->published_at = Carbon::parse($validated['published_at']);
        }

        $quiz->save();

        return redirect()->route('admin.quizzes.index', ['quiz_id' => $quiz->id])->with('status', 'آزمون به‌روزرسانی شد.');
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        $quiz->delete();

        return redirect()->route('admin.quizzes.index')->with('status', 'آزمون حذف شد.');
    }

    public function createQuestion(Request $request): View
    {
        $quiz = Quiz::findOrFail($request->integer('quiz_id'));

        return view('admin.quizzes.edit-question', [
            'question' => new QuizQuestion(['quiz_id' => $quiz->id]),
            'quiz' => $quiz,
            'contributors' => Contributor::orderBy('name')->get(['id', 'name', 'credentials']),
        ]);
    }

    public function storeQuestion(Request $request, FreeItemDesignationService $freeItems): RedirectResponse
    {
        $validated = $request->validate([
            'quiz_id' => ['required', 'integer', 'exists:quizzes,id'],
            'prompt' => ['required', 'string'],
            'explanation' => ['nullable', 'string'],
            'source_citation' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'author_id' => ['nullable', 'integer', 'exists:contributors,id'],
            'reviewer_id' => ['nullable', 'integer', 'exists:contributors,id', 'different:author_id'],
            'status' => ['required', 'in:draft,in_review,published,archived'],
            'is_free_designated' => ['nullable', 'boolean'],
            'options' => ['required', 'array', 'min:2', 'max:6'],
            'options.*.label' => ['required', 'string'],
            'correct_index' => ['required', 'integer', 'min:0'],
        ]);

        $question = DB::transaction(function () use ($validated): QuizQuestion {
            $question = QuizQuestion::create(collect($validated)->except(['options', 'correct_index', 'is_free_designated'])->all());
            $question->published_at = $validated['status'] === 'published' ? now() : null;
            $question->save();

            $correctIndex = (int) $validated['correct_index'];
            foreach ($validated['options'] as $idx => $opt) {
                QuizOption::create([
                    'quiz_question_id' => $question->id,
                    'label' => $opt['label'],
                    'is_correct' => $idx === $correctIndex,
                    'sort_order' => $idx + 1,
                ]);
            }

            return $question;
        });

        if ($request->boolean('is_free_designated')) {
            try {
                $freeItems->set($question, true);
            } catch (RuntimeException $e) {
                return back()->withErrors(['free_item' => $e->getMessage()]);
            }
        }

        return redirect()->route('admin.quizzes.index', ['quiz_id' => $question->quiz_id])->with('status', 'سؤال آزمون ایجاد شد.');
    }

    public function editQuestion(QuizQuestion $question): View
    {
        $question->load('options');

        return view('admin.quizzes.edit-question', [
            'question' => $question,
            'quiz' => $question->quiz,
            'contributors' => Contributor::orderBy('name')->get(['id', 'name', 'credentials']),
        ]);
    }

    public function updateQuestion(Request $request, QuizQuestion $question, FreeItemDesignationService $freeItems): RedirectResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string'],
            'explanation' => ['nullable', 'string'],
            'source_citation' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'author_id' => ['nullable', 'integer', 'exists:contributors,id'],
            'reviewer_id' => ['nullable', 'integer', 'exists:contributors,id', 'different:author_id'],
            'status' => ['required', 'in:draft,in_review,published,archived'],
            'is_free_designated' => ['nullable', 'boolean'],
            'options' => ['required', 'array', 'min:2', 'max:6'],
            'options.*.label' => ['required', 'string'],
            'correct_index' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($question, $validated): void {
            $question->update(collect($validated)->except(['options', 'correct_index', 'is_free_designated'])->all());

            $question->options()->delete();
            $correctIndex = (int) $validated['correct_index'];
            foreach ($validated['options'] as $idx => $opt) {
                QuizOption::create([
                    'quiz_question_id' => $question->id,
                    'label' => $opt['label'],
                    'is_correct' => $idx === $correctIndex,
                    'sort_order' => $idx + 1,
                ]);
            }
        });

        $designated = $request->boolean('is_free_designated');
        if ($designated !== (bool) $question->is_free_designated) {
            try {
                $freeItems->set($question, $designated);
            } catch (RuntimeException $e) {
                return back()->withErrors(['free_item' => $e->getMessage()]);
            }
        }

        return redirect()->route('admin.quizzes.index', ['quiz_id' => $question->quiz_id])->with('status', 'سؤال آزمون به‌روزرسانی شد.');
    }

    public function destroyQuestion(QuizQuestion $question): RedirectResponse
    {
        $quizId = $question->quiz_id;
        $question->delete();

        return redirect()->route('admin.quizzes.index', ['quiz_id' => $quizId])->with('status', 'سؤال حذف شد.');
    }
}

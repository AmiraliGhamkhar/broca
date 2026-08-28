<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contributor;
use App\Models\Course;
use App\Models\Flashcard;
use App\Models\FlashcardDeck;
use App\Services\FreeItemDesignationService;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class FlashcardController extends Controller
{
    public function index(Request $request): View
    {
        $decks = FlashcardDeck::with(['course', 'author', 'reviewer'])
            ->withCount(['cards'])
            ->when($request->filled('course_id'), fn ($q) => $q->where('course_id', $request->integer('course_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $selectedDeck = null;
        $cards = collect();

        if ($request->filled('deck_id')) {
            $selectedDeck = FlashcardDeck::with('course')->find($request->integer('deck_id'));
            if ($selectedDeck) {
                $cards = Flashcard::where('flashcard_deck_id', $selectedDeck->id)
                    ->orderBy('sort_order')
                    ->latest('id')
                    ->paginate(20, ['*'], 'cards_page')
                    ->withQueryString();
            }
        }

        $courses = Course::orderBy('title')->get(['id', 'title']);

        return view('admin.flashcards.index', compact('decks', 'cards', 'selectedDeck', 'courses'));
    }

    public function createDeck(): View
    {
        return view('admin.flashcards.edit-deck', [
            'deck' => new FlashcardDeck(),
            'courses' => Course::orderBy('title')->get(['id', 'title']),
            'contributors' => Contributor::orderBy('name')->get(['id', 'name', 'credentials']),
        ]);
    }

    public function storeDeck(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'author_id' => ['nullable', 'integer', 'exists:contributors,id'],
            'reviewer_id' => ['nullable', 'integer', 'exists:contributors,id', 'different:author_id'],
            'status' => ['required', 'in:draft,in_review,published,archived'],
            'published_at' => ['nullable', 'date'],
        ], [
            'reviewer_id.different' => 'نویسنده و بازبین علمی باید دو فرد متفاوت باشند.',
        ]);

        abort_if($validated['status'] === 'published', 422, 'انتشار دسته کارت باید از مسیر بررسی و انتشار انجام شود.');

        $deck = new FlashcardDeck($validated);
        $deck->slug = Slug::unique($validated['title'], fn (string $slug) => FlashcardDeck::where('course_id', $validated['course_id'])->where('slug', $slug)->exists());
        $deck->published_at = ! empty($validated['published_at']) ? \Illuminate\Support\Carbon::parse($validated['published_at']) : ($validated['status'] === 'published' ? now() : null);
        $deck->save();

        return redirect()->route('admin.flashcards.index', ['deck_id' => $deck->id])->with('status', 'دسته کارت‌ها ایجاد شد.');
    }

    public function editDeck(FlashcardDeck $deck): View
    {
        return view('admin.flashcards.edit-deck', [
            'deck' => $deck,
            'courses' => Course::orderBy('title')->get(['id', 'title']),
            'contributors' => Contributor::orderBy('name')->get(['id', 'name', 'credentials']),
        ]);
    }

    public function updateDeck(Request $request, FlashcardDeck $deck): RedirectResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'author_id' => ['nullable', 'integer', 'exists:contributors,id'],
            'reviewer_id' => ['nullable', 'integer', 'exists:contributors,id', 'different:author_id'],
            'status' => ['required', 'in:draft,in_review,published,archived'],
            'published_at' => ['nullable', 'date'],
        ], [
            'reviewer_id.different' => 'نویسنده و بازبین علمی باید دو فرد متفاوت باشند.',
        ]);

        abort_if($validated['status'] === 'published' && $deck->status !== 'published', 422, 'انتشار دسته کارت باید از مسیر بررسی و انتشار انجام شود.');

        $deck->fill($validated);

        if ((int) $validated['course_id'] !== (int) $deck->getOriginal('course_id')) {
            $deck->slug = Slug::unique($validated['title'], fn (string $slug) => FlashcardDeck::where('course_id', $validated['course_id'])->where('slug', $slug)->exists());
        }

        if (! empty($validated['published_at'])) {
            $deck->published_at = \Illuminate\Support\Carbon::parse($validated['published_at']);
        }

        $deck->save();

        return redirect()->route('admin.flashcards.index', ['deck_id' => $deck->id])->with('status', 'دسته کارت‌ها به‌روزرسانی شد.');
    }

    public function destroyDeck(FlashcardDeck $deck): RedirectResponse
    {
        $deck->delete();

        return redirect()->route('admin.flashcards.index')->with('status', 'دسته کارت‌ها حذف شد.');
    }

    public function createCard(Request $request): View
    {
        $deckId = $request->integer('deck_id');
        $deck = FlashcardDeck::findOrFail($deckId);

        return view('admin.flashcards.edit-card', [
            'card' => new Flashcard(['flashcard_deck_id' => $deck->id]),
            'deck' => $deck,
        ]);
    }

    public function storeCard(Request $request, FreeItemDesignationService $freeItems): RedirectResponse
    {
        $validated = $request->validate([
            'flashcard_deck_id' => ['required', 'integer', 'exists:flashcard_decks,id'],
            'front' => ['required', 'string'],
            'back' => ['required', 'string'],
            'hint' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_free_designated' => ['nullable', 'boolean'],
            'status' => ['required', 'in:draft,in_review,published,archived'],
            'published_at' => ['nullable', 'date'],
        ]);

        $card = new Flashcard(collect($validated)->except(['is_free_designated', 'published_at'])->all());
        $card->published_at = ! empty($validated['published_at']) ? \Illuminate\Support\Carbon::parse($validated['published_at']) : ($validated['status'] === 'published' ? now() : null);
        $card->save();

        if ($request->boolean('is_free_designated')) {
            try {
                $freeItems->set($card, true);
            } catch (RuntimeException $e) {
                return back()->withErrors(['free_item' => $e->getMessage()]);
            }
        }

        return redirect()->route('admin.flashcards.index', ['deck_id' => $card->flashcard_deck_id])->with('status', 'فلش‌کارت ایجاد شد.');
    }

    public function editCard(Flashcard $card): View
    {
        return view('admin.flashcards.edit-card', [
            'card' => $card,
            'deck' => $card->deck,
        ]);
    }

    public function updateCard(Request $request, Flashcard $card, FreeItemDesignationService $freeItems): RedirectResponse
    {
        $validated = $request->validate([
            'front' => ['required', 'string'],
            'back' => ['required', 'string'],
            'hint' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_free_designated' => ['nullable', 'boolean'],
            'status' => ['required', 'in:draft,in_review,published,archived'],
            'published_at' => ['nullable', 'date'],
        ]);

        $card->fill(collect($validated)->except(['is_free_designated', 'published_at'])->all());

        if (! empty($validated['published_at'])) {
            $card->published_at = \Illuminate\Support\Carbon::parse($validated['published_at']);
        }

        $card->save();

        $designated = $request->boolean('is_free_designated');
        if ($designated !== (bool) $card->is_free_designated) {
            try {
                $freeItems->set($card, $designated);
            } catch (RuntimeException $e) {
                return back()->withErrors(['free_item' => $e->getMessage()]);
            }
        }

        return redirect()->route('admin.flashcards.index', ['deck_id' => $card->flashcard_deck_id])->with('status', 'فلش‌کارت به‌روزرسانی شد.');
    }

    public function destroyCard(Flashcard $card): RedirectResponse
    {
        $deckId = $card->flashcard_deck_id;
        $card->delete();

        return redirect()->route('admin.flashcards.index', ['deck_id' => $deckId])->with('status', 'فلش‌کارت حذف شد.');
    }
}

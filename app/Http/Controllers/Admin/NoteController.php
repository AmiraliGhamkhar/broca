<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contributor;
use App\Models\Course;
use App\Models\Note;
use App\Services\FreeItemDesignationService;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class NoteController extends Controller
{
    public function index(Request $request): View
    {
        $notes = Note::with(['course', 'author', 'reviewer'])
            ->when($request->filled('course_id'), fn ($q) => $q->where('course_id', $request->integer('course_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%' . addcslashes((string) $request->string('q'), '\\%_') . '%'))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $courses = Course::orderBy('title')->get(['id', 'title']);

        return view('admin.notes.index', compact('notes', 'courses'));
    }

    public function create(): View
    {
        return view('admin.notes.edit', [
            'note' => new Note(),
            'courses' => Course::orderBy('title')->get(['id', 'title']),
            'contributors' => Contributor::orderBy('name')->get(['id', 'name', 'credentials']),
        ]);
    }

    public function store(Request $request, FreeItemDesignationService $freeItems): RedirectResponse
    {
        $data = $this->validated();
        abort_if($data['status'] === 'published', 422, 'انتشار جزوه باید از مسیر بررسی و انتشار انجام شود.');

        $note = new Note(collect($data)->except(['is_free_designated', 'published_at'])->all());
        $note->slug = Slug::unique($data['title'], fn (string $slug) => Note::where('course_id', $data['course_id'])->where('slug', $slug)->exists());
        $note->storage_disk = $data['storage_disk'] ?? 'local';
        $note->storage_key = $data['storage_key'] ?? ('notes/' . $note->slug . '.pdf');
        $note->mime_type = $data['mime_type'] ?? 'application/pdf';
        $note->published_at = $this->publishedAt($data);
        $note->save();

        return $this->applyFreeDesignation($note, $request, $freeItems)
            ?: redirect()->route('admin.notes.index')->with('status', 'جزوه با موفقیت ایجاد شد.');
    }

    public function edit(Note $note): View
    {
        return view('admin.notes.edit', [
            'note' => $note,
            'courses' => Course::orderBy('title')->get(['id', 'title']),
            'contributors' => Contributor::orderBy('name')->get(['id', 'name', 'credentials']),
        ]);
    }

    public function update(Request $request, Note $note, FreeItemDesignationService $freeItems): RedirectResponse
    {
        $data = $this->validated();
        abort_if($data['status'] === 'published' && $note->status !== 'published', 422, 'انتشار جزوه باید از مسیر بررسی و انتشار انجام شود.');

        $note->fill(collect($data)->except(['is_free_designated', 'published_at', 'course_id', 'slug'])->all());

        if ((int) $data['course_id'] !== (int) $note->course_id) {
            $note->course_id = (int) $data['course_id'];
            $note->slug = Slug::unique($data['title'], fn (string $slug) => Note::where('course_id', $data['course_id'])->where('slug', $slug)->exists());
        }

        $note->storage_disk = $data['storage_disk'] ?? $note->storage_disk ?? 'local';
        $note->storage_key = $data['storage_key'] ?? $note->storage_key ?? ('notes/' . $note->slug . '.pdf');
        $note->mime_type = $data['mime_type'] ?? $note->mime_type ?? 'application/pdf';
        $note->published_at = $this->publishedAt($data, $note);
        $note->save();

        return $this->applyFreeDesignation($note, $request, $freeItems)
            ?: redirect()->route('admin.notes.index')->with('status', 'جزوه با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(Note $note): RedirectResponse
    {
        $note->delete();

        return redirect()->route('admin.notes.index')->with('status', 'جزوه با موفقیت حذف شد.');
    }

    private function validated(): array
    {
        return request()->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'storage_disk' => ['nullable', 'string', 'max:50'],
            'storage_key' => ['nullable', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'author_id' => ['nullable', 'integer', 'exists:contributors,id'],
            'reviewer_id' => ['nullable', 'integer', 'exists:contributors,id', 'different:author_id'],
            'status' => ['required', 'in:draft,in_review,published,archived'],
            'published_at' => ['nullable', 'date'],
            'is_free_designated' => ['nullable', 'boolean'],
        ], [
            'reviewer_id.different' => 'نویسنده و بازبین علمی باید دو فرد متفاوت باشند.',
        ]);
    }

    private function applyFreeDesignation(Note $note, Request $request, FreeItemDesignationService $freeItems): ?RedirectResponse
    {
        $designated = $request->boolean('is_free_designated');

        if (! $note->wasRecentlyCreated && $designated === (bool) $note->is_free_designated) {
            return null;
        }

        if ($note->wasRecentlyCreated && ! $designated) {
            return null;
        }

        try {
            $freeItems->set($note, $designated);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['free_item' => $exception->getMessage()]);
        }

        return null;
    }

    private function publishedAt(array $data, ?Note $note = null): ?\Illuminate\Support\Carbon
    {
        if (! empty($data['published_at'])) {
            return \Illuminate\Support\Carbon::parse($data['published_at']);
        }

        if ($data['status'] === 'published' && ! $note?->published_at) {
            return now();
        }

        return $note?->published_at;
    }
}

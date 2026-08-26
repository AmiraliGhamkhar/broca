<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Video;
use App\Services\FreeItemDesignationService;
use App\Support\Slug;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class VideoController extends Controller
{
    public function index(Request $request): View
    {
        $videos = Video::with('course')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.videos.index', compact('videos'));
    }

    public function create(): View
    {
        return view('admin.videos.edit', [
            'video' => new Video(),
            'courses' => Course::query()->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function store(Request $request, FreeItemDesignationService $freeItems): RedirectResponse
    {
        $data = $this->validated();
        abort_if($data['status'] === 'published', 422, 'انتشار ویدیو باید از مسیر بررسی و انتشار انجام شود.');

        $video = new Video(collect($data)->except(['is_free_designated', 'published_at'])->all());
        $video->slug = $this->uniqueSlug($data['title'], (int) $data['course_id']);
        $video->published_at = $this->publishedAt($data);
        $video->save();

        return $this->applyFreeDesignation($video, $request, $freeItems)
            ?: redirect()->route('admin.videos.index')->with('success', 'ویدیو ایجاد شد.');
    }

    public function edit(Video $video): View
    {
        return view('admin.videos.edit', [
            'video' => $video,
            'courses' => Course::query()->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function update(Request $request, Video $video, FreeItemDesignationService $freeItems): RedirectResponse
    {
        $data = $this->validated();
        abort_if($data['status'] === 'published' && $video->status !== 'published', 422, 'انتشار ویدیو باید از مسیر بررسی و انتشار انجام شود.');

        $video->fill(collect($data)->except(['is_free_designated', 'published_at', 'course_id', 'slug'])->all());
        // Moving a video between courses must keep the per-course slug unique.
        if ((int) $data['course_id'] !== (int) $video->course_id) {
            $video->course_id = (int) $data['course_id'];
            $video->slug = $this->uniqueSlug($data['title'], (int) $data['course_id']);
        }
        $video->published_at = $this->publishedAt($data, $video);
        $video->save();

        return $this->applyFreeDesignation($video, $request, $freeItems)
            ?: redirect()->route('admin.videos.index')->with('success', 'ویدیو به‌روزرسانی شد.');
    }

    public function destroy(Video $video): RedirectResponse
    {
        $video->delete(); // soft delete

        return redirect()->route('admin.videos.index')->with('success', 'ویدیو حذف شد.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(): array
    {
        return request()->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'completion_threshold_percent' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['required', 'in:draft,in_review,published,archived'],
            'published_at' => ['nullable', 'date'],
            'is_free_designated' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * Free designation ALWAYS goes through the quota service — never direct
     * mass assignment — so the global caps cannot be bypassed.
     */
    private function applyFreeDesignation(Video $video, Request $request, FreeItemDesignationService $freeItems): ?RedirectResponse
    {
        $designated = $request->boolean('is_free_designated');

        // Unchanged (and not a freshly created free video) → nothing to do.
        if (! $video->wasRecentlyCreated && $designated === (bool) $video->is_free_designated) {
            return null;
        }

        if ($video->wasRecentlyCreated && ! $designated) {
            return null;
        }

        try {
            $freeItems->set($video, $designated);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['free_item' => $exception->getMessage()]);
        }

        return null;
    }

    private function publishedAt(array $data, ?Video $video = null): ?\Illuminate\Support\Carbon
    {
        if (! empty($data['published_at'])) {
            return \Illuminate\Support\Carbon::parse($data['published_at']);
        }

        // Publishing without an explicit date stamps "now" so the publish
        // gate (published_at in the past) passes immediately.
        if ($data['status'] === 'published' && ! $video?->published_at) {
            return now();
        }

        return $video?->published_at;
    }

    private function uniqueSlug(string $title, int $courseId): string
    {
        return Slug::unique(
            $title,
            fn (string $slug): bool => Video::query()
                ->where('course_id', $courseId)
                ->where('slug', $slug)
                ->exists()
        );
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contributor;
use App\Models\Course;
use App\Models\Subject;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $courses = Course::with(['subject', 'author', 'reviewer'])
            ->withCount(['videos', 'notes', 'decks', 'quizzes', 'enrollments'])
            ->when($request->filled('subject_id'), fn ($q) => $q->where('subject_id', $request->integer('subject_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%' . addcslashes((string) $request->string('q'), '\\%_') . '%'))
            ->orderBy('sort_order')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $subjects = Subject::orderBy('name')->get(['id', 'name']);

        return view('admin.courses.index', compact('courses', 'subjects'));
    }

    public function create(): View
    {
        return view('admin.courses.edit', [
            'course' => new Course(),
            'subjects' => Subject::orderBy('name')->get(['id', 'name']),
            'contributors' => Contributor::orderBy('name')->get(['id', 'name', 'credentials']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated();
        abort_if($data['status'] === 'published', 422, 'انتشار دوره باید از مسیر بررسی و انتشار انجام شود.');

        $course = new Course(collect($data)->except(['published_at'])->all());
        $course->slug = Slug::unique($data['title'], fn (string $slug) => Course::where('slug', $slug)->exists());
        $course->published_at = $this->publishedAt($data);
        $course->save();

        return redirect()->route('admin.courses.index')->with('status', 'دوره با موفقیت ایجاد شد.');
    }

    public function edit(Course $course): View
    {
        return view('admin.courses.edit', [
            'course' => $course,
            'subjects' => Subject::orderBy('name')->get(['id', 'name']),
            'contributors' => Contributor::orderBy('name')->get(['id', 'name', 'credentials']),
        ]);
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $data = $this->validated($course);
        abort_if($data['status'] === 'published' && $course->status !== 'published', 422, 'انتشار دوره باید از مسیر بررسی و انتشار انجام شود.');

        $course->fill(collect($data)->except(['published_at'])->all());

        if ($data['title'] !== $course->getOriginal('title')) {
            $course->slug = Slug::unique($data['title'], fn (string $slug) => Course::where('slug', $slug)->where('id', '!=', $course->id)->exists());
        }

        $course->published_at = $this->publishedAt($data, $course);
        $course->save();

        return redirect()->route('admin.courses.index')->with('status', 'دوره با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $course->delete();

        return redirect()->route('admin.courses.index')->with('status', 'دوره با موفقیت حذف شد.');
    }

    private function validated(?Course $course = null): array
    {
        return request()->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'cover_image_path' => ['nullable', 'string', 'max:255'],
            'level' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'author_id' => ['nullable', 'integer', 'exists:contributors,id'],
            'reviewer_id' => ['nullable', 'integer', 'exists:contributors,id', 'different:author_id'],
            'status' => ['required', 'in:draft,in_review,published,archived'],
            'published_at' => ['nullable', 'date'],
        ], [
            'reviewer_id.different' => 'نویسنده و بازبین علمی باید دو فرد متفاوت باشند.',
        ]);
    }

    private function publishedAt(array $data, ?Course $course = null): ?\Illuminate\Support\Carbon
    {
        if (! empty($data['published_at'])) {
            return \Illuminate\Support\Carbon::parse($data['published_at']);
        }

        if ($data['status'] === 'published' && ! $course?->published_at) {
            return now();
        }

        return $course?->published_at;
    }
}

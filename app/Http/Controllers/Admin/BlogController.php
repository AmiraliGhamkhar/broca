<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BlogController extends Controller
{
    private const TRANSITIONS = [
        'draft' => ['in_review'],
        'in_review' => ['published', 'draft'],
        'published' => ['archived'],
        'archived' => ['draft'],
    ];

    public function index(Request $request): View
    {
        $posts = BlogPost::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('q'), fn ($query) => $query->where('title', 'like', '%' . addcslashes((string) $request->string('q'), '\\%_') . '%'))
            ->latest('published_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.blogs.index', compact('posts'));
    }

    public function create(): View
    {
        return view('admin.blogs.edit', ['post' => new BlogPost()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated();
        abort_if($data['status'] === 'published', 422, 'انتشار مقاله باید از مسیر بازبینی و انتشار انجام شود.');

        $post = new BlogPost($data);
        $post->slug = Slug::unique($data['title'], fn (string $slug) => BlogPost::query()->where('slug', $slug)->exists());
        $post->published_at = $this->publishedAt($data);
        $post->save();

        return redirect()->route('admin.blogs.index')->with('status', 'مطلب وبلاگ ایجاد شد.');
    }

    public function edit(BlogPost $blog): View
    {
        return view('admin.blogs.edit', ['post' => $blog]);
    }

    public function update(Request $request, BlogPost $blog): RedirectResponse
    {
        $data = $this->validated();
        abort_if($data['status'] === 'published' && $blog->status !== 'published', 422, 'انتشار مقاله باید از مسیر بازبینی و انتشار انجام شود.');

        $titleChanged = $data['title'] !== $blog->title;
        $blog->fill($data);

        if ($titleChanged) {
            $blog->slug = Slug::unique($data['title'], fn (string $slug) => BlogPost::query()->where('id', '!=', $blog->id)->where('slug', $slug)->exists());
        }

        $blog->published_at = $this->publishedAt($data, $blog);
        $blog->save();

        return redirect()->route('admin.blogs.index')->with('status', 'مطلب وبلاگ به‌روزرسانی شد.');
    }

    public function transition(Request $request, BlogPost $blog): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:draft,in_review,published,archived'],
        ]);

        $target = $data['status'];
        $current = (string) $blog->status;

        if ($current !== $target && ! in_array($target, self::TRANSITIONS[$current] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => "گذار وضعیت از «{$current}» به «{$target}» مجاز نیست.",
            ]);
        }

        if ($target === 'published') {
            $author = trim((string) $blog->author_name);
            $reviewer = trim((string) $blog->reviewer_name);

            if ($author === '' || $reviewer === '') {
                throw ValidationException::withMessages([
                    'status' => 'انتشار مقاله نیازمند نویسنده و بازبین علمی است.',
                ]);
            }

            if (mb_strtolower($author) === mb_strtolower($reviewer)) {
                throw ValidationException::withMessages([
                    'status' => 'نویسنده و بازبین علمی باید دو نام متفاوت باشند.',
                ]);
            }
        }

        $blog->status = $target;
        if ($target === 'published' && ! $blog->published_at) {
            $blog->published_at = now();
        }
        $blog->save();

        return back()->with('status', 'وضعیت مقاله به‌روزرسانی شد.');
    }

    public function destroy(BlogPost $blog): RedirectResponse
    {
        $blog->delete();

        return redirect()->route('admin.blogs.index')->with('status', 'مطلب وبلاگ حذف شد.');
    }

    private function validated(): array
    {
        return request()->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:120'],
            'author_name' => ['nullable', 'string', 'max:120'],
            'reviewer_name' => ['nullable', 'string', 'max:120'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['required', 'string'],
            'cover_image_path' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:draft,in_review,published,archived'],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
        ]);
    }

    private function publishedAt(array $data, ?BlogPost $post = null): ?\Illuminate\Support\Carbon
    {
        if (! empty($data['published_at'])) {
            return \Illuminate\Support\Carbon::parse($data['published_at']);
        }

        if ($data['status'] === 'published' && ! $post?->published_at) {
            return now();
        }

        return $post?->published_at;
    }
}

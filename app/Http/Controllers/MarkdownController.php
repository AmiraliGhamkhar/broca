<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Course;
use App\Models\Subject;
use App\Services\SiteMarkdown;
use App\Support\MarkdownTwin;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Markdown twins for the public pages (llmstxt.org convention) plus the
 * /llms.txt index. Every document is built from Eloquent — the same rows
 * that feed the HTML — and answered with RFC 7763's text/markdown MIME.
 *
 * These routes are the "URL-explicit" half of the LLM visibility stack;
 * ServeMarkdown handles the "Accept header" half on the same routes' HTML
 * twins.
 */
class MarkdownController extends Controller
{
    public function llms(): Response
    {
        // Enumerates the whole published catalog; robots.txt explicitly
        // invites AI crawlers, which poll this index hard. Cached and
        // invalidated on publish by PublicIndexCacheObserver.
        return $this->markdown(
            Cache::remember('seo.llms.txt', 3600, fn () => $this->buildLlms())
        );
    }

    private function buildLlms(): string
    {
        $lines = [
            '# Broca | بروکا',
            '',
            '> پلتفرم تخصصی آموزش علوم پایه و بالینی پزشکی برای دانشجویان: دوره‌های ساخت‌یافته با بازبینی علمی، مرور فاصله‌دار (SM-2)، جزوات و ارزیابی. Persian medical-education platform with scientifically reviewed courses, spaced repetition, notes and assessments.',
            '',
            '## دسترسی‌ها',
            '',
            '- [کاتالوگ دوره‌ها]('.route('catalog', absolute: true).'): فهرست کامل دوره‌های منتشرشده',
            '- [پلن‌های اشتراک]('.route('plans', absolute: true).'): قیمت‌ها، مدت دسترسی و پرسش‌های پرتکرار',
            '- [وبلاگ و مقالات]('.route('blog.index', absolute: true).'): محتوای آموزشی رایگان با بازبینی علمی',
            '- [بیانیهٔ پزشکی]('.route('legal.show', 'medical-disclaimer', absolute: true).'): سلب مسئولیت بالینی (YMYL)',
            '',
        ];

        $lines[] = '## شاخه‌های آموزشی';
        $lines[] = '';
        foreach (Subject::query()->where('is_visible', true)->orderBy('sort_order')->get() as $subject) {
            $lines[] = '- ['.$subject->name.']('.route('subjects.show', $subject->slug, absolute: true).'): '.($subject->description ?: 'دوره‌های این شاخه');
            $lines[] = '';
        }

        $lines[] = '## دوره‌ها';
        $lines[] = '';
        foreach (Course::query()->published()->with('subject')->orderBy('sort_order')->get() as $course) {
            $lines[] = SiteMarkdown::courseLine($course);
        }
        $lines[] = '';

        $lines[] = '## آخرین مقالات';
        $lines[] = '';
        foreach (BlogPost::query()->published()->latest('published_at')->limit(10)->get() as $post) {
            $lines[] = '- ['.$post->title.']('.route('blog.show', $post->slug, absolute: true).')';
        }
        $lines[] = '';

        $lines[] = '## نسخه‌های Markdown';
        $lines[] = '';
        $lines[] = 'هر صفحهٔ عمومی نسخهٔ Markdown هم دارد (پسوند `.md` روی همان آدرس) و با `Accept: text/markdown` نیز پاسخ داده می‌شود:';
        $lines[] = '';
        $lines[] = '- '.route('home', absolute: true).' → /index.md';
        $lines[] = '- '.route('catalog', absolute: true).' → /catalog.md';
        $lines[] = '- '.route('plans', absolute: true).' → /plans.md';
        $lines[] = '- '.route('blog.index', absolute: true).' → /blog.md';
        $lines[] = '';

        return implode("\n", $lines);
    }

    public function index(): Response
    {
        return $this->markdown($this->twin('home'));
    }

    public function catalog(): Response
    {
        return $this->markdown($this->twin('catalog'));
    }

    public function subject(Subject $subject): Response
    {
        abort_unless($subject->is_visible, 404);

        return $this->markdown($this->twin('subjects.show', ['subject' => $subject]));
    }

    public function course(Course $course): Response
    {
        abort_unless($course->isPublished(), 404);

        return $this->markdown($this->twin('courses.show', ['course' => $course]));
    }

    public function blogIndex(): Response
    {
        return $this->markdown($this->twin('blog.index'));
    }

    public function blogPost(string $slug): Response
    {
        $markdown = $this->twin('blog.show', ['slug' => $slug]);
        abort_if($markdown === null, 404);

        return $this->markdown($markdown);
    }

    public function plans(): Response
    {
        return $this->markdown($this->twin('plans'));
    }

    public function legal(string $page): Response
    {
        $markdown = $this->twin('legal.show', ['page' => $page]);
        abort_if($markdown === null, 404);

        return $this->markdown($markdown);
    }

    /**
     * Cached twin lookup — same renderer the content-negotiation middleware
     * uses, so the .md routes and the Accept-header path share one cache.
     *
     * @param  array<string, mixed>  $params
     */
    private function twin(string $routeName, array $params = []): ?string
    {
        return MarkdownTwin::markdownForRoute($routeName, $params);
    }

    private function markdown(string $content): Response
    {
        return response($content, 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

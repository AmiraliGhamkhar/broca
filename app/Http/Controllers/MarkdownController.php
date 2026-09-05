<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Course;
use App\Models\Subject;
use App\Services\SiteMarkdown;
use Illuminate\Http\Response;

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

        return $this->markdown(implode("\n", $lines));
    }

    public function index(): Response
    {
        return $this->markdown(SiteMarkdown::home());
    }

    public function catalog(): Response
    {
        return $this->markdown(SiteMarkdown::catalog());
    }

    public function subject(Subject $subject): Response
    {
        abort_unless($subject->is_visible, 404);

        return $this->markdown(SiteMarkdown::subject($subject));
    }

    public function course(Course $course): Response
    {
        abort_unless($course->isPublished(), 404);

        return $this->markdown(SiteMarkdown::course($course));
    }

    public function blogIndex(): Response
    {
        return $this->markdown(SiteMarkdown::blogIndex());
    }

    public function blogPost(string $slug): Response
    {
        $post = BlogPost::query()->published()->where('slug', $slug)->firstOrFail();

        return $this->markdown(SiteMarkdown::blogPost($post));
    }

    public function plans(): Response
    {
        return $this->markdown(SiteMarkdown::plans());
    }

    public function legal(string $page): Response
    {
        $markdown = SiteMarkdown::legal($page);
        abort_if($markdown === null, 404);

        return $this->markdown($markdown);
    }

    private function markdown(string $content): Response
    {
        return response($content, 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

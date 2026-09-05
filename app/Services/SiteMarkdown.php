<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\Course;
use App\Models\Plan;
use App\Models\Subject;
use App\Support\LegalContent;
use App\Support\PlanFaq;

/**
 * Clean Markdown renderings of the public pages — the "Markdown twin" layer
 * for AI answer engines (llmstxt.org convention).
 *
 * Every method builds from Eloquent: the same rows that feed the HTML pages,
 * so the two formats cannot drift. Output is plain Markdown (RFC 7763 MIME
 * text/markdown), absolute URLs, no navigation chrome.
 */
class SiteMarkdown
{
    public static function home(): string
    {
        $lines = [
            '# Broca | بروکا',
            '',
            '> پلتفرم تخصصی آموزش علوم پایه و بالینی پزشکی برای دانشجویان: دوره‌های ساخت‌یافته با بازبینی علمی، مرور فاصله‌دار (SM-2)، جزوات و ارزیابی. Persian medical-education platform with scientifically reviewed courses, spaced repetition and assessments.',
            '',
            '## دسترسی‌ها',
            '',
            '- [کاتالوگ دوره‌ها]('.self::url('catalog').'): فهرست کامل دوره‌های منتشرشده',
            '- [پلن‌های اشتراک]('.self::url('plans').'): قیمت‌ها و محدودهٔ دسترسی',
            '- [وبلاگ و مقالات]('.self::url('blog.index').'): محتوای آموزشی رایگان',
            '- [بیانیهٔ پزشکی]('.self::url('legal.show', 'medical-disclaimer').'): سلب مسئولیت بالینی (YMYL)',
            '',
        ];

        $subjects = Subject::query()->where('is_visible', true)->orderBy('sort_order')->get();
        if ($subjects->isNotEmpty()) {
            $lines[] = '## شاخه‌های آموزشی';
            $lines[] = '';
            foreach ($subjects as $subject) {
                $lines[] = '- ['.$subject->name.']('.self::subjectUrl($subject).'): '.self::summary($subject->description, 'مجموعهٔ دوره‌های این شاخه.');
            }
            $lines[] = '';
        }

        $courses = Course::query()->published()->with('subject')->orderBy('sort_order')->get();
        if ($courses->isNotEmpty()) {
            $lines[] = '## دوره‌ها';
            $lines[] = '';
            foreach ($courses as $course) {
                $lines[] = self::courseLine($course);
            }
            $lines[] = '';
        }

        $posts = BlogPost::query()->published()->latest('published_at')->limit(5)->get();
        if ($posts->isNotEmpty()) {
            $lines[] = '## آخرین مقالات';
            $lines[] = '';
            foreach ($posts as $post) {
                $lines[] = '- ['.$post->title.']('.self::blogUrl($post).') — '.self::summary($post->excerpt, '');
            }
            $lines[] = '';
        }

        $lines[] = '---';
        $lines[] = 'بروکا مشاورهٔ پزشکی ارائه نمی‌دهد؛ تمامی محتوا صرفاً جنبهٔ آموزشی دارد.';

        return implode("\n", $lines);
    }

    public static function catalog(): string
    {
        $lines = [
            '# کاتالوگ دوره‌های پزشکی — Broca',
            '',
            '> نسخهٔ وب: '.self::url('catalog'),
            '',
            '## دوره‌های منتشرشده',
            '',
        ];

        $courses = Course::query()->published()->with(['subject', 'author', 'reviewer'])->orderBy('sort_order')->get();

        foreach ($courses as $course) {
            $lines[] = self::courseLine($course);
        }

        return implode("\n", $lines);
    }

    public static function subject(Subject $subject): string
    {
        $lines = [
            '# دوره‌های '.$subject->name.' — Broca',
            '',
            '> نسخهٔ وب: '.self::subjectUrl($subject),
            '',
            self::summary($subject->description, ''),
            '',
            '## دوره‌ها',
            '',
        ];

        $courses = $subject->courses()->published()->with(['author', 'reviewer'])->orderBy('sort_order')->get();

        foreach ($courses as $course) {
            $lines[] = self::courseLine($course);
        }

        if ($courses->isEmpty()) {
            $lines[] = 'هنوز دوره‌ای برای این شاخه منتشر نشده است.';
        }

        return implode("\n", $lines);
    }

    public static function course(Course $course): string
    {
        $lines = [
            '# '.$course->title,
            '',
            '> نسخهٔ وب: '.self::courseUrl($course),
            '',
            self::summary($course->excerpt ?: $course->description, ''),
            '',
            '## مشخصات',
            '',
            '- شاخه: '.$course->subject?->name,
            '- سطح: '.($course->level ?: 'علوم پایه پزشکی'),
            '- مدرس/نویسنده: '.$course->author?->name.($course->author?->credentials ? ' — '.$course->author->credentials : ''),
            '- بازبین علمی: '.$course->reviewer?->name,
            '',
        ];

        $videos = $course->videos()->published()->orderBy('sort_order')->get();
        if ($videos->isNotEmpty()) {
            $lines[] = '## ویدیوها ('.$videos->count().')';
            $lines[] = '';
            foreach ($videos as $video) {
                $lines[] = self::itemLine($video->title, (bool) $video->is_free_designated);
            }
            $lines[] = '';
        }

        $notes = $course->notes()->published()->orderBy('sort_order')->get();
        if ($notes->isNotEmpty()) {
            $lines[] = '## جزوات ('.$notes->count().')';
            $lines[] = '';
            foreach ($notes as $note) {
                $lines[] = self::itemLine($note->title, (bool) $note->is_free_designated);
            }
            $lines[] = '';
        }

        $decks = $course->decks()->published()->withCount('cards')->orderBy('sort_order')->get();
        if ($decks->isNotEmpty()) {
            $lines[] = '## دسته‌های فلش‌کارت ('.$decks->count().')';
            $lines[] = '';
            foreach ($decks as $deck) {
                $lines[] = '- '.$deck->title.' ('.$deck->cards_count.' کارت)';
            }
            $lines[] = '';
        }

        $quizzes = $course->quizzes()->published()->get();
        if ($quizzes->isNotEmpty()) {
            $lines[] = '## آزمون‌ها ('.$quizzes->count().')';
            $lines[] = '';
            foreach ($quizzes as $quiz) {
                $lines[] = '- '.$quiz->title;
            }
            $lines[] = '';
        }

        $lines[] = '---';
        $lines[] = 'بروکا مشاورهٔ پزشکی ارائه نمی‌دهد؛ محتوا صرفاً آموزشی است.';

        return implode("\n", $lines);
    }

    public static function blogIndex(): string
    {
        $lines = [
            '# وبلاگ و مقالات آموزشی — Broca',
            '',
            '> نسخهٔ وب: '.self::url('blog.index'),
            '',
            '## مقالات',
            '',
        ];

        $posts = BlogPost::query()->published()->latest('published_at')->get();

        foreach ($posts as $post) {
            $lines[] = '- ['.$post->title.']('.self::blogUrl($post).') — '.($post->published_at?->format('Y/m/d') ?? '').': '.self::summary($post->excerpt, '');
        }

        return implode("\n", $lines);
    }

    public static function blogPost(BlogPost $post): string
    {
        $lines = [
            '# '.$post->title,
            '',
            '> نسخهٔ وب: '.self::blogUrl($post),
            '',
            '- نویسنده: '.$post->author_name,
            '- بازبین علمی: '.$post->reviewer_name,
            '- انتشار: '.$post->published_at?->format('Y/m/d'),
            '',
            $post->content,
            '',
        ];

        return implode("\n", $lines);
    }

    public static function plans(): string
    {
        $lines = [
            '# پلن‌های اشتراک — Broca',
            '',
            '> نسخهٔ وب: '.self::url('plans'),
            '',
            '## پلن‌ها',
            '',
        ];

        foreach (Plan::query()->where('is_active', true)->orderBy('sort_order')->get() as $plan) {
            $toman = (int) floor($plan->price_irr / 10);
            $period = $plan->duration_months > 0 ? $plan->duration_months.' ماه' : 'دسترسی رایگان';
            $lines[] = '### '.$plan->name;
            $lines[] = '';
            $lines[] = self::summary($plan->description, '');
            $lines[] = '';
            $lines[] = '- قیمت: '.number_format($toman).' تومان ('.number_format((int) $plan->price_irr).' ریال)';
            $lines[] = '- مدت: '.$period;
            $lines[] = '';
        }

        $lines[] = '## پرسش‌های پرتکرار';
        $lines[] = '';
        foreach (PlanFaq::all() as $item) {
            $lines[] = '### '.$item['q'];
            $lines[] = '';
            $lines[] = $item['a'];
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    public static function legal(string $page): ?string
    {
        $document = LegalContent::document($page);
        if ($document === null) {
            return null;
        }

        $lines = [
            '# '.$document['heading'].' — Broca',
            '',
            '> نسخهٔ وب: '.self::url('legal.show', $page),
            '',
        ];

        foreach ($document['sections'] as $section) {
            $lines[] = '## '.($section['h'] ?? $document['heading']);
            $lines[] = '';
            $lines[] = $section['p'];
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /** One line per course, used by home/catalog/subject. */
    public static function courseLine(Course $course): string
    {
        $parts = [self::summary($course->excerpt ?: $course->description, 'دورهٔ آموزشی '.$course->title.' در بروکا.')];
        if ($course->author?->name) {
            $parts[] = 'مدرس: '.$course->author->name;
        }
        if ($course->reviewer?->name) {
            $parts[] = 'بازبین علمی: '.$course->reviewer->name;
        }

        return '- ['.$course->title.']('.self::courseUrl($course).') — '.implode(' · ', $parts);
    }

    private static function itemLine(string $title, bool $free): string
    {
        return '- '.$title.($free ? ' (دسترسی رایگان)' : '');
    }

    private static function summary(?string $text, string $fallback): string
    {
        $clean = trim((string) $text);
        if ($clean === '') {
            return $fallback;
        }

        return \Illuminate\Support\Str::limit($clean, 160, '');
    }

    private static function url(string $route, ...$params): string
    {
        return route($route, $params, true);
    }

    private static function subjectUrl(Subject $subject): string
    {
        return route('subjects.show', $subject->slug, true);
    }

    private static function courseUrl(Course $course): string
    {
        return route('courses.show', $course->slug, true);
    }

    private static function blogUrl(BlogPost $post): string
    {
        return route('blog.show', $post->slug, true);
    }
}

<?php

namespace App\Support;

use App\Models\BlogPost;
use App\Models\Course;
use App\Models\Subject;
use App\Services\SiteMarkdown;
use Illuminate\Support\Facades\Cache;

/**
 * Maps the public HTML routes to their Markdown twins (llmstxt.org
 * convention: the same path + ".md").
 *
 * Two consumers:
 *  - App\Http\Middleware\ServeMarkdown — content negotiation (Accept:
 *    text/markdown) and the .md route handlers build the document.
 *  - the layouts.app view composer — renders <link rel="alternate"> and the
 *    hidden agent hint; that only needs the URL, which never touches the DB.
 *
 * Rendering is CACHED (audit 2026-09-07): twins run 4–8 uncached queries per
 * hit against exactly the crawler population robots.txt invites, while their
 * HTML siblings (sitemap.xml, llms.txt) are cached. Invalidations go through
 * PublicIndexCacheObserver, which bumps the generation counter below — so a
 * publish makes new content discoverable immediately, without per-URL key
 * bookkeeping.
 */
class MarkdownTwin
{
    public const VERSION_KEY = 'seo.md.generation';

    /** @return list<string> route names that have a Markdown twin */
    public static function twinRoutes(): array
    {
        return [
            'home',
            'catalog',
            'plans',
            'blog.index',
            'blog.show',
            'subjects.show',
            'courses.show',
            'legal.show',
        ];
    }

    public static function hasTwin(?string $routeName): bool
    {
        return $routeName !== null && in_array($routeName, self::twinRoutes(), true);
    }

    /**
     * Absolute URL of the Markdown twin for the given route, or null.
     * Pure URL construction — safe to run on every HTML page render.
     *
     * @param array<string, mixed> $params the route's parameters (models or
     *        raw values — both are handled, as route()->parameters() returns
     *        bound models after dispatch).
     */
    public static function alternateUrlForRoute(string $routeName, array $params): ?string
    {
        return match ($routeName) {
            'home' => url('/index.md'),
            'catalog' => url('/catalog.md'),
            'plans' => url('/plans.md'),
            'blog.index' => url('/blog.md'),
            'blog.show' => self::slugUrl('/blog', (string) ($params['slug'] ?? '')),
            'subjects.show' => self::modelSlugUrl('seo.md.subject', $params['subject'] ?? null),
            'courses.show' => self::modelSlugUrl('seo.md.course', $params['course'] ?? null),
            'legal.show' => self::slugUrl('/', (string) ($params['page'] ?? '')),
            default => null,
        };
    }

    /**
     * Current cache generation. Bumped by PublicIndexCacheObserver::flush()
     * so every cached twin invalidates at once when published content
     * changes. Driver-agnostic (no wildcard flush needed).
     */
    public static function generation(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    public static function bumpGeneration(): void
    {
        Cache::forever(self::VERSION_KEY, self::generation() + 1);
    }

    /**
     * The cached Markdown document for the given route, or null when the
     * route has no twin or its content is no longer public.
     *
     * @param  array<string, mixed>  $params
     */
    public static function markdownForRoute(string $routeName, array $params): ?string
    {
        $key = self::cacheKey($routeName, $params);
        $cached = Cache::get($key);

        if (is_string($cached) || $cached === '') {
            return $cached === '' ? null : $cached;
        }

        $markdown = self::renderMarkdown($routeName, $params);

        // Cache both hits and confirmed misses ('' encodes "no twin /
        // unpublished") so crawler 404s don't re-query the DB either.
        Cache::put($key, $markdown === null ? '' : $markdown, 3600);

        return $markdown;
    }

    /**
     * The uncached renderer. Kept separate so the cache layer is the only
     * public entry point for twins.
     *
     * @param  array<string, mixed>  $params
     */
    public static function renderMarkdown(string $routeName, array $params): ?string
    {
        return match ($routeName) {
            'home' => SiteMarkdown::home(),
            'catalog' => SiteMarkdown::catalog(),
            'plans' => SiteMarkdown::plans(),
            'blog.index' => SiteMarkdown::blogIndex(),
            'blog.show' => self::markdownBlog((string) ($params['slug'] ?? '')),
            'subjects.show' => self::markdownSubject($params['subject'] ?? null),
            'courses.show' => self::markdownCourse($params['course'] ?? null),
            'legal.show' => SiteMarkdown::legal((string) ($params['page'] ?? '')),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private static function cacheKey(string $routeName, array $params): string
    {
        $normalized = [];

        foreach ($params as $key => $value) {
            $normalized[$key] = $value instanceof \Illuminate\Database\Eloquent\Model
                ? $value->getMorphClass().':'.$value->getKey()
                : (string) $value;
        }

        return 'seo.md.'.self::generation().'.'.md5($routeName.'|'.serialize($normalized));
    }

    private static function markdownBlog(string $slug): ?string
    {
        if ($slug === '') {
            return null;
        }

        $post = BlogPost::query()->published()->where('slug', $slug)->first();

        return $post ? SiteMarkdown::blogPost($post) : null;
    }

    private static function markdownSubject(Subject|string|null $subject): ?string
    {
        if ($subject === null) {
            return null;
        }

        $model = $subject instanceof Subject
            ? $subject
            : Subject::query()->where('slug', (string) $subject)->first();

        return $model && $model->is_visible ? SiteMarkdown::subject($model) : null;
    }

    private static function markdownCourse(Course|string|null $course): ?string
    {
        if ($course === null) {
            return null;
        }

        $model = $course instanceof Course
            ? $course
            : Course::query()->where('slug', (string) $course)->first();

        return $model && $model->isPublished() ? SiteMarkdown::course($model) : null;
    }

    /**
     * Absolute "{prefix}{slug}.md" URL (blog posts, legal pages).
     */
    private static function slugUrl(string $prefix, string $slug): ?string
    {
        return $slug === '' ? null : url(rtrim($prefix, '/').'/'.rawurlencode($slug).'.md');
    }

    /**
     * Absolute .md URL for a slug-bound route, from either the bound model
     * (post-dispatch) or the raw slug string (pre-dispatch). Always built
     * from the slug itself — never from model-key resolution.
     */
    private static function modelSlugUrl(string $route, Subject|Course|string|null $model): ?string
    {
        $slug = $model instanceof \Illuminate\Database\Eloquent\Model
            ? (string) $model->slug
            : (string) $model;

        return $slug === '' ? null : route($route, $slug, true);
    }
}

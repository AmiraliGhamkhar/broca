<?php

namespace App\Support;

use App\Models\BlogPost;
use App\Models\Course;
use App\Models\Subject;
use App\Services\SiteMarkdown;

/**
 * Maps the public HTML routes to their Markdown twins (llmstxt.org
 * convention: the same path + ".md").
 *
 * Two consumers:
 *  - App\Http\Middleware\ServeMarkdown — content negotiation (Accept:
 *    text/markdown) and the .md route handlers build the document.
 *  - the layouts.app view composer — renders <link rel="alternate"> and the
 *    hidden agent hint; that only needs the URL, which never touches the DB.
 */
class MarkdownTwin
{
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
     * The Markdown document for the given route, or null when the route has
     * no twin or its content is no longer public (unpublished/removed).
     * Touches the DB — call it only when a client actually asks for Markdown.
     *
     * @param array<string, mixed> $params
     */
    public static function markdownForRoute(string $routeName, array $params): ?string
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

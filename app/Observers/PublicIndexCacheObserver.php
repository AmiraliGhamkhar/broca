<?php

namespace App\Observers;

use App\Support\MarkdownTwin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Invalidates the cached public discovery documents (sitemap.xml, llms.txt)
 * whenever the content they enumerate changes.
 *
 * Both documents walk every visible subject, published course and published
 * post on each request. They are the endpoints AI crawlers poll hardest
 * (robots.txt explicitly invites GPTBot/ClaudeBot/PerplexityBot), so building
 * them per-hit is a DB-load amplifier on shared hosting.
 *
 * Caching them without invalidation would delay newly published content from
 * being discoverable, so publish/unpublish/delete clears the cache
 * immediately — same pattern as CourseFreeCapObserver.
 */
class PublicIndexCacheObserver
{
    /** Cache keys covering the generated discovery documents. */
    public const KEYS = [
        'seo.sitemap.xml',
        'seo.llms.txt',
    ];

    public static function flush(): void
    {
        foreach (self::KEYS as $key) {
            Cache::forget($key);
        }

        // The Markdown twins cache under generation-suffixed keys (exact-key
        // stores can't do prefix flushes), so invalidation bumps the
        // generation counter instead — every twin re-renders on next request.
        MarkdownTwin::bumpGeneration();
    }

    public function saved(Model $model): void
    {
        // Only a change that alters what is publicly listed matters.
        if ($model->wasRecentlyCreated || $model->wasChanged([
            'status', 'published_at', 'deleted_at', 'is_visible', 'slug', 'title', 'name', 'sort_order',
        ])) {
            self::flush();
        }
    }

    public function deleted(Model $model): void
    {
        self::flush();
    }

    public function restored(Model $model): void
    {
        self::flush();
    }
}

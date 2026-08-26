<?php

namespace App\Support;

/**
 * Persian-safe slug generation. Str::slug() strips Persian letters entirely
 * (ASCII transliteration), which yields empty slugs for Persian titles, so we
 * keep Unicode letters/digits and only replace separators.
 */
final class Slug
{
    public static function fromTitle(string $title): string
    {
        $slug = trim((string) preg_replace('/[^\p{L}\p{N}]+/u', '-', trim($title)), '-');

        return $slug !== '' ? $slug : 'item';
    }

    /**
     * Generate a slug unique within $exists closure (return true when the slug
     * is taken). Appends -2, -3, … on collisions.
     */
    public static function unique(string $title, callable $exists): string
    {
        $base = self::fromTitle($title);
        $slug = $base;
        $attempt = 1;

        while ($exists($slug)) {
            $slug = $base.'-'.(++$attempt);
        }

        return $slug;
    }
}

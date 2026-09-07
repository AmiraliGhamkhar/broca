<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Operator-manageable brand assets: the navbar/footer logo and the landing
 * hero photograph. Files live on the PUBLIC disk under fixed names so the
 * layouts can render them without a settings table:
 *
 *   brand/logo.*   → replaces the «ب» letter-mark when present
 *   hero/hero.jpg  → hero JPEG fallback (always expected)
 *   hero/hero.webp → hero WebP source (optional — only linked when present)
 *
 * The Telegram admin bot writes these files (/set_logo, /set_hero,
 * /remove_logo); this class is the single read path the Blade views use.
 * Results are memoized per request — file stats would otherwise run on
 * every partial render.
 */
final class BrandAssets
{
    /** @var array<string, string|null> */
    private static array $memo = [];

    /** Absolute public URL of an uploaded logo image, or null (letter-mark fallback). */
    public static function logoUrl(): ?string
    {
        return self::$memo['logo'] ??= self::firstPublicFile('brand/logo', ['png', 'jpg', 'jpeg', 'webp', 'svg', 'gif']);
    }

    /** Absolute public URL of the hero WebP source, or null (JPEG-only rendering). */
    public static function heroWebpUrl(): ?string
    {
        return self::$memo['hero_webp'] ??= self::resolveHeroWebp();
    }

    /**
     * The absence marker (set when an operator upload could not produce a
     * current WebP) must outrank the committed repo WebP — otherwise a stale
     * docroot twin would render over a newer uploaded JPEG.
     */
    private static function resolveHeroWebp(): ?string
    {
        if (Storage::disk('public')->exists('images/hero/hero.webp.absent')) {
            return null;
        }

        return self::firstPublicFile('hero/hero', ['webp'])
            ?? (is_file(public_path('images/hero/hero.webp')) ? asset('images/hero/hero.webp') : null);
    }

    /** Absolute public URL of the hero JPEG (the <img> fallback — always rendered). */
    public static function heroJpgUrl(): string
    {
        $url = self::firstPublicFile('hero/hero', ['jpg', 'jpeg']);

        return $url ?? (is_file(public_path('images/hero/hero.jpg')) ? asset('images/hero/hero.jpg') : asset('images/og-default.png'));
    }

    /**
     * Replace the fixed-name brand file. Writes original bytes with their
     * existing extension (validated by the caller) and, for the hero, keeps
     * the WebP twin consistent: a fresh WebP is generated via GD when the
     * host provides it; when it does not, the stale WebP is REMOVED so the
     * <picture> source can never serve an image older than the JPEG.
     *
     * Uploaded files live on the public disk (served under /storage via
     * storage:link); the committed repo defaults under public/images remain
     * the fallback when no upload exists.
     *
     * @param  array{body:string, extension:string}  $image  downloaded bytes + extension
     * @param  'logo'|'hero'  $target
     */
    public static function store(array $image, string $target): string
    {
        $directory = $target === 'hero' ? 'images/hero' : 'images/brand';
        $basename = $target === 'hero' ? 'hero' : 'logo';

        // Drop every previous variant first: the new file decides the
        // extension, and a stale sibling must never win the lookup.
        foreach (['png', 'jpg', 'jpeg', 'webp', 'svg', 'gif'] as $extension) {
            Storage::disk('public')->delete($directory.'/'.$basename.'.'.$extension);
        }

        $extension = strtolower($image['extension']);
        $storageKey = $directory.'/'.$basename.'.'.$extension;
        Storage::disk('public')->put($storageKey, $image['body']);

        if ($target === 'hero') {
            self::syncHeroWebp($image['body'], $extension);
        }

        self::$memo = [];

        return Storage::disk('public')->url($storageKey);
    }

    public static function removeLogo(): void
    {
        foreach (['png', 'jpg', 'jpeg', 'webp', 'svg', 'gif'] as $extension) {
            Storage::disk('public')->delete('images/brand/logo.'.$extension);
        }

        self::$memo = [];
    }

    /**
     * Regenerate hero.webp from the uploaded image via GD, or mark the WebP
     * twin absent when GD/WebP support is unavailable (fail-consistent: the
     * view falls back to JPEG-only and never serves a stale WebP — including
     * the committed repo one, which the absence marker outranks).
     */
    private static function syncHeroWebp(string $bytes, string $extension): void
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            self::markHeroWebpAbsent();

            return;
        }

        $source = @imagecreatefromstring($bytes);

        if ($source === false) {
            self::markHeroWebpAbsent();

            return;
        }

        // Flatten to a plain truecolor canvas: PNG alpha over WebP can
        // produce dark fringes in some GD builds.
        $width = imagesx($source);
        $height = imagesy($source);
        $canvas = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $source, 0, 0, 0, 0, $width, $height);

        ob_start();
        try {
            imagewebp($canvas, null, 82);
            $webp = (string) ob_get_clean();
            Storage::disk('public')->put('images/hero/hero.webp', $webp);
            Storage::disk('public')->delete('images/hero/hero.webp.absent');
        } finally {
            ob_end_clean();
            imagedestroy($canvas);
            imagedestroy($source);
        }

        // The uploaded file itself keeps its own extension (jpg/png/webp);
        // the hero <img> fallback reads images/hero/hero.jpg — normalize a
        // non-jpeg upload into that name so the fallback always exists.
        if ($extension !== 'jpg' && $extension !== 'jpeg') {
            Storage::disk('public')->delete(['images/hero/hero.jpg', 'images/hero/hero.jpeg']);
            Storage::disk('public')->put('images/hero/hero.jpg', $bytes);
        }
    }

    private static function markHeroWebpAbsent(): void
    {
        Storage::disk('public')->delete('images/hero/hero.webp');
        Storage::disk('public')->put('images/hero/hero.webp.absent', '');
    }

    /**
     * @param  list<string>  $extensions
     */
    private static function firstPublicFile(string $basename, array $extensions): ?string
    {
        foreach ($extensions as $extension) {
            $key = $basename.'.'.$extension;

            if (Storage::disk('public')->exists($key)) {
                return Storage::disk('public')->url($key);
            }
        }

        return null;
    }
}

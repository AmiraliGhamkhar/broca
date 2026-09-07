<?php

namespace Tests\Feature;

use App\Support\BrandAssets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Bot-managed brand assets (audit/round 2026-09-07): logo and hero live on
 * the public disk under FIXED names (images/brand/logo.*, images/hero/hero.*)
 * so views resolve them without a DB setting. Storing deletes every sibling
 * variant first — a stale extension must never win the lookup.
 */
class BrandAssetsTest extends TestCase
{
    use RefreshDatabase;

    /** Standard 1×1 transparent PNG. */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        // BrandAssets memoizes URL lookups per request; the static cache
        // survives across tests in the same PHP process, so reset it.
        BrandAssets::removeLogo();
    }

    public function test_store_writes_the_logo_under_a_fixed_name_and_url(): void
    {
        $url = BrandAssets::store($this->image('png'), 'logo');

        Storage::disk('public')->assertExists('images/brand/logo.png');
        $this->assertSame(Storage::disk('public')->url('images/brand/logo.png'), $url);
        $this->assertNotNull(BrandAssets::logoUrl());
    }

    public function test_replacing_the_logo_deletes_stale_variants(): void
    {
        BrandAssets::store($this->image('png'), 'logo');
        BrandAssets::store($this->image('jpg'), 'logo');

        Storage::disk('public')->assertExists('images/brand/logo.jpg');
        Storage::disk('public')->assertMissing('images/brand/logo.png');
    }

    public function test_remove_logo_falls_back_to_null(): void
    {
        BrandAssets::store($this->image('png'), 'logo');
        $this->assertNotNull(BrandAssets::logoUrl());

        BrandAssets::removeLogo();

        Storage::disk('public')->assertMissing('images/brand/logo.png');
        $this->assertNull(BrandAssets::logoUrl());
    }

    public function test_hero_store_normalizes_a_non_jpeg_upload_and_produces_a_consistent_twin(): void
    {
        BrandAssets::store($this->image('png'), 'hero');

        // The upload keeps its own extension…
        Storage::disk('public')->assertExists('images/hero/hero.png');
        // …and the JPEG fallback the hero <img> tag relies on always exists.
        Storage::disk('public')->assertExists('images/hero/hero.jpg');
        $this->assertSame(Storage::disk('public')->url('images/hero/hero.jpg'), BrandAssets::heroJpgUrl());

        // The WebP twin was regenerated (GD available) OR explicitly marked
        // absent (no GD/WebP support) — either way, never a stale one.
        if (Storage::disk('public')->exists('images/hero/hero.webp')) {
            $this->assertNotNull(BrandAssets::heroWebpUrl());
        } else {
            Storage::disk('public')->assertExists('images/hero/hero.webp.absent');
            $this->assertNull(BrandAssets::heroWebpUrl(), 'the absence marker must outrank any WebP, including the committed docroot one');
        }
    }

    public function test_svg_is_rejected_at_the_bot_boundary(): void
    {
        $this->expectException(\RuntimeException::class);

        // The bot's upload extractor refuses SVG before BrandAssets ever
        // sees it; the boundary contract here is that the support class is
        // only ever handed raster extensions (exercised via store()).
        BrandAssets::store(['body' => '<svg xmlns="http://www.w3.org/2000/svg"/>', 'extension' => 'svg'], 'logo');
    }

    /**
     * @return array{body:string, extension:string}
     */
    private function image(string $extension): array
    {
        return [
            'body' => (string) base64_decode(self::PNG, true),
            'extension' => $extension,
        ];
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_home_page_renders_successfully(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="fa"', false);
        $response->assertSee('heart-placeholder', false);
        $response->assertSee('این ویدیوی نمونه است', false);
    }

    public function test_vazirmatn_font_is_self_hosted(): void
    {
        // The @font-face for Vazirmatn lives in the compiled CSS,
        // not the HTML body, so we look it up via Vite's manifest.
        $manifestPath = public_path('build/manifest.json');

        $this->assertFileExists($manifestPath, 'Vite manifest must exist.');

        $manifest = json_decode(file_get_contents($manifestPath), true);
        $cssKey = 'resources/css/app.css';
        $this->assertArrayHasKey($cssKey, $manifest, 'manifest must contain app.css entry.');
        $cssFile = public_path('build/'.$manifest[$cssKey]['file']);

        $this->assertFileExists($cssFile);
        $css = file_get_contents($cssFile);

        $this->assertStringContainsString('Vazirmatn', $css);
        $this->assertStringContainsString('/fonts/vazirmatn/', $css);
    }
}

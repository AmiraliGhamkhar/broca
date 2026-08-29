<?php

namespace Tests\Feature;

use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Security headers, secure session cookie, and the dynamic robots/sitemap
 * endpoints (regression for the static public/robots.txt shadow).
 */
class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_csp_restricts_sources_to_self_plus_inline_necessities(): void
    {
        $csp = $this->get(route('home'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->headers->get('Content-Security-Policy');

        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src 'self' 'unsafe-inline'", $csp);
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline'", $csp);
        $this->assertStringContainsString("img-src 'self' data:", $csp);
        $this->assertStringContainsString("connect-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
    }

    public function test_session_cookie_is_marked_secure_when_configured(): void
    {
        config(['session.secure' => true]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSecureCookie(config('session.cookie'));
    }

    public function test_robots_txt_is_served_by_the_controller_not_a_static_file(): void
    {
        // The static shadow file that used to hide this route must never
        // come back (Apache/Nginx serve public/robots.txt before PHP).
        $this->assertFileDoesNotExist(public_path('robots.txt'));

        $this->get(route('seo.robots'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('User-agent: *')
            ->assertSee('Disallow: /admin')
            ->assertSee('Sitemap: '.route('seo.sitemap'));
    }

    public function test_sitemap_lists_published_courses_only(): void
    {
        $published = Course::factory()->published()->create();
        Course::factory()->create(); // draft — must not appear

        $content = $this->get(route('seo.sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->getContent();

        $this->assertStringContainsString('<loc>'.route('courses.show', $published).'</loc>', $content);
        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $content);
        $this->assertStringContainsString('</urlset>', $content);
    }
}

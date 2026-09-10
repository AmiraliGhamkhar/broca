<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Course;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * LLM / answer-engine surface (2026-09-05 audit):
 *  - robots.txt: retrieval agents + Content-Signal,
 *  - /llms.txt: curated Markdown index,
 *  - .md twins: clean Markdown per public page,
 *  - Accept: text/markdown content negotiation (+ Vary, Link),
 *  - HTML pages advertising the Markdown alternate.
 */
class GeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_robots_txt_covers_retrieval_agents_and_declares_content_signal(): void
    {
        $content = $this->get(route('seo.robots'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->getContent();

        // Retrieval agents (the ones that fetch pages while answering user
        // queries — where citations come from) must be named explicitly.
        foreach (['OAI-SearchBot', 'ChatGPT-User', 'Claude-SearchBot', 'Claude-User', 'PerplexityBot', 'Perplexity-User'] as $bot) {
            $this->assertStringContainsString("User-agent: {$bot}", $content);
        }

        // Training agents stay allowed by current client policy.
        foreach (['GPTBot', 'ClaudeBot', 'Google-Extended', 'CCBot', 'Applebot-Extended', 'Meta-ExternalAgent'] as $bot) {
            $this->assertStringContainsString("User-agent: {$bot}", $content);
        }

        $this->assertStringContainsString('Content-Signal: search=yes, ai-input=yes, ai-train=yes', $content);
        $this->assertStringContainsString('Disallow: /admin', $content);
    }

    public function test_every_named_robots_group_repeats_the_full_rule_set(): void
    {
        // RFC 9309 §2.2.1: a crawler obeys ONLY the most specific matching
        // User-agent group — named groups and the "*" group are never
        // combined. An earlier revision put the Disallow set solely in the
        // "*" group, so every named agent legally ignored those rules.
        $content = $this->get(route('seo.robots'))->assertOk()->getContent();

        $namedAgents = [
            'OAI-SearchBot', 'ChatGPT-User', 'Claude-SearchBot', 'Claude-User',
            'PerplexityBot', 'Perplexity-User', 'GPTBot', 'ClaudeBot', 'CCBot',
            'Google-Extended', 'Applebot-Extended', 'Meta-ExternalAgent',
        ];

        foreach (['/admin', '/dashboard', '/checkout', '/payments', '/video-playback'] as $path) {
            $this->assertSame(
                count($namedAgents) + 1, // named groups + the "*" group
                substr_count($content, 'Disallow: '.$path),
                "Disallow: {$path} must appear once per group (the wildcard group plus every named agent)."
            );
        }

        foreach ($namedAgents as $agent) {
            // The agent's group must carry the protected paths AND the
            // site-wide allow in one block.
            $group = substr($content, (int) strpos($content, 'User-agent: '.$agent));
            $group = substr($group, 0, (int) strpos($group."\n\n", "\n\n"));
            $this->assertStringContainsString('Disallow: /admin', $group, "[{$agent}] group lost the protected paths.");
            $this->assertStringContainsString('Allow: /', $group, "[{$agent}] group lost the site-wide allow.");
            $this->assertStringContainsString('Content-Signal:', $group, "[{$agent}] group lost the content signal.");
        }
    }

    public function test_llms_txt_is_a_markdown_index_of_the_public_site(): void
    {
        $subject = Subject::factory()->create(['is_visible' => true]);
        $course = Course::factory()->published()->for($subject, 'subject')->create();
        $post = BlogPost::factory()->published()->create();

        $content = $this->get('/llms.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->getContent();

        $this->assertStringStartsWith('# ', $content);
        $this->assertStringContainsString('>', $content); // identity blockquote

        foreach ([
            route('catalog'),
            route('plans'),
            route('blog.index'),
            route('subjects.show', $subject),
            route('courses.show', $course),
            route('blog.show', $post->slug),
        ] as $url) {
            $this->assertStringContainsString($url, $content);
        }

        $this->assertStringContainsString($course->title, $content);
    }

    public function test_markdown_twins_serve_clean_markdown(): void
    {
        $subject = Subject::factory()->create(['is_visible' => true]);
        $course = Course::factory()->published()->for($subject, 'subject')->create(['title' => 'ژنتیک پزشکی بروکا']);
        $post = BlogPost::factory()->published()->create(['slug' => 'test-post', 'title' => 'ناحیه بروکا']);

        $this->get('/index.md')->assertOk()->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
        $this->get('/catalog.md')->assertOk()->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
        $this->get('/plans.md')->assertOk()->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
        $this->get('/blog.md')->assertOk()->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
        $this->get('/terms.md')->assertOk()->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');

        $this->get('/subjects/'.$subject->slug.'.md')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertSee($subject->name, false);

        $courseContent = $this->get('/courses/'.$course->slug.'.md')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->getContent();
        $this->assertStringContainsString('# ژنتیک پزشکی بروکا', $courseContent);
        $this->assertStringContainsString(route('courses.show', $course), $courseContent);

        $this->get('/blog/test-post.md')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertSee('ناحیه بروکا', false);
    }

    public function test_markdown_twin_is_404_for_unpublished_or_unknown_content(): void
    {
        $draft = Course::factory()->create();
        $this->get('/courses/'.$draft->slug.'.md')->assertNotFound();

        $this->get('/courses/does-not-exist.md')->assertNotFound();
        $this->get('/blog/does-not-exist.md')->assertNotFound();
        $this->get('/legal/not-a-page.md')->assertNotFound();
    }

    public function test_content_negotiation_serves_markdown_only_when_explicitly_requested(): void
    {
        Course::factory()->published()->create(['title' => 'فیزیولوژی قلب نمونه']);

        // Explicit preference → Markdown + Vary + Link back to the HTML page.
        $this->get(route('catalog'), ['Accept' => 'text/markdown'])
            ->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertHeader('Vary', 'Accept')
            ->assertHeader('Link', '<'.route('catalog').'>; rel="alternate"; type="text/html"')
            ->assertSee('فیزیولوژی قلب نمونه', false);

        // Browser-style header → HTML, untouched.
        $this->get(route('catalog'), ['Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'])
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8');

        // Ranked preference: HTML wins over a lower-q Markdown.
        $this->get(route('catalog'), ['Accept' => 'text/html, text/markdown;q=0.5'])
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8');

        // Bare wildcard (curl/wget) → HTML: no silent substitution.
        $this->get(route('catalog'), ['Accept' => '*/*'])
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    public function test_html_pages_advertise_their_markdown_alternate(): void
    {
        $content = $this->get(route('catalog'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('<link rel="alternate" type="text/markdown" href="'.url('/catalog.md').'">', $content);
        $this->assertStringContainsString('/catalog.md', $content); // hidden agent hint

        // Auth-gated pages have no twin and must not advertise one.
        $user = User::factory()->create();
        $dashboard = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();
        $this->assertStringNotContainsString('rel="alternate" type="text/markdown"', $dashboard);
    }

    public function test_home_page_exposes_og_image_theme_color_and_site_search_action(): void
    {
        $content = $this->get(route('home'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('<meta property="og:image" content="'.url('/images/og-default.png').'"', $content);
        $this->assertStringContainsString('<meta name="theme-color" content="#222222">', $content);
        $this->assertStringContainsString('"@type": "SearchAction"', $content);
        $this->assertStringContainsString('q={search_term_string}', $content);
    }

    public function test_catalog_itemlist_wraps_full_course_entities(): void
    {
        Course::factory()->count(3)->published()->create();

        $content = $this->get(route('catalog'))->assertOk()->getContent();

        // The Course List rich result (and answer engines) expect ListItem →
        // Course nesting, not flat name/url entries.
        $this->assertStringContainsString('"@type": "Course"', $content);
        $this->assertSame(3, substr_count($content, '"@type": "Course"'));
        $this->assertStringContainsString('"@type": "ItemList"', $content);
    }

    public function test_course_page_jsonld_includes_url_and_breadcrumbs(): void
    {
        $course = Course::factory()->published()->create(['title' => 'آناتومی قفسه سینه']);

        $content = $this->get(route('courses.show', $course))->assertOk()->getContent();

        $this->assertStringContainsString('"@type": "Course"', $content);
        $this->assertStringContainsString('"url": "'.route('courses.show', $course).'"', $content);
        $this->assertStringContainsString('"@type": "BreadcrumbList"', $content);
        $this->assertStringContainsString('aria-label="مسیر صفحه"', $content); // visible mirror
    }
}

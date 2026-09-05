<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Course;
use App\Models\Subject;
use Illuminate\Http\Response;

/**
 * SEO/GEO endpoints. AI answer-engine crawlers (GPTBot, ClaudeBot,
 * PerplexityBot, …) are explicitly allowed for public, non-gated pages —
 * paywalled material stays behind auth regardless of robots.txt.
 */
class SeoController extends Controller
{
    public function robots(): Response
    {
        // Policy 2026-09-05 (client decision open — see DECISIONS.md): ALL
        // AI crawlers are welcome on public pages. The explicit Allow blocks
        // matter because several engines IGNORE a bare "User-agent: *" allow
        // for bots they recognize by name; unknown lines are ignored by
        // strict parsers, so adding them can never break existing crawlers.
        //
        // Distinction that actually drives citation traffic (2026 log
        // studies): RETRIEVAL agents fetch pages while answering user
        // queries — that is where citations happen — while TRAINING agents
        // crawl for model training. Both are allowed here today; if the
        // client later wants to opt out of training, only the training
        // agents (GPTBot, ClaudeBot, CCBot, Google-Extended) should be
        // flipped to Disallow.
        $lines = [
            'User-agent: *',
            // Cloudflare's Content-Signal convention (contentsignals.org):
            // usage permission, independent of the access rules below.
            'Content-Signal: search=yes, ai-input=yes, ai-train=yes',
            'Disallow: /admin',
            'Disallow: /dashboard',
            'Disallow: /checkout',
            'Disallow: /payments',
            'Disallow: /video-playback',
            '',
            '# Retrieval/search agents (fetch pages on user queries — citations)',
            'User-agent: OAI-SearchBot',
            'Allow: /',
            '',
            'User-agent: ChatGPT-User',
            'Allow: /',
            '',
            'User-agent: Claude-SearchBot',
            'Allow: /',
            '',
            'User-agent: Claude-User',
            'Allow: /',
            '',
            'User-agent: PerplexityBot',
            'Allow: /',
            '',
            'User-agent: Perplexity-User',
            'Allow: /',
            '',
            '# Training agents (currently allowed by client policy)',
            'User-agent: GPTBot',
            'Allow: /',
            '',
            'User-agent: ClaudeBot',
            'Allow: /',
            '',
            'User-agent: Google-Extended',
            'Allow: /',
            '',
            'Sitemap: '.route('seo.sitemap'),
        ];

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        $urls = [
            ['loc' => route('home'), 'priority' => '1.0'],
            ['loc' => route('catalog'), 'priority' => '0.9'],
            ['loc' => route('plans'), 'priority' => '0.8'],
            ['loc' => route('blog.index'), 'priority' => '0.7'],
            ['loc' => route('legal.show', 'terms'), 'priority' => '0.3'],
            ['loc' => route('legal.show', 'privacy'), 'priority' => '0.3'],
            ['loc' => route('legal.show', 'medical-disclaimer'), 'priority' => '0.3'],
            ['loc' => route('legal.show', 'contact'), 'priority' => '0.3'],
        ];

        Subject::query()->where('is_visible', true)->each(function (Subject $subject) use (&$urls): void {
            $urls[] = [
                'loc' => route('subjects.show', $subject),
                'priority' => '0.6',
                'lastmod' => optional($subject->updated_at)->toDateString(),
            ];
        });

        Course::query()->published()->each(function (Course $course) use (&$urls): void {
            $urls[] = ['loc' => route('courses.show', $course), 'priority' => '0.8', 'lastmod' => optional($course->updated_at)->toDateString()];
        });

        BlogPost::query()->published()->each(function (BlogPost $post) use (&$urls): void {
            $urls[] = ['loc' => route('blog.show', $post->slug), 'priority' => '0.7', 'lastmod' => optional($post->updated_at)->toDateString()];
        });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $url) {
            $xml .= '  <url>'."\n";
            $xml .= '    <loc>'.htmlspecialchars($url['loc'], ENT_XML1).'</loc>'."\n";
            if (! empty($url['lastmod'])) {
                $xml .= '    <lastmod>'.$url['lastmod'].'</lastmod>'."\n";
            }
            $xml .= '    <priority>'.$url['priority'].'</priority>'."\n";
            $xml .= '  </url>'."\n";
        }
        $xml .= '</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}

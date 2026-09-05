<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Course;
use App\Models\Subject;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * SEO/GEO endpoints. AI answer-engine crawlers (GPTBot, ClaudeBot,
 * PerplexityBot, …) are explicitly allowed for public, non-gated pages —
 * paywalled material stays behind auth regardless of robots.txt.
 */
class SeoController extends Controller
{
    public function robots(): Response
    {
        // Policy 2026-09-05, corrected in the Round-6 audit: per RFC 9309 §2.2.1
        // (and Google's robots.txt spec), a crawler obeys ONLY the most
        // specific matching User-agent group — named groups and the "*"
        // group are NEVER combined. Earlier revisions put the Disallow set
        // only in the "*" group, which meant every named agent below legally
        // ignored those rules. Every named group therefore repeats the full
        // rule set. The explicit Allow blocks matter because several engines
        // IGNORE a bare "User-agent: *" allow for bots they recognize by
        // name; unknown lines are ignored by strict parsers, so adding them
        // can never break existing crawlers.
        //
        // Distinction that actually drives citation traffic (2026 log
        // studies): RETRIEVAL agents fetch pages while answering user
        // queries — that is where citations happen — while TRAINING agents
        // crawl for model training. Both are allowed here today (client
        // decision 2026-09-05); if the client later wants to opt out of
        // training, flip only the training agents to a full-site Disallow.
        $protectedPaths = ['/admin', '/dashboard', '/checkout', '/payments', '/video-playback'];

        // Retrieval/search agents (fetch pages on user queries — citations).
        $retrievalAgents = [
            'OAI-SearchBot',
            'ChatGPT-User',
            'Claude-SearchBot',
            'Claude-User',
            'PerplexityBot',
            'Perplexity-User',
        ];

        // Training agents (currently allowed by client policy). CCBot,
        // Applebot-Extended and Meta-ExternalAgent are included per the same
        // allow-everything decision (2026-09-05).
        $trainingAgents = [
            'GPTBot',
            'ClaudeBot',
            'CCBot',
            'Google-Extended',
            'Applebot-Extended',
            'Meta-ExternalAgent',
        ];

        $lines = [
            'User-agent: *',
            // Cloudflare's Content-Signal convention (contentsignals.org):
            // usage permission, independent of the access rules below.
            'Content-Signal: search=yes, ai-input=yes, ai-train=yes',
        ];

        foreach ($protectedPaths as $path) {
            $lines[] = 'Disallow: '.$path;
        }

        foreach (['retrieval' => $retrievalAgents, 'training' => $trainingAgents] as $group => $agents) {
            foreach ($agents as $agent) {
                $lines[] = '';
                $lines[] = $group === 'retrieval'
                    ? '# Retrieval/search agents (fetch pages on user queries — citations)'
                    : '# Training agents (currently allowed by client policy)';
                $lines[] = 'User-agent: '.$agent;
                $lines[] = 'Content-Signal: search=yes, ai-input=yes, ai-train=yes';
                foreach ($protectedPaths as $path) {
                    $lines[] = 'Disallow: '.$path;
                }
                $lines[] = 'Allow: /';
            }
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.route('seo.sitemap');

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        // The sitemap walks every visible subject, published course and
        // published post on each hit. AI crawlers (GPTBot, ClaudeBot,
        // PerplexityBot, …) are explicitly invited above and poll this
        // aggressively, so an uncached build is a free DB-load amplifier on
        // shared hosting. Content changes are admin-driven and not
        // time-critical for crawlers; 1 hour is well inside every engine's
        // recrawl window.
        $xml = Cache::remember('seo.sitemap.xml', 3600, fn () => $this->buildSitemap());

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    private function buildSitemap(): string
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

        return $xml;
    }
}

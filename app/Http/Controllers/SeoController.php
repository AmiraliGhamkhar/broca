<?php

namespace App\Http\Controllers;

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
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /dashboard',
            'Disallow: /checkout',
            'Disallow: /payments',
            'Disallow: /video-playback',
            '',
            '# AI answer-engine crawlers are welcome on public pages',
            'User-agent: GPTBot',
            'Allow: /',
            '',
            'User-agent: ClaudeBot',
            'Allow: /',
            '',
            'User-agent: Claude-SearchBot',
            'Allow: /',
            '',
            'User-agent: PerplexityBot',
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
            $urls[] = ['loc' => route('subjects.show', $subject), 'priority' => '0.6'];
        });

        Course::query()->published()->each(function (Course $course) use (&$urls): void {
            $urls[] = ['loc' => route('courses.show', $course), 'priority' => '0.8', 'lastmod' => optional($course->updated_at)->toDateString()];
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

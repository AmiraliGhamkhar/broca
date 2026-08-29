<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Blog posts. Only slugs that exist (or are explicitly announced on the
 * index) resolve — previously ANY slug rendered the same static article,
 * which manufactured duplicate content for arbitrary URLs (audit finding,
 * 2026-08-29).
 */
class BlogController extends Controller
{
    /** The one fully written article. */
    public const CANONICAL_SLUG = 'broca-area-and-aphasia';

    /** Announced on the index but not yet written — render an honest coming-soon panel. */
    private const ANNOUNCED_SLUGS = [
        'ecg-electrophysiology',
        'sm2-spaced-repetition-medicine',
    ];

    public function index(): View
    {
        return view('blog.index');
    }

    public function show(string $slug): View
    {
        abort_unless($slug === self::CANONICAL_SLUG || in_array($slug, self::ANNOUNCED_SLUGS, true), 404);

        return view('blog.show', ['slug' => $slug]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        return view('blog.index', [
            'posts' => BlogPost::query()
                ->published()
                ->latest('published_at')
                ->latest('id')
                ->paginate(9),
        ]);
    }

    public function show(string $slug): View
    {
        $post = BlogPost::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('blog.show', ['post' => $post]);
    }
}

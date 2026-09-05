<?php

namespace App\Http\Controllers;

use App\Support\LegalContent;
use Illuminate\View\View;

class LegalController extends Controller
{
    public function show(string $page): View
    {
        // Headings + body copy live in LegalContent (shared with the
        // Markdown twin /{page}.md).
        abort_unless(LegalContent::document($page) !== null, 404);

        return view('legal.placeholder', ['page' => $page]);
    }
}

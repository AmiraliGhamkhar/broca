<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LegalController extends Controller
{
    public function show(string $page): View
    {
        $headings = ['terms' => 'شرایط استفاده', 'privacy' => 'حریم خصوصی', 'medical-disclaimer' => 'بیانیهٔ پزشکی', 'contact' => 'تماس با بروکا'];
        abort_unless(isset($headings[$page]), 404);

        return view('legal.placeholder', ['heading' => $headings[$page]]);
    }
}

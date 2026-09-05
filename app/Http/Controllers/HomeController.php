<?php

namespace App\Http\Controllers;

use App\Models\Contributor;
use Illuminate\View\View;

/**
 * Landing page.
 *
 * The faculty section is DB-driven on purpose. It previously rendered a
 * hardcoded array of four invented doctors with invented credentials, which
 * SPEC.md §"No placeholder may be presented as a real medical credential"
 * forbids outright — on a medical-education product that is a trust and
 * liability exposure, not a cosmetic placeholder.
 *
 * Now the section renders only real, visible `contributors` rows, and the
 * view hides the whole block when none exist. An empty site therefore says
 * nothing rather than saying something false.
 */
class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('welcome', [
            'faculty' => Contributor::query()
                ->where('is_visible', true)
                // Only contributors actually attached to published material
                // may be presented as faculty — being in the table is not a
                // claim of involvement.
                ->where(fn ($query) => $query
                    ->whereHas('authoredCourses', fn ($q) => $q->published())
                    ->orWhereHas('reviewedCourses', fn ($q) => $q->published()))
                ->orderBy('name')
                ->limit(4)
                ->get(['id', 'name', 'credentials', 'specialty', 'bio']),
        ]);
    }
}

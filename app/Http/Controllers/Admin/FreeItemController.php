<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FreeItemDesignationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class FreeItemController extends Controller
{
    public function update(Request $request, string $type, int $id, FreeItemDesignationService $service): RedirectResponse
    {
        $data = $request->validate(['designated' => ['required', 'boolean']]);
        $tables = ['videos' => \App\Models\Video::class, 'notes' => \App\Models\Note::class, 'flashcards' => \App\Models\Flashcard::class, 'quiz_questions' => \App\Models\QuizQuestion::class];
        abort_unless(isset($tables[$type]), 404);
        $item = $tables[$type]::query()->findOrFail($id);
        try {
            $service->set($item, (bool) $data['designated']);
            return back()->with('status', 'وضعیت محتوای رایگان به‌روزرسانی شد.');
        } catch (RuntimeException $exception) {
            return back()->withErrors(['free_item' => $exception->getMessage()]);
        }
    }
}

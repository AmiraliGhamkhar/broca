<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Note;
use App\Policies\ContentPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NoteController extends Controller
{
    public function show(Request $request, Course $course, Note $note): View
    {
        abort_unless($note->course_id === $course->id && $this->published($course, $note), 404);
        abort_unless(app(ContentPolicy::class)->viewNote($request->user(), $note), 403);

        $note->load(['author', 'reviewer']);

        return view('learner.note', compact('course', 'note'));
    }

    public function download(Request $request, Note $note): StreamedResponse
    {
        abort_unless(app(ContentPolicy::class)->viewNote($request->user(), $note), 403);

        $disk = Storage::disk($note->storage_disk);
        abort_unless($disk->exists($note->storage_key), 404);

        $extension = match ($note->mime_type) {
            'application/pdf' => '.pdf',
            'text/plain' => '.txt',
            default => '',
        };
        $filename = preg_replace('/[^\\p{L}\\p{N}._-]+/u', '-', $note->title) ?: 'note';
        $filename = trim($filename, '.-') ?: 'note';

        return $disk->download($note->storage_key, $filename.$extension, array_filter([
            'Content-Type' => $note->mime_type,
        ]));
    }

    private function published(Course $course, Note $note): bool
    {
        return $course->status === 'published'
            && $course->published_at?->isPast()
            && $note->status === 'published'
            && $note->published_at?->isPast();
    }
}

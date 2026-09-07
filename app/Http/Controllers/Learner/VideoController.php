<?php

namespace App\Http\Controllers\Learner;

use App\Contracts\VideoProvider;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Video;
use App\Models\VideoProgress;
use App\Policies\ContentPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VideoController extends Controller
{
    public function show(Request $request, Course $course, Video $video): View
    {
        abort_unless($video->course_id === $course->id && $this->published($course, $video), 404);

        $canPlay = app(ContentPolicy::class)->viewVideo($request->user(), $video);
        $threshold = $video->completion_threshold_percent ?: config('broca.video_completion_threshold');

        // Hydrate the player with the learner's existing progress so a
        // revisit resumes from where they stopped instead of zero.
        $progress = VideoProgress::query()
            ->where('user_id', $request->user()->id)
            ->where('video_id', $video->id)
            ->first();

        return view('learner.video', [
            'course' => $course,
            'video' => $video,
            'canPlay' => $canPlay,
            'threshold' => $threshold,
            'initialPercent' => (int) ($progress->watched_percent ?? 0),
            'initialCompleted' => (bool) $progress?->completed_at,
        ]);
    }

    /**
     * Short-lived playback authorization. Returns a signed media URL; the
     * media route re-checks entitlement before streaming.
     */
    public function playback(Request $request, Video $video, VideoProvider $provider): JsonResponse
    {
        abort_unless($video->isPublished(), 404);
        abort_unless(app(ContentPolicy::class)->viewVideo($request->user(), $video), 403);

        try {
            $playback = $provider->authorize($video);
        } catch (\RuntimeException) {
            abort(404);
        }

        return response()->json($playback);
    }

    /**
     * Streams the video's own placeholder asset behind a signed URL.
     * Entitlement is re-checked server-side; the signature itself carries
     * the expiry. The stored manifest_reference is a bare filename — the
     * charset check plus realpath containment stop any traversal attempt.
     */
    public function media(Request $request, string $videoId): BinaryFileResponse
    {
        // No implicit model binding on this route: the `signed` middleware
        // must reject bad signatures with 403 BEFORE any resource resolution,
        // otherwise tampered URLs would 404 at binding and leak whether a
        // video id exists behind the signature.
        $video = Video::query()->findOrFail($videoId);

        abort_unless($video->isPublished(), 404);
        abort_unless(app(ContentPolicy::class)->viewVideo($request->user(), $video), 403);

        $reference = (string) $video->manifest_reference;

        // Bare filename only: no slashes, no backslashes, no NUL, no '..'.
        if ($reference === '' || ! preg_match('/^[A-Za-z0-9._-]+$/', $reference)) {
            abort(404);
        }

        // Bytes live on the PRIVATE disk (storage/app/private/videos),
        // outside the public docroot — a docroot copy is statically served
        // with zero auth and bypasses this whole signed+entitlement chain
        // (audit 2026-09-07).
        $videosRoot = Storage::disk('local')->path('videos');
        $videosDir = realpath($videosRoot);
        $path = realpath($videosRoot.DIRECTORY_SEPARATOR.$reference);

        // realpath() resolves symlinks and '..' — the resolved file must
        // still live inside the videos directory.
        if ($videosDir === false || $path === false || ! str_starts_with($path, $videosDir.DIRECTORY_SEPARATOR)) {
            abort(404, 'Playback asset is not configured yet.');
        }

        // The signed URL lives 300s and the entitlement re-check runs on
        // every server request, so caching the bytes just under that window
        // is safe and lets the player range-seek/replay from the local cache
        // instead of re-streaming (no-store forced a full re-download on
        // every reload).
        return response()->file($path, ['Cache-Control' => 'private, max-age=290']);
    }

    public function progress(Request $request, Video $video): JsonResponse
    {
        abort_unless(app(ContentPolicy::class)->viewVideo($request->user(), $video), 403);
        $data = $request->validate(['watched_seconds' => ['required', 'integer', 'min:0']]);
        $duration = (int) $video->duration_seconds;
        $seconds = $duration > 0 ? min($data['watched_seconds'], $duration) : $data['watched_seconds'];
        $percent = $duration > 0 ? min(100, (int) floor($seconds / $duration * 100)) : 0;
        $threshold = $video->completion_threshold_percent ?: config('broca.video_completion_threshold');

        $progress = \DB::transaction(function () use ($request, $video, $seconds, $percent, $threshold): VideoProgress {
            $progress = VideoProgress::query()->where('user_id', $request->user()->id)->where('video_id', $video->id)->lockForUpdate()->first();
            if (! $progress) {
                try {
                    $progress = VideoProgress::create(['user_id' => $request->user()->id, 'video_id' => $video->id]);
                } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                    // Lost a first-insert race — re-read the winner's row.
                    $progress = VideoProgress::query()->where('user_id', $request->user()->id)->where('video_id', $video->id)->lockForUpdate()->firstOrFail();
                }
            }
            $progress->watched_seconds = max((int) $progress->watched_seconds, $seconds);
            $progress->watched_percent = max((int) $progress->watched_percent, $percent);
            $progress->last_watched_at = now();
            if ($progress->watched_percent >= $threshold && ! $progress->completed_at) {
                $progress->completed_at = now();
            }
            $progress->save();

            return $progress;
        });

        return response()->json(['completed' => (bool) $progress->completed_at, 'watched_percent' => $progress->watched_percent]);
    }

    private function published(Course $course, Video $video): bool
    {
        return $course->status === 'published' && $course->published_at?->isPast() && $video->isPublished();
    }
}

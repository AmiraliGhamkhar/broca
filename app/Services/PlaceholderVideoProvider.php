<?php

namespace App\Services;

use App\Contracts\VideoProvider;
use App\Models\Video;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class PlaceholderVideoProvider implements VideoProvider
{
    public function authorize(Video $video): array
    {
        if (! $video->manifest_reference) {
            throw new RuntimeException('مرجع پخش ویدیو ثبت نشده است.');
        }

        $expiresAt = now()->addMinutes(5);
        $url = URL::temporarySignedRoute('videos.manifest.placeholder', $expiresAt, [
            'video' => $video->getKey(),
            'reference' => hash_hmac('sha256', $video->manifest_reference, (string) config('app.key')),
        ]);

        return ['playback_url' => $url, 'expires_at' => $expiresAt->toIso8601String()];
    }
}

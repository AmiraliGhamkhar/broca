<?php

namespace App\Services;

use App\Contracts\VideoProvider;
use App\Models\Video;
use Illuminate\Support\Facades\URL;
use RuntimeException;

/**
 * Provider-neutral playback boundary. Returns a short-lived SIGNED media URL;
 * the signed URL carries the expiry, and the media route re-checks entitlement
 * server-side before streaming anything.
 *
 * When a real VOD/CDN provider is wired in, replace this binding with an
 * adapter that converts the stored provider-neutral reference into the
 * provider's signed playback URL — the controller and views do not change.
 */
class PlaceholderVideoProvider implements VideoProvider
{
    public function authorize(Video $video): array
    {
        if ($video->playback_provider === 'external' && filter_var($video->playback_asset_id, FILTER_VALIDATE_URL)) {
            return [
                'playback_url' => $video->playback_asset_id,
                'expires_at' => now()->addMinutes(5)->toIso8601String(),
            ];
        }

        if (! $video->manifest_reference) {
            throw new RuntimeException('مرجع پخش ویدیو ثبت نشده است.');
        }

        $expiresAt = now()->addMinutes(5);
        $url = URL::temporarySignedRoute('videos.media', $expiresAt, [
            'video' => $video->getKey(),
        ]);

        return [
            'playback_url' => $url,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }
}

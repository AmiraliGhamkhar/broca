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
 * External URLs are additionally checked against the configured origin
 * allowlist (BROCA_EXTERNAL_VIDEO_ORIGINS) — save-time validation can drift;
 * authorize() is the last line before the URL reaches a player.
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
            $this->assertAllowedOrigin((string) $video->playback_asset_id);

            // An external link does not expire — reporting a fictional
            // expiry (previous behaviour) misleads both the UI copy and the
            // operator into believing the link rotates.
            return [
                'playback_url' => (string) $video->playback_asset_id,
                'expires_at' => null,
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

    private function assertAllowedOrigin(string $url): void
    {
        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?: ''));

        $allowed = collect(config('broca.external_video_origins', []))
            ->filter(fn ($origin) => is_string($origin) && $origin !== '')
            ->map(fn (string $origin) => strtolower((string) (parse_url($origin, PHP_URL_HOST) ?: $origin)))
            ->all();

        if ($allowed === [] || $host === '' || ! in_array($host, $allowed, true)) {
            // Fail closed: caught by VideoController::playback → 404.
            throw new RuntimeException('دامنهٔ پخش خارجی مجاز نیست.');
        }
    }
}

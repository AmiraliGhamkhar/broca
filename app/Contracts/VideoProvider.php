<?php

namespace App\Contracts;

use App\Models\Video;

interface VideoProvider
{
    /**
     * @return array{playback_url: string, expires_at: string|null}
     */
    public function authorize(Video $video): array;
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['logo_image_path', 'hero_image_path', 'hero_image_alt'])]
class SiteSetting extends Model
{
    public static function current(): self
    {
        return self::query()->firstOrCreate(['id' => 1], [
            'hero_image_alt' => 'ماکت آموزشی قلب روی پایهٔ سفید، در نور موزه‌ای',
        ]);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_image_path ?: null;
    }

    public function heroUrl(): string
    {
        return $this->hero_image_path ?: '/images/hero/hero.webp';
    }
}

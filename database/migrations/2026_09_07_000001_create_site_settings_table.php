<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('logo_image_path')->nullable();
            $table->string('hero_image_path')->nullable();
            $table->string('hero_image_alt', 255)->default('تصویر معرفی آکادمی بروکا');
            $table->timestamps();
        });

        DB::table('site_settings')->insert([
            'id' => 1,
            'hero_image_alt' => 'ماکت آموزشی قلب روی پایهٔ سفید، در نور موزه‌ای',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};

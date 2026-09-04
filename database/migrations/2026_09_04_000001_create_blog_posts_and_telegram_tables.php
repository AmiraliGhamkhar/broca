<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category', 120)->nullable();
            $table->string('author_name', 120)->nullable();
            $table->string('reviewer_name', 120)->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('cover_image_path')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->string('meta_title', 255)->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });

        Schema::create('telegram_admins', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('telegram_user_id')->unique();
            $table->string('username')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('telegram_chat_sessions', function (Blueprint $table): void {
            $table->id();
            $table->bigInteger('telegram_chat_id')->unique();
            $table->unsignedBigInteger('telegram_user_id');
            $table->string('workflow', 100)->nullable();
            $table->json('context')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['telegram_user_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_chat_sessions');
        Schema::dropIfExists('telegram_admins');
        Schema::dropIfExists('blog_posts');
    }
};

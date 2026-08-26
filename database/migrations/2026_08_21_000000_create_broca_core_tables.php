<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_consents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('terms_version', 40);
            $table->string('privacy_version', 40);
            $table->string('medical_disclaimer_version', 40);
            $table->timestamp('accepted_at');
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('contributors', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('credentials');
            $table->string('specialty')->nullable();
            $table->text('bio')->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('is_visible')->default(true)->index();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('subjects', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true)->index();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('description')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('contributors')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('contributors')->nullOnDelete();
            $table->string('level', 30)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['subject_id', 'status']);
            $table->index(['status', 'published_at']);
        });

        Schema::create('course_tag', function (Blueprint $table): void {
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['course_id', 'tag_id']);
        });

        Schema::create('videos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('playback_provider')->nullable();
            $table->string('playback_asset_id')->nullable();
            $table->string('manifest_reference')->nullable();
            $table->unsignedTinyInteger('completion_threshold_percent')->nullable();
            $table->boolean('is_free_designated')->default(false)->index();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('contributors')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('contributors')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['course_id', 'slug']);
        });

        Schema::create('notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('storage_disk')->default('local');
            $table->string('storage_key');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('checksum')->nullable();
            $table->boolean('is_free_designated')->default(false)->index();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('contributors')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('contributors')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['course_id', 'slug']);
        });

        Schema::create('flashcard_decks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('contributors')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('contributors')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['course_id', 'slug']);
        });

        Schema::create('flashcards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('flashcard_deck_id')->constrained()->cascadeOnDelete();
            $table->longText('front');
            $table->longText('back');
            $table->text('hint')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_free_designated')->default(false)->index();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('course_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->timestamp('enrolled_at');
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
            $table->unique(['user_id', 'course_id']);
        });

        Schema::create('video_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('video_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('watched_seconds')->default(0);
            $table->unsignedTinyInteger('watched_percent')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_watched_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'video_id']);
        });

        Schema::create('quizzes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('pass_threshold_percent')->nullable();
            $table->boolean('is_free_designated')->default(false);
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('contributors')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('contributors')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['course_id', 'slug']);
        });

        Schema::create('quiz_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->longText('prompt');
            $table->longText('explanation')->nullable();
            $table->text('source_citation')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('contributors')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('contributors')->nullOnDelete();
            $table->boolean('is_free_designated')->default(false)->index();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('quiz_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quiz_question_id')->constrained()->cascadeOnDelete();
            $table->text('label');
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('user_flashcard_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flashcard_id')->constrained()->cascadeOnDelete();
            $table->string('state', 20)->default('new');
            $table->decimal('ease_factor', 4, 2)->default(2.50);
            $table->unsignedInteger('interval_days')->default(0);
            $table->unsignedInteger('repetition_count')->default(0);
            $table->timestamp('due_at')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'flashcard_id']);
        });

        Schema::create('flashcard_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flashcard_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_id')->constrained('user_flashcard_schedules')->cascadeOnDelete();
            $table->unsignedTinyInteger('quality');
            $table->unsignedInteger('previous_interval_days');
            $table->unsignedInteger('new_interval_days');
            $table->decimal('previous_ease_factor', 4, 2);
            $table->decimal('new_ease_factor', 4, 2);
            $table->timestamp('reviewed_at');
            $table->timestamps();
        });

        Schema::create('quiz_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score_percent');
            $table->unsignedInteger('correct_count');
            $table->unsignedInteger('question_count');
            $table->boolean('passed');
            $table->timestamp('started_at');
            $table->timestamp('submitted_at');
            $table->timestamps();
            $table->index(['user_id', 'quiz_id']);
        });

        Schema::create('quiz_attempt_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quiz_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('selected_option_id')->nullable()->constrained('quiz_options')->nullOnDelete();
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
            $table->unique(['quiz_attempt_id', 'quiz_question_id']);
        });

        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedTinyInteger('duration_months')->default(0);
            $table->unsignedBigInteger('price_irr')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->string('gateway')->nullable();
            $table->string('gateway_reference')->nullable()->unique();
            $table->timestamps();
            $table->index(['user_id', 'status', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('quiz_attempt_answers');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('flashcard_reviews');
        Schema::dropIfExists('user_flashcard_schedules');
        Schema::dropIfExists('quiz_options');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('quizzes');
        Schema::dropIfExists('video_progress');
        Schema::dropIfExists('course_enrollments');
        Schema::dropIfExists('flashcards');
        Schema::dropIfExists('flashcard_decks');
        Schema::dropIfExists('notes');
        Schema::dropIfExists('videos');
        Schema::dropIfExists('course_tag');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('contributors');
        Schema::dropIfExists('user_consents');
    }
};

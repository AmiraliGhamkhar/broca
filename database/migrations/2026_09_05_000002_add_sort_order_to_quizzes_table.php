<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `quizzes.sort_order` was omitted from the core-tables migration while every
 * sibling content table (subjects, courses, videos, notes, decks, flashcards,
 * quiz_questions, quiz_options) defines it.
 *
 * The omission is a live bug, not a cosmetic gap:
 *  - Admin\QuizController validates `sort_order` on store/update, writes it
 *    when reordering questions, and calls ->orderBy('sort_order') when
 *    listing quizzes — that ORDER BY throws SQLSTATE[42S22] on MySQL.
 *  - DatabaseSeeder passes 'sort_order' to Quiz::create(), so a fresh
 *    `php artisan db:seed` aborts partway through, leaving a half-populated
 *    database.
 *
 * Backfilled by id so existing rows get a stable, deterministic order rather
 * than all collapsing to 0.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('quizzes', 'sort_order')) {
            return;
        }

        Schema::table('quizzes', function (Blueprint $table): void {
            $table->unsignedInteger('sort_order')->default(0)->after('description');
        });

        // Deterministic per-course ordering for rows that predate the column.
        foreach (\Illuminate\Support\Facades\DB::table('quizzes')->select('course_id')->distinct()->pluck('course_id') as $courseId) {
            $ids = \Illuminate\Support\Facades\DB::table('quizzes')
                ->where('course_id', $courseId)
                ->orderBy('id')
                ->pluck('id');

            foreach ($ids as $index => $id) {
                \Illuminate\Support\Facades\DB::table('quizzes')
                    ->where('id', $id)
                    ->update(['sort_order' => $index + 1]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('quizzes', 'sort_order')) {
            return;
        }

        Schema::table('quizzes', function (Blueprint $table): void {
            $table->dropColumn('sort_order');
        });
    }
};

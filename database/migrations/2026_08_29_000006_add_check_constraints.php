<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * DB-level status enums. The application already validates these values
     * in controllers, but a DB CHECK is the last line of defense against
     * drift (direct SQL, future code paths, bulk imports) — audit finding,
     * 2026-08-29. Values mirror exactly what the models/controllers write.
     *
     * MySQL 8 only: SQLite (test runner) has no ALTER TABLE ... ADD
     * CONSTRAINT, so the constraints are skipped on non-MySQL drivers.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $contentStatus = "'draft', 'in_review', 'published', 'archived'";

        $checks = [
            'users' => ["status IN ('active', 'suspended')"],
            'courses' => ["status IN ($contentStatus)"],
            'videos' => ["status IN ($contentStatus)"],
            'notes' => ["status IN ($contentStatus)"],
            'flashcard_decks' => ["status IN ($contentStatus)"],
            'flashcards' => ["status IN ($contentStatus)"],
            'quizzes' => ["status IN ($contentStatus)"],
            'quiz_questions' => ["status IN ($contentStatus)"],
            'subscriptions' => ["status IN ('active', 'scheduled', 'expired', 'cancelled')"],
            'invoices' => [
                "status IN ('pending', 'initiated', 'paid', 'failed', 'cancelled', 'expired')",
                'amount_irr >= 0',
            ],
        ];

        foreach ($checks as $table => $expressions) {
            foreach ($expressions as $index => $expression) {
                $name = "chk_{$table}_".($index === 0 ? 'status' : 'amount');
                DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$name}` CHECK ({$expression})");
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $names = [
            'users' => 'chk_users_status',
            'courses' => 'chk_courses_status',
            'videos' => 'chk_videos_status',
            'notes' => 'chk_notes_status',
            'flashcard_decks' => 'chk_flashcard_decks_status',
            'flashcards' => 'chk_flashcards_status',
            'quizzes' => 'chk_quizzes_status',
            'quiz_questions' => 'chk_quiz_questions_status',
            'subscriptions' => 'chk_subscriptions_status',
            'invoices' => ['chk_invoices_status', 'chk_invoices_amount'],
        ];

        foreach ($names as $table => $constraints) {
            foreach ((array) $constraints as $constraint) {
                DB::statement("ALTER TABLE `{$table}` DROP CHECK `{$constraint}`");
            }
        }
    }
};

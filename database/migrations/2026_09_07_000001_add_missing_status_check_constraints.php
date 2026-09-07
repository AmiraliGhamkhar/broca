<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Closes the gap left by 2026_08_29_000006_add_check_constraints: the batch
 * covered every content/financial status column but skipped
 * course_enrollments.status and user_flashcard_schedules.state, which stayed
 * free-text. Values mirror exactly what the code writes
 * (EnrollmentController 'active', SrsService 'new'/'learning'/'review',
 * User::isEnrolledIn filters status='active').
 *
 * MySQL 8 only — SQLite (test runner) cannot ALTER TABLE ... ADD CONSTRAINT.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $checks = [
            'course_enrollments' => [
                'chk_course_enrollments_status' => "status IN ('active', 'cancelled')",
            ],
            'user_flashcard_schedules' => [
                'chk_user_flashcard_schedules_state' => "state IN ('new', 'learning', 'review')",
            ],
        ];

        foreach ($checks as $table => $constraints) {
            foreach ($constraints as $name => $expression) {
                $exists = DB::selectOne(
                    'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
                     WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME = ?
                       AND CONSTRAINT_NAME = ?',
                    [$table, $name]
                );

                if (! $exists) {
                    DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$name}` CHECK ({$expression})");
                }
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE `course_enrollments` DROP CHECK `chk_course_enrollments_status`');
        DB::statement('ALTER TABLE `user_flashcard_schedules` DROP CHECK `chk_user_flashcard_schedules_state`');
    }
};

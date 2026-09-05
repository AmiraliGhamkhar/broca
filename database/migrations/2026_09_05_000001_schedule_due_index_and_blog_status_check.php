<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Two targeted schema fixes found in the 2026-09-05 audit:
     *
     * 1. user_flashcard_schedules is scanned by (user_id, due_at <= now())
     *    ORDER BY due_at LIMIT 10 on the learner dashboard. The implicit
     *    single-column FK index on user_id filters the first predicate, but
     *    the due_at range + sort still needs a secondary sort. The composite
     *    index serves both in one pass.
     *
     * 2. blog_posts was added after the 2026-08-29 CHECK-constraint batch, so
     *    its status column is not enforced at the DB level like every other
     *    content table. Same guard, same values, MySQL 8 only (the test
     *    runner is SQLite, which cannot ALTER TABLE ... ADD CONSTRAINT).
     */
    public function up(): void
    {
        if (! Schema::hasIndex('user_flashcard_schedules', 'user_flashcard_schedules_user_id_due_at_index')) {
            Schema::table('user_flashcard_schedules', function (Blueprint $table): void {
                $table->index(['user_id', 'due_at'], 'user_flashcard_schedules_user_id_due_at_index');
            });
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $statusCheck = "status IN ('draft', 'in_review', 'published', 'archived')";

        if (! DB::selectOne("SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'blog_posts'
              AND CONSTRAINT_NAME = 'chk_blog_posts_status'")) {
            DB::statement("ALTER TABLE `blog_posts` ADD CONSTRAINT `chk_blog_posts_status` CHECK ({$statusCheck})");
        }
    }

    public function down(): void
    {
        Schema::table('user_flashcard_schedules', function (Blueprint $table): void {
            $table->dropIndex('user_flashcard_schedules_user_id_due_at_index');
        });

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE `blog_posts` DROP CHECK `chk_blog_posts_status`');
    }
};

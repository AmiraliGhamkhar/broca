<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('free_item_quotas')) {
            Schema::create('free_item_quotas', function (Blueprint $table): void {
                $table->id();
                $table->string('content_type')->unique();
                $table->timestamps();
            });
        }
        foreach (['videos', 'notes', 'flashcards', 'quiz_questions'] as $type) {
            DB::table('free_item_quotas')->insertOrIgnore(['content_type' => $type, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('free_item_quotas');
    }
};

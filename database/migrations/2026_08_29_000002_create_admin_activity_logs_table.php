<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Created directly in the final shape intended by
     * 2026_08_26_000004_preserve_financial_and_audit_records (nullable
     * user_id with SET NULL + actor snapshots): that migration runs BEFORE
     * this table exists on a fresh database and skips it, so it must not
     * rely on a later ALTER pass.
     */
    public function up(): void
    {
        Schema::create('admin_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_name_snapshot')->nullable();
            $table->string('actor_email_snapshot')->nullable();
            $table->string('action', 255);
            $table->string('route_name', 255)->nullable();
            $table->string('method', 10);
            $table->string('url', 500);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 1000)->nullable();
            $table->json('payload')->nullable();
            $table->unsignedSmallInteger('status_code')->default(200);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_activity_logs');
    }
};

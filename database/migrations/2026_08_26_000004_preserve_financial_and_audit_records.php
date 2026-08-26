<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['invoices', 'subscriptions', 'admin_activity_logs'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropForeign(['user_id']);
                $table->foreignId('user_id')->nullable()->change();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        Schema::table('invoices', function (Blueprint $table): void {
            $table->string('user_name_snapshot')->nullable()->after('user_id');
            $table->string('user_email_snapshot')->nullable()->after('user_name_snapshot');
            $table->string('user_phone_snapshot', 20)->nullable()->after('user_email_snapshot');
        });

        Schema::table('admin_activity_logs', function (Blueprint $table): void {
            $table->string('actor_name_snapshot')->nullable()->after('user_id');
            $table->string('actor_email_snapshot')->nullable()->after('actor_name_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn(['user_name_snapshot', 'user_email_snapshot', 'user_phone_snapshot']);
        });

        Schema::table('admin_activity_logs', function (Blueprint $table): void {
            $table->dropColumn(['actor_name_snapshot', 'actor_email_snapshot']);
        });

        foreach (['invoices', 'subscriptions', 'admin_activity_logs'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropForeign(['user_id']);
                $table->foreignId('user_id')->nullable(false)->change();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }
};

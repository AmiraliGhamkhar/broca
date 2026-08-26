<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            // The hourly stale-invoice sweep filters on (status, expires_at).
            $table->index(['status', 'expires_at'], 'invoices_status_expires_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropIndex('invoices_status_expires_at_index');
        });
    }
};

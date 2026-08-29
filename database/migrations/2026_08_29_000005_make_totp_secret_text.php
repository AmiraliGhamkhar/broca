<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * totp_secret now uses Laravel's 'encrypted' cast, which persists a
     * ~200-char JSON payload — the original string(255) column would store
     * it, but text is the honest type for ciphertext (audit finding,
     * 2026-08-29).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('totp_secret')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('totp_secret')->nullable()->change();
        });
    }
};

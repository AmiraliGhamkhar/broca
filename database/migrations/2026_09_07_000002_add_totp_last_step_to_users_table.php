<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TOTP replay guard: stores the time-step whose code last passed the login
 * challenge for this admin. A code captured in the ±1 step drift window
 * (≤90s) can no longer be replayed — any step <= the recorded one is
 * rejected (see Totp::verifyStep and TwoFactorController::verify).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('totp_last_step')->nullable()->after('recovery_codes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('totp_last_step');
        });
    }
};

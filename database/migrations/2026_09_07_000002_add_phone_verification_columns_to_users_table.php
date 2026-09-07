<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mobile verification state, alongside the existing email_verified_at.
 *
 * Nothing backfills: every existing account keeps its current verification
 * state (an account that verified by email stays verified — see
 * App\Http\Middleware\EnsureVerifiedContact, which accepts EITHER channel).
 * New signups get a code on their number and can activate from the phone when
 * the email never lands.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
            // bcrypt hash of the live one-time code; cleared on use, expiry
            // and lockout. Null means "no code outstanding".
            $table->string('phone_verification_code')->nullable()->after('phone_verified_at');
            $table->timestamp('phone_verification_expires_at')->nullable()->after('phone_verification_code');
            $table->unsignedSmallInteger('phone_verification_attempts')->default(0)->after('phone_verification_expires_at');
            $table->timestamp('phone_verification_last_sent_at')->nullable()->after('phone_verification_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'phone_verified_at',
                'phone_verification_code',
                'phone_verification_expires_at',
                'phone_verification_attempts',
                'phone_verification_last_sent_at',
            ]);
        });
    }
};

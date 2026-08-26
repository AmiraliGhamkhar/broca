<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Invoices are the financial record created before a user is sent to the
     * payment gateway. Amounts are integers in the smallest currency unit
     * (Rial/IRR) — never floats.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('number', 40)->unique();
            $table->unsignedBigInteger('amount_irr');
            $table->string('currency', 3)->default('IRR');
            $table->string('status', 20)->default('pending')->index();
            $table->string('gateway', 30)->nullable();
            $table->string('gateway_payment_id')->nullable();
            $table->string('authority', 64)->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};

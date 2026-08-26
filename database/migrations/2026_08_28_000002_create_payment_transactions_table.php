<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('gateway');
            $table->json('request_payload');
            $table->json('response_payload');
            $table->string('reference_number')->nullable();
            $table->enum('status', ['initiated','verified','failed','duplicate'])->default('initiated');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->unique(['gateway', 'reference_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};

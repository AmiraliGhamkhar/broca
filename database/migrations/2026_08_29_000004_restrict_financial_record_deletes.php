<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Financial records must never vanish with their parent: the payment
     * transaction ledger and the invoice→subscription link are forensic
     * evidence. Original migrations created these FKs with cascadeOnDelete;
     * restrict them so an accidental invoice delete fails loudly instead of
     * silently destroying money records (audit finding, 2026-08-29).
     */
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table): void {
            $table->dropForeign(['invoice_id']);
            $table->foreign('invoice_id')->references('id')->on('invoices')->restrictOnDelete();
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropForeign(['invoice_id']);
            $table->foreign('invoice_id')->references('id')->on('invoices')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table): void {
            $table->dropForeign(['invoice_id']);
            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropForeign(['invoice_id']);
            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
        });
    }
};

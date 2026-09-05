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
     *
     * Drop and re-add are split into separate Schema::table calls so the
     * SQLite table-rebuild emulation (used in local dev and the test suite)
     * never has to mix two FK operations in a single blueprint.
     */
    public function up(): void
    {
        foreach (['payment_transactions', 'subscriptions'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropForeign(['invoice_id']);
            });

            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreign('invoice_id')->references('id')->on('invoices')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['payment_transactions', 'subscriptions'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropForeign(['invoice_id']);
            });

            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
            });
        }
    }
};

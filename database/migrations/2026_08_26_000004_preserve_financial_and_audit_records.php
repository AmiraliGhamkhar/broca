<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Keep financial and audit records when their user is deleted:
     * user_id becomes nullable with ON DELETE SET NULL, and the rows keep
     * name/email/phone snapshots of the user at the time of the action.
     *
     * This migration is written defensively for two reasons:
     *
     * 1. `admin_activity_logs` was only created by a LATER migration
     *    (2026_08_29_000002), so on a fresh database that table does not
     *    exist yet when this migration runs. It must be skipped here and is
     *    created directly in its final shape by that migration.
     *
     * 2. The previous (non-idempotent) version of this migration could die
     *    halfway through on MySQL, leaving invoices/subscriptions already
     *    converted. Every step is guarded so re-running it from any
     *    partially-applied state converges on the same final schema.
     *
     * Each schema change also lives in its own Schema::table call: SQLite
     * (local dev and the test suite) emulates ALTERs by rebuilding the
     * table, and combining unrelated changes in one blueprint corrupts the
     * rebuild.
     */
    public function up(): void
    {
        foreach (['invoices', 'subscriptions', 'admin_activity_logs'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue; // created by a later migration with its final schema
            }

            $this->ensureNullableUserRelation($tableName);
        }

        foreach ([
            'invoices' => ['user_name_snapshot', 'user_email_snapshot', 'user_phone_snapshot'],
            'admin_activity_logs' => ['actor_name_snapshot', 'actor_email_snapshot'],
        ] as $tableName => $columns) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $missing = array_filter($columns, fn (string $column): bool => ! Schema::hasColumn($tableName, $column));

            if ($missing !== []) {
                Schema::table($tableName, function (Blueprint $table) use ($missing): void {
                    foreach ($missing as $column) {
                        $table->string($column)->nullable();
                    }
                });
            }
        }
    }

    public function down(): void
    {
        foreach ([
            'invoices' => ['user_name_snapshot', 'user_email_snapshot', 'user_phone_snapshot'],
            'admin_activity_logs' => ['actor_name_snapshot', 'actor_email_snapshot'],
        ] as $tableName => $columns) {
            if (Schema::hasTable($tableName)) {
                $present = array_filter($columns, fn (string $column): bool => Schema::hasColumn($tableName, $column));

                if ($present !== []) {
                    Schema::table($tableName, function (Blueprint $table) use ($present): void {
                        $table->dropColumn(array_values($present));
                    });
                }
            }
        }

        foreach (['invoices', 'subscriptions', 'admin_activity_logs'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $this->ensureCascadingUserRelation($tableName);
        }
    }

    /**
     * Convert user_id to nullable + ON DELETE SET NULL, idempotently.
     */
    private function ensureNullableUserRelation(string $tableName): void
    {
        $foreignKey = $this->userIdForeignKey($tableName);

        if ($foreignKey !== null && ($foreignKey['on_delete'] ?? null) !== 'SET NULL') {
            Schema::table($tableName, function (Blueprint $table): void {
                // Column-form drop: SQLite can only drop FKs identified by
                // their columns (it rebuilds the table), MySQL resolves the
                // conventional name from them.
                $table->dropForeign(['user_id']);
            });

            $foreignKey = null;
        }

        $userId = collect(Schema::getColumns($tableName))->first(fn (array $column): bool => $column['name'] === 'user_id');

        if ($userId !== null && ! $userId['nullable']) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('user_id')->nullable()->change();
            });
        }

        if ($foreignKey === null) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    /**
     * Restore the original cascade behaviour, idempotently.
     */
    private function ensureCascadingUserRelation(string $tableName): void
    {
        $foreignKey = $this->userIdForeignKey($tableName);

        if ($foreignKey !== null) {
            Schema::table($tableName, function (Blueprint $table): void {
                // Column-form drop: SQLite can only drop FKs identified by
                // their columns (it rebuilds the table), MySQL resolves the
                // conventional name from them.
                $table->dropForeign(['user_id']);
            });
        }

        Schema::table($tableName, function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable(false)->change();
        });

        Schema::table($tableName, function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function userIdForeignKey(string $tableName): ?array
    {
        return collect(Schema::getForeignKeys($tableName))
            ->first(fn (array $foreignKey): bool => $foreignKey['columns'] === ['user_id']);
    }
};

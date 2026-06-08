<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (! $this->hasColumn('ris_orders', 'accession_number')) {
            DB::statement("ALTER TABLE ris_orders ADD COLUMN accession_number VARCHAR(64) NULL");
            DB::statement("ALTER TABLE ris_orders ADD UNIQUE INDEX ris_orders_accession_number_unique (accession_number)");
        }

        if (! $this->hasColumn('ris_orders', 'priority')) {
            DB::statement("ALTER TABLE ris_orders ADD COLUMN priority ENUM('routine','urgent','stat') NOT NULL DEFAULT 'routine'");
        }

        if (! $this->hasColumn('ris_orders', 'clinical_indication')) {
            DB::statement("ALTER TABLE ris_orders ADD COLUMN clinical_indication TEXT NULL");
        }

        if (! $this->hasColumn('ris_orders', 'requested_by_user_id')) {
            DB::statement("ALTER TABLE ris_orders ADD COLUMN requested_by_user_id BIGINT UNSIGNED NULL");
            DB::statement("ALTER TABLE ris_orders ADD INDEX ris_orders_requested_by_user_id_index (requested_by_user_id)");
            DB::statement("ALTER TABLE ris_orders ADD CONSTRAINT ris_orders_requested_by_user_id_foreign FOREIGN KEY (requested_by_user_id) REFERENCES users(id) ON DELETE SET NULL");
        }

        if (! $this->hasColumn('ris_orders', 'scheduled_at')) {
            DB::statement("ALTER TABLE ris_orders ADD COLUMN scheduled_at TIMESTAMP NULL DEFAULT NULL");
        }

        if (! $this->hasColumn('ris_orders', 'started_at')) {
            DB::statement("ALTER TABLE ris_orders ADD COLUMN started_at TIMESTAMP NULL DEFAULT NULL");
        }

        if (! $this->hasColumn('ris_orders', 'received_at')) {
            DB::statement("ALTER TABLE ris_orders ADD COLUMN received_at TIMESTAMP NULL DEFAULT NULL");
        }

        if (! $this->hasColumn('ris_orders', 'cancelled_at')) {
            DB::statement("ALTER TABLE ris_orders ADD COLUMN cancelled_at TIMESTAMP NULL DEFAULT NULL");
        }

        if (! $this->hasColumn('ris_orders', 'cancelled_reason')) {
            DB::statement("ALTER TABLE ris_orders ADD COLUMN cancelled_reason VARCHAR(255) NULL");
        }
    }

    public function down(): void
    {
        if ($this->hasColumn('ris_orders', 'requested_by_user_id')) {
            DB::statement("ALTER TABLE ris_orders DROP FOREIGN KEY ris_orders_requested_by_user_id_foreign");
            DB::statement("ALTER TABLE ris_orders DROP INDEX ris_orders_requested_by_user_id_index");
            DB::statement("ALTER TABLE ris_orders DROP COLUMN requested_by_user_id");
        }

        if ($this->hasColumn('ris_orders', 'accession_number')) {
            DB::statement("ALTER TABLE ris_orders DROP INDEX ris_orders_accession_number_unique");
            DB::statement("ALTER TABLE ris_orders DROP COLUMN accession_number");
        }

        foreach (['priority', 'clinical_indication', 'scheduled_at', 'started_at', 'received_at', 'cancelled_at', 'cancelled_reason'] as $column) {
            if ($this->hasColumn('ris_orders', $column)) {
                DB::statement("ALTER TABLE ris_orders DROP COLUMN {$column}");
            }
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        return DB::table('information_schema.COLUMNS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->exists();
    }
};

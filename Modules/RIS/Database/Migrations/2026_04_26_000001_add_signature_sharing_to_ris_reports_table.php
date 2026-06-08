<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('ris_reports')) {
            return;
        }

        if (! $this->hasColumn('ris_reports', 'signing_physician_name')) {
            DB::statement('ALTER TABLE ris_reports ADD COLUMN signing_physician_name VARCHAR(191) NULL');
        }

        if (! $this->hasColumn('ris_reports', 'pdf_path')) {
            DB::statement('ALTER TABLE ris_reports ADD COLUMN pdf_path VARCHAR(255) NULL');
        }

        if (! $this->hasColumn('ris_reports', 'share_token')) {
            DB::statement('ALTER TABLE ris_reports ADD COLUMN share_token VARCHAR(80) NULL');
            DB::statement('ALTER TABLE ris_reports ADD UNIQUE INDEX ris_reports_share_token_unique (share_token)');
        }

        if (! $this->hasColumn('ris_reports', 'share_url')) {
            DB::statement('ALTER TABLE ris_reports ADD COLUMN share_url VARCHAR(500) NULL');
        }

        if (! $this->hasColumn('ris_reports', 'share_expires_at')) {
            DB::statement('ALTER TABLE ris_reports ADD COLUMN share_expires_at TIMESTAMP NULL DEFAULT NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('ris_reports')) {
            return;
        }

        if ($this->hasColumn('ris_reports', 'share_token')) {
            DB::statement('ALTER TABLE ris_reports DROP INDEX ris_reports_share_token_unique');
        }

        foreach (['share_expires_at', 'share_url', 'share_token', 'pdf_path', 'signing_physician_name'] as $column) {
            if ($this->hasColumn('ris_reports', $column)) {
                DB::statement("ALTER TABLE ris_reports DROP COLUMN {$column}");
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

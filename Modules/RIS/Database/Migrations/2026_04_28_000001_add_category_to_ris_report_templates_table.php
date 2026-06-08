<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('ris_report_templates')) {
            return;
        }

        if (! $this->hasColumn('ris_report_templates', 'category')) {
            DB::statement('ALTER TABLE ris_report_templates ADD COLUMN category VARCHAR(120) NULL AFTER title');
        }

        DB::table('ris_report_templates')
            ->whereNull('category')
            ->orWhere('category', '')
            ->update(['category' => 'Général']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('ris_report_templates')) {
            return;
        }

        if ($this->hasColumn('ris_report_templates', 'category')) {
            DB::statement('ALTER TABLE ris_report_templates DROP COLUMN category');
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
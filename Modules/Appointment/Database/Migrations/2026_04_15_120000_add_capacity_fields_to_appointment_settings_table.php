<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $columnExists = static function (string $table, string $column): bool {
            $dbName = DB::getDatabaseName();
            return DB::table('information_schema.columns')
                ->where('table_schema', $dbName)
                ->where('table_name', $table)
                ->where('column_name', $column)
                ->exists();
        };

        if (! $columnExists('appointment_settings', 'emergency_slots_per_day')) {
            Schema::table('appointment_settings', function (Blueprint $table): void {
                $table->unsignedTinyInteger('emergency_slots_per_day')->default(0)->after('queue_service_id');
            });
        }

        if (! $columnExists('appointment_settings', 'weekly_revenue_target')) {
            Schema::table('appointment_settings', function (Blueprint $table): void {
                $table->decimal('weekly_revenue_target', 12, 2)->default(0)->after('emergency_slots_per_day');
            });
        }

        if (! $columnExists('appointment_settings', 'capacity_exceptions')) {
            Schema::table('appointment_settings', function (Blueprint $table): void {
                $table->longText('capacity_exceptions')->nullable()->after('weekly_revenue_target');
            });
        }

        if (! $columnExists('appointment_settings', 'external_sync_enabled')) {
            Schema::table('appointment_settings', function (Blueprint $table): void {
                $table->boolean('external_sync_enabled')->default(false)->after('capacity_exceptions');
            });
        }

        if (! $columnExists('appointment_settings', 'external_sync_provider')) {
            Schema::table('appointment_settings', function (Blueprint $table): void {
                $table->string('external_sync_provider', 30)->nullable()->after('external_sync_enabled');
            });
        }
    }

    public function down(): void
    {
        $columnExists = static function (string $table, string $column): bool {
            $dbName = DB::getDatabaseName();
            return DB::table('information_schema.columns')
                ->where('table_schema', $dbName)
                ->where('table_name', $table)
                ->where('column_name', $column)
                ->exists();
        };

        foreach ([
            'external_sync_provider',
            'external_sync_enabled',
            'capacity_exceptions',
            'weekly_revenue_target',
            'emergency_slots_per_day',
        ] as $column) {
            if ($columnExists('appointment_settings', $column)) {
                Schema::table('appointment_settings', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }
};

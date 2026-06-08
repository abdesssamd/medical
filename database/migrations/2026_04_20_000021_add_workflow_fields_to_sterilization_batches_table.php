<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $database = DB::getDatabaseName();
        $hasColumn = static function (string $column) use ($database): bool {
            return DB::table('information_schema.COLUMNS')
                ->where('TABLE_SCHEMA', $database)
                ->where('TABLE_NAME', 'sterilization_batches')
                ->where('COLUMN_NAME', $column)
                ->exists();
        };

        Schema::table('sterilization_batches', function (Blueprint $table) use ($hasColumn): void {
            if (! $hasColumn('sterility_validity_days')) {
                $table->unsignedInteger('sterility_validity_days')->default(7)->after('status');
            }

            if (! $hasColumn('bowie_dick_passed')) {
                $table->boolean('bowie_dick_passed')->default(false)->after('sterility_validity_days');
            }

            if (! $hasColumn('helix_passed')) {
                $table->boolean('helix_passed')->default(false)->after('bowie_dick_passed');
            }

            if (! $hasColumn('validated_at')) {
                $table->timestamp('validated_at')->nullable()->after('helix_passed');
            }
        });
    }

    public function down(): void
    {
        $database = DB::getDatabaseName();

        Schema::table('sterilization_batches', function (Blueprint $table) use ($database): void {
            $columns = [
                'sterility_validity_days',
                'bowie_dick_passed',
                'helix_passed',
                'validated_at',
            ];

            $existing = array_values(array_filter($columns, static function (string $column) use ($database): bool {
                return DB::table('information_schema.COLUMNS')
                    ->where('TABLE_SCHEMA', $database)
                    ->where('TABLE_NAME', 'sterilization_batches')
                    ->where('COLUMN_NAME', $column)
                    ->exists();
            }));
            if (! empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
};

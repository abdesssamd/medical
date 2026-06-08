<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $database = DB::getDatabaseName();

        $columnExists = static function (string $column) use ($database): bool {
            return DB::table('information_schema.COLUMNS')
                ->where('TABLE_SCHEMA', $database)
                ->where('TABLE_NAME', 'users')
                ->where('COLUMN_NAME', $column)
                ->exists();
        };

        $foreignExists = static function (string $constraintName) use ($database): bool {
            return DB::table('information_schema.TABLE_CONSTRAINTS')
                ->where('CONSTRAINT_SCHEMA', $database)
                ->where('TABLE_NAME', 'users')
                ->where('CONSTRAINT_NAME', $constraintName)
                ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
                ->exists();
        };

        if (! $columnExists('specialty_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('specialty_id')->nullable()->after('role');
            });
        }

        if (! $columnExists('professional_title')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('professional_title')->nullable()->after('name');
            });
        }

        if (! $columnExists('phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone')->nullable()->after('email');
            });
        }

        if (! $columnExists('organization_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('organization_id')->nullable()->after('specialty_id');
            });
        }

        if (! $foreignExists('users_specialty_id_foreign')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('specialty_id')->references('id')->on('specialties')->nullOnDelete();
            });
        }

        if (! $foreignExists('users_organization_id_foreign')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('organization_id')->references('id')->on('organizations')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['specialty_id']);
            $table->dropForeign(['organization_id']);
            
            $table->dropColumn([
                'specialty_id',
                'professional_title',
                'phone',
                'organization_id',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE ris_modalities MODIFY type VARCHAR(50) NOT NULL DEFAULT 'DX'");
        DB::statement("UPDATE ris_modalities SET type = 'DX' WHERE type = 'radio'");
        DB::statement("UPDATE ris_modalities SET type = 'CT' WHERE type = 'scanner'");
        DB::statement("UPDATE ris_modalities SET type = 'PX' WHERE type = 'panoramique'");

        Schema::table('ris_modalities', function (Blueprint $table): void {
            $table->string('description', 255)->nullable()->after('type');
            $table->string('ae_title', 64)->nullable()->change();
            $table->string('ip_address', 45)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::statement("UPDATE ris_modalities SET type = 'radio' WHERE type = 'DX'");
        DB::statement("UPDATE ris_modalities SET type = 'scanner' WHERE type = 'CT'");
        DB::statement("UPDATE ris_modalities SET type = 'panoramique' WHERE type = 'PX'");

        Schema::table('ris_modalities', function (Blueprint $table): void {
            $table->dropColumn('description');
        });
    }
};

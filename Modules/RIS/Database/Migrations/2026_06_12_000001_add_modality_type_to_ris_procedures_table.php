<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ris_procedures', function (Blueprint $table): void {
            $table->string('modality_type', 50)->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('ris_procedures', function (Blueprint $table): void {
            $table->dropColumn('modality_type');
        });
    }
};

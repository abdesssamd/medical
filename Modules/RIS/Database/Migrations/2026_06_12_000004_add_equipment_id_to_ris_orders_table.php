<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ris_orders', function (Blueprint $table) {
            $table->foreignId('equipment_id')->nullable()->after('modality_id')
                ->constrained('ris_equipments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ris_orders', function (Blueprint $table) {
            $table->dropForeign(['equipment_id']);
            $table->dropColumn('equipment_id');
        });
    }
};

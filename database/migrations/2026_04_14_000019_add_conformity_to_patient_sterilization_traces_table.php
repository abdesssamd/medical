<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_sterilization_traces', function (Blueprint $table): void {
            $table->boolean('is_conformity_ok')->default(true)->after('scanned_at');
            $table->string('conformity_issue', 191)->nullable()->after('is_conformity_ok');
        });
    }

    public function down(): void
    {
        Schema::table('patient_sterilization_traces', function (Blueprint $table): void {
            $table->dropColumn(['is_conformity_ok', 'conformity_issue']);
        });
    }
};


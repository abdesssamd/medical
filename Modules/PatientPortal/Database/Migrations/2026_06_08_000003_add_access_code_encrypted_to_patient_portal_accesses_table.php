<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_portal_accesses', function (Blueprint $table): void {
            if (! Schema::hasColumn('patient_portal_accesses', 'access_code_encrypted')) {
                $table->text('access_code_encrypted')->nullable()->after('access_code_hash');
            }
        });
    }

    public function down(): void
    {
        Schema::table('patient_portal_accesses', function (Blueprint $table): void {
            if (Schema::hasColumn('patient_portal_accesses', 'access_code_encrypted')) {
                $table->dropColumn('access_code_encrypted');
            }
        });
    }
};

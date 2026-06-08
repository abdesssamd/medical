<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('display_screens', function (Blueprint $table): void {
            $table->string('tv_primary_color', 20)->default('#1d4ed8')->after('adhkar_text');
            $table->string('tv_secondary_color', 20)->default('#0f172a')->after('tv_primary_color');
        });
    }

    public function down(): void
    {
        Schema::table('display_screens', function (Blueprint $table): void {
            $table->dropColumn(['tv_primary_color', 'tv_secondary_color']);
        });
    }
};

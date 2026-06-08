<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('display_screens', function (Blueprint $table): void {
            $table->boolean('adhkar_enabled')->default(false)->after('audio_repeat');
            $table->text('adhkar_text')->nullable()->after('adhkar_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('display_screens', function (Blueprint $table): void {
            $table->dropColumn(['adhkar_enabled', 'adhkar_text']);
        });
    }
};

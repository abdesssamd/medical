<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('display_screens', function (Blueprint $table): void {
            $table->boolean('audio_enabled')->default(true)->after('video_url');
            $table->string('audio_order', 10)->default('fr_ar')->after('audio_enabled');
            $table->unsignedTinyInteger('audio_repeat')->default(1)->after('audio_order');
        });
    }

    public function down(): void
    {
        Schema::table('display_screens', function (Blueprint $table): void {
            $table->dropColumn(['audio_enabled', 'audio_order', 'audio_repeat']);
        });
    }
};

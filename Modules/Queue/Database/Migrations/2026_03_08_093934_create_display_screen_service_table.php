<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('display_screen_service', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('display_screen_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['display_screen_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('display_screen_service');
    }
};

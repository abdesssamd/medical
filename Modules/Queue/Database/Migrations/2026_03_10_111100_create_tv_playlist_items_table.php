<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tv_playlist_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('display_screen_id')->nullable()->constrained('display_screens')->nullOnDelete();
            $table->string('title', 180);
            $table->enum('type', ['video', 'image', 'message']);
            $table->string('media_url', 500)->nullable();
            $table->string('message', 1000)->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('days', 32)->nullable();
            $table->unsignedInteger('sort_order')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['organization_id', 'is_active']);
            $table->index(['display_screen_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tv_playlist_items');
    }
};

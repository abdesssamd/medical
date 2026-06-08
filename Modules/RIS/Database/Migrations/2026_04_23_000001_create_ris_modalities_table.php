<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ris_modalities', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->enum('type', ['radio', 'scanner', 'panoramique']);
            $table->string('ae_title')->unique();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ris_modalities');
    }
};

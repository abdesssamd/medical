<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ris_equipments', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 191);
            $table->string('modality_type', 50)->nullable()->comment('radio, scanner, panoramique');
            $table->foreignId('modality_id')->nullable()->constrained('ris_modalities')->nullOnDelete();
            $table->string('ae_title', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('location', 191)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ris_equipments');
    }
};

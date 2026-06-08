<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appointment_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('professional_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('default_commission_amount', 10, 2)->default(20);
            $table->string('currency', 3)->default('MAD');
            $table->boolean('allow_secretary_edit')->default(true);
            $table->boolean('allow_secretary_cancel')->default(true);
            $table->string('timezone', 64)->default('Europe/Paris');
            $table->timestamps();

            $table->unique('professional_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_settings');
    }
};

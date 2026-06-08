<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plannings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('professional_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 0 (Sunday) ... 6 (Saturday)
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('consultation_minutes')->default(20);
            $table->unsignedInteger('max_patients_per_day')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['professional_id', 'day_of_week']);
            $table->index(['professional_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plannings');
    }
};

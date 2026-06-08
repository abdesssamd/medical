<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ris_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('ris_orders')->cascadeOnDelete();
            $table->text('content');
            $table->foreignId('signing_physician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->unique('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ris_reports');
    }
};

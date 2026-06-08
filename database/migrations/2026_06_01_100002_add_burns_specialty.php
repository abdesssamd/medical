<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('specialties')->where('code', 'BURNS')->exists();

        if (! $exists) {
            DB::table('specialties')->insert([
                'code' => 'BURNS',
                'name' => 'Chirurgie des Brûlés',
                'name_ar' => 'جراحة الحروق',
                'default_color' => '#ef4444',
                'default_duration_minutes' => 45,
                'description' => 'Spécialité chirurgicale dédiée à la prise en charge des brûlures, réanimation hydrique et greffes de peau.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('specialties')->where('code', 'BURNS')->delete();
    }
};

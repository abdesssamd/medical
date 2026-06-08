<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('specialties')->where('code', 'DENTAL')->exists();

        if (! $exists) {
            DB::table('specialties')->insert([
                'code' => 'DENTAL',
                'name' => 'Médecine Dentaire',
                'name_ar' => 'طب الأسنان',
                'default_color' => '#3b82f6',
                'default_duration_minutes' => 30,
                'description' => 'Spécialité médicale dédiée à la santé bucco-dentaire, aux soins dentaires et à l\'orthodontie.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('specialties')->where('code', 'DENTAL')->delete();
    }
};

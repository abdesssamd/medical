<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('specialties')->insert([
            'code' => 'INTMED',
            'name' => 'Médecine Interne',
            'name_ar' => 'الطب الباطني',
            'default_color' => '#6366f1',
            'default_duration_minutes' => 45,
            'description' => 'Spécialité dédiée aux maladies chroniques complexes et polypathologies.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('specialties')->where('code', 'INTMED')->delete();
    }
};

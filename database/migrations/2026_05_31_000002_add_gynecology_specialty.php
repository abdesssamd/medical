<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('specialties')->where('code', 'GYNECO')->exists();

        if (! $exists) {
            DB::table('specialties')->insert([
                'code' => 'GYNECO',
                'name' => 'Gynécologie-Obstétrique',
                'name_ar' => 'أمراض النساء والتوليد',
                'default_color' => '#ec4899',
                'default_duration_minutes' => 30,
                'description' => 'Spécialité médicale dédiée à la santé reproductive féminine, au suivi de grossesse et à l\'obstétrique.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('specialties')->where('code', 'GYNECO')->delete();
    }
};

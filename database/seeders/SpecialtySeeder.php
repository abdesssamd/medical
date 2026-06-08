<?php

namespace Database\Seeders;

use App\Models\Specialty;
use Illuminate\Database\Seeder;

class SpecialtySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $specialties = [
            [
                'code' => 'OMNI',
                'name' => 'Omnipraticien',
                'name_ar' => 'طبيب عام',
                'default_color' => '#3b82f6', // Blue
                'default_duration_minutes' => 30,
                'description' => 'Soins dentaires généraux, consultations, détarrage',
            ],
            [
                'code' => 'ORTHO',
                'name' => 'Orthodontiste',
                'name_ar' => 'أخصائي تقويم الأسنان',
                'default_color' => '#8b5cf6', // Purple
                'default_duration_minutes' => 45,
                'description' => 'Appareils orthodontiques, alignement dentaire',
            ],
            [
                'code' => 'CHIR',
                'name' => 'Chirurgien Dentiste',
                'name_ar' => 'جراح أسنان',
                'default_color' => '#ef4444', // Red
                'default_duration_minutes' => 120,
                'description' => 'Chirurgie buccale, extractions complexes, implants',
            ],
            [
                'code' => 'ENDO',
                'name' => 'Endodontiste',
                'name_ar' => 'أخصائي علاج الجذور',
                'default_color' => '#10b981', // Green
                'default_duration_minutes' => 60,
                'description' => 'Traitement de canal, pulpectomie',
            ],
            [
                'code' => 'PARO',
                'name' => 'Parodontiste',
                'name_ar' => 'أخصائي اللثة',
                'default_color' => '#f59e0b', // Amber
                'default_duration_minutes' => 45,
                'description' => 'Traitement des gencives, parodontologie',
            ],
            [
                'code' => 'PEDO',
                'name' => 'Pédodontiste',
                'name_ar' => 'أخصائي أسنان الأطفال',
                'default_color' => '#ec4899', // Pink
                'default_duration_minutes' => 30,
                'description' => 'Soins dentaires pour enfants',
            ],
        ];

        foreach ($specialties as $specialty) {
            Specialty::updateOrCreate(
                ['code' => $specialty['code']],
                $specialty
            );
        }

        $this->command->info('Specialties seeded successfully!');
    }
}

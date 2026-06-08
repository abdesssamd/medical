<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('specialties')->where('code', 'PEDIA')->exists();

        if (! $exists) {
            DB::table('specialties')->insert([
                'code' => 'PEDIA',
                'name' => 'Pédiatrie',
                'name_ar' => 'طب الأطفال',
                'default_color' => '#06b6d4',
                'default_duration_minutes' => 20,
                'description' => 'Spécialité médicale dédiée à la santé de l\'enfant, de la naissance à l\'adolescence.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $vaccines = [
            ['code' => 'BCG', 'name' => 'BCG', 'name_ar' => 'السل', 'disease' => 'Tuberculose', 'disease_ar' => 'السل', 'recommended_age_months' => 0, 'dose_number' => 1, 'total_doses' => 1, 'route' => 'intradermal', 'site' => 'left_arm', 'sort_order' => 1],
            ['code' => 'HEPB_BIRTH', 'name' => 'Hépatite B (naissance)', 'name_ar' => 'التهاب الكبد ب (عند الولادة)', 'disease' => 'Hépatite B', 'disease_ar' => 'التهاب الكبد ب', 'recommended_age_months' => 0, 'dose_number' => 1, 'total_doses' => 1, 'route' => 'intramuscular', 'site' => 'thigh', 'sort_order' => 2],
            ['code' => 'PENTA_1', 'name' => 'Pentavalent D1', 'name_ar' => 'الخماسي الجرعة 1', 'disease' => 'DTC + Hépatite B + Hib', 'disease_ar' => 'الخناق والكزاز والسعال الديكي والتهاب الكبد ب والمستدمية', 'recommended_age_months' => 2, 'dose_number' => 1, 'total_doses' => 3, 'route' => 'intramuscular', 'site' => 'thigh', 'sort_order' => 3],
            ['code' => 'OPV_1', 'name' => 'VPO D1', 'name_ar' => 'شلل الأطفال الفموي الجرعة 1', 'disease' => 'Poliomyélite', 'disease_ar' => 'شلل الأطفال', 'recommended_age_months' => 2, 'dose_number' => 1, 'total_doses' => 4, 'route' => 'oral', 'site' => 'oral', 'sort_order' => 4],
            ['code' => 'PCV_1', 'name' => 'Pneumocoque D1', 'name_ar' => 'المكورات الرئوية الجرعة 1', 'disease' => 'Infections à pneumocoque', 'disease_ar' => 'التهابات المكورات الرئوية', 'recommended_age_months' => 2, 'dose_number' => 1, 'total_doses' => 3, 'route' => 'intramuscular', 'site' => 'thigh', 'sort_order' => 5],
            ['code' => 'ROTA_1', 'name' => 'Rotavirus D1', 'name_ar' => 'الروتا الجرعة 1', 'disease' => 'Gastro-entérite à rotavirus', 'disease_ar' => 'التهاب المعدة والأمعاء بالروتا', 'recommended_age_months' => 2, 'dose_number' => 1, 'total_doses' => 3, 'route' => 'oral', 'site' => 'oral', 'sort_order' => 6],
            ['code' => 'PENTA_2', 'name' => 'Pentavalent D2', 'name_ar' => 'الخماسي الجرعة 2', 'disease' => 'DTC + Hépatite B + Hib', 'disease_ar' => 'الخناق والكزاز والسعال الديكي والتهاب الكبد ب والمستدمية', 'recommended_age_months' => 4, 'dose_number' => 2, 'total_doses' => 3, 'route' => 'intramuscular', 'site' => 'thigh', 'sort_order' => 7],
            ['code' => 'OPV_2', 'name' => 'VPO D2', 'name_ar' => 'شلل الأطفال الفموي الجرعة 2', 'disease' => 'Poliomyélite', 'disease_ar' => 'شلل الأطفال', 'recommended_age_months' => 4, 'dose_number' => 2, 'total_doses' => 4, 'route' => 'oral', 'site' => 'oral', 'sort_order' => 8],
            ['code' => 'PCV_2', 'name' => 'Pneumocoque D2', 'name_ar' => 'المكورات الرئوية الجرعة 2', 'disease' => 'Infections à pneumocoque', 'disease_ar' => 'التهابات المكورات الرئوية', 'recommended_age_months' => 4, 'dose_number' => 2, 'total_doses' => 3, 'route' => 'intramuscular', 'site' => 'thigh', 'sort_order' => 9],
            ['code' => 'ROTA_2', 'name' => 'Rotavirus D2', 'name_ar' => 'الروتا الجرعة 2', 'disease' => 'Gastro-entérite à rotavirus', 'disease_ar' => 'التهاب المعدة والأمعاء بالروتا', 'recommended_age_months' => 4, 'dose_number' => 2, 'total_doses' => 3, 'route' => 'oral', 'site' => 'oral', 'sort_order' => 10],
            ['code' => 'PENTA_3', 'name' => 'Pentavalent D3', 'name_ar' => 'الخماسي الجرعة 3', 'disease' => 'DTC + Hépatite B + Hib', 'disease_ar' => 'الخناق والكزاز والسعال الديكي والتهاب الكبد ب والمستدمية', 'recommended_age_months' => 6, 'dose_number' => 3, 'total_doses' => 3, 'route' => 'intramuscular', 'site' => 'thigh', 'sort_order' => 11],
            ['code' => 'OPV_3', 'name' => 'VPO D3', 'name_ar' => 'شلل الأطفال الفموي الجرعة 3', 'disease' => 'Poliomyélite', 'disease_ar' => 'شلل الأطفال', 'recommended_age_months' => 6, 'dose_number' => 3, 'total_doses' => 4, 'route' => 'oral', 'site' => 'oral', 'sort_order' => 12],
            ['code' => 'PCV_3', 'name' => 'Pneumocoque D3', 'name_ar' => 'المكورات الرئوية الجرعة 3', 'disease' => 'Infections à pneumocoque', 'disease_ar' => 'التهابات المكورات الرئوية', 'recommended_age_months' => 6, 'dose_number' => 3, 'total_doses' => 3, 'route' => 'intramuscular', 'site' => 'thigh', 'sort_order' => 13],
            ['code' => 'ROTA_3', 'name' => 'Rotavirus D3', 'name_ar' => 'الروتا الجرعة 3', 'disease' => 'Gastro-entérite à rotavirus', 'disease_ar' => 'التهاب المعدة والأمعاء بالروتا', 'recommended_age_months' => 6, 'dose_number' => 3, 'total_doses' => 3, 'route' => 'oral', 'site' => 'oral', 'sort_order' => 14],
            ['code' => 'MEASLES_1', 'name' => 'Rougeole D1', 'name_ar' => 'الحصبة الجرعة 1', 'disease' => 'Rougeole', 'disease_ar' => 'الحصبة', 'recommended_age_months' => 9, 'dose_number' => 1, 'total_doses' => 2, 'route' => 'subcutaneous', 'site' => 'arm', 'sort_order' => 15],
            ['code' => 'VITAMIN_A_1', 'name' => 'Vitamine A (6-11 mois)', 'name_ar' => 'فيتامين أ (6-11 شهر)', 'disease' => 'Carence en vitamine A', 'disease_ar' => 'نقص فيتامين أ', 'recommended_age_months' => 6, 'dose_number' => 1, 'total_doses' => 1, 'route' => 'oral', 'site' => 'oral', 'sort_order' => 16],
            ['code' => 'MEASLES_2', 'name' => 'Rougeole D2', 'name_ar' => 'الحصبة الجرعة 2', 'disease' => 'Rougeole', 'disease_ar' => 'الحصبة', 'recommended_age_months' => 18, 'dose_number' => 2, 'total_doses' => 2, 'route' => 'subcutaneous', 'site' => 'arm', 'sort_order' => 17],
            ['code' => 'OPV_BOOSTER', 'name' => 'VPO Rappel', 'name_ar' => 'شلل الأطفال المعزز', 'disease' => 'Poliomyélite', 'disease_ar' => 'شلل الأطفال', 'recommended_age_months' => 18, 'dose_number' => 4, 'total_doses' => 4, 'route' => 'oral', 'site' => 'oral', 'sort_order' => 18],
            ['code' => 'DTC_BOOSTER', 'name' => 'DTC Rappel', 'name_ar' => 'الخناق والكزاز والسعال الديكي المعزز', 'disease' => 'Diphtérie, Tétanos, Coqueluche', 'disease_ar' => 'الخناق والكزاز والسعال الديكي', 'recommended_age_months' => 18, 'dose_number' => 4, 'total_doses' => 4, 'route' => 'intramuscular', 'site' => 'thigh', 'sort_order' => 19],
            ['code' => 'MMR', 'name' => 'ROR (Rougeole-Oreillons-Rubéole)', 'name_ar' => 'الحصبة والنكاف والحصبة الألمانية', 'disease' => 'Rougeole, Oreillons, Rubéole', 'disease_ar' => 'الحصبة والنكاف والحصبة الألمانية', 'recommended_age_months' => 12, 'dose_number' => 1, 'total_doses' => 2, 'route' => 'subcutaneous', 'site' => 'arm', 'sort_order' => 20],
            ['code' => 'VARICELLA', 'name' => 'Varicelle', 'name_ar' => 'جدري الماء', 'disease' => 'Varicelle', 'disease_ar' => 'جدري الماء', 'recommended_age_months' => 12, 'dose_number' => 1, 'total_doses' => 1, 'route' => 'subcutaneous', 'site' => 'arm', 'is_mandatory' => false, 'sort_order' => 21],
            ['code' => 'HEPA', 'name' => 'Hépatite A', 'name_ar' => 'التهاب الكبد أ', 'disease' => 'Hépatite A', 'disease_ar' => 'التهاب الكبد أ', 'recommended_age_months' => 12, 'dose_number' => 1, 'total_doses' => 2, 'route' => 'intramuscular', 'site' => 'arm', 'is_mandatory' => false, 'sort_order' => 22],
            ['code' => 'HPV_1', 'name' => 'HPV D1 (filles)', 'name_ar' => 'فيروس الورم الحليمي الجرعة 1', 'disease' => 'Cancer du col utérin', 'disease_ar' => 'سرطان عنق الرحم', 'recommended_age_months' => 108, 'dose_number' => 1, 'total_doses' => 2, 'route' => 'intramuscular', 'site' => 'arm', 'is_mandatory' => false, 'sort_order' => 23],
            ['code' => 'HPV_2', 'name' => 'HPV D2 (filles)', 'name_ar' => 'فيروس الورم الحليمي الجرعة 2', 'disease' => 'Cancer du col utérin', 'disease_ar' => 'سرطان عنق الرحم', 'recommended_age_months' => 114, 'dose_number' => 2, 'total_doses' => 2, 'route' => 'intramuscular', 'site' => 'arm', 'is_mandatory' => false, 'sort_order' => 24],
        ];

        foreach ($vaccines as $vaccine) {
            $exists = DB::table('vaccines')->where('code', $vaccine['code'])->exists();
            if (! $exists) {
                DB::table('vaccines')->insert(array_merge($vaccine, [
                    'is_mandatory' => $vaccine['is_mandatory'] ?? true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    public function down(): void
    {
        DB::table('specialties')->where('code', 'PEDIA')->delete();
        DB::table('vaccines')->truncate();
    }
};

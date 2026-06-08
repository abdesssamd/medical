<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Appointment\Models\Planning;
use Modules\Appointment\Models\Setting;
use Modules\Queue\Models\Organization;
use Modules\Queue\Models\Service as QueueService;

class AppointmentDemoSeeder extends Seeder
{
    public function run(): void
    {
        $hospitalOrgId = Organization::where('code', 'HOSP-001')->value('id');
        $queueServiceId = QueueService::where('code', 'CONS')->value('id');

        $professional = User::firstOrCreate(
            ['email' => 'pro@rdv.local'],
            [
                'name' => 'Professional Demo',
                'role' => 'professional',
                'organization_id' => $hospitalOrgId,
                'password' => Hash::make('password'),
            ]
        );

        if (! $professional->organization_id && $hospitalOrgId) {
            $professional->update(['organization_id' => $hospitalOrgId]);
        }

        User::firstOrCreate(
            ['email' => 'secretary@rdv.local'],
            [
                'name' => 'Secretary Demo',
                'role' => 'secretary',
                'password' => Hash::make('password'),
            ]
        );

        Setting::updateOrCreate(
            ['professional_id' => $professional->id],
            [
                'default_commission_amount' => 25,
                'currency' => 'MAD',
                'allow_secretary_edit' => true,
                'allow_secretary_cancel' => true,
                'timezone' => 'Europe/Paris',
                'queue_service_id' => $queueServiceId,
            ]
        );

        foreach ([1, 2, 3, 4, 5] as $day) {
            Planning::updateOrCreate(
                ['professional_id' => $professional->id, 'day_of_week' => $day],
                [
                    'start_time' => '09:00:00',
                    'end_time' => '17:00:00',
                    'consultation_minutes' => 20,
                    'max_patients_per_day' => 18,
                    'is_active' => true,
                ]
            );
        }
    }
}

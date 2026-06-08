<?php

namespace Database\Seeders;

use Modules\Queue\Models\Agent;
use Modules\Queue\Models\AppSetting;
use Modules\Queue\Models\Counter;
use Modules\Queue\Models\DisplayScreen;
use Modules\Queue\Models\Kiosk;
use Modules\Queue\Models\Organization;
use Modules\Queue\Models\Service;
use Modules\Queue\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoQueueSeeder extends Seeder
{
    public function run(): void
    {
        AppSetting::setValue('tv_display_template', 'classic');

        // Seeder idempotent: if base queue organizations already exist, do not recreate demo dataset.
        if (Organization::where('code', 'CITY-001')->exists()) {
            User::firstOrCreate(
                ['email' => 'admin@queue.local'],
                [
                    'organization_id' => null,
                    'name' => 'Super Admin',
                    'role' => 'super_admin',
                    'password' => Hash::make('password'),
                ]
            );

            return;
        }

        $mairie = Organization::create([
            'name' => 'Mairie Centrale',
            'name_ar' => 'Al Baladiya Markaziya',
            'code' => 'CITY-001',
            'type' => 'mairie',
            'address' => 'Centre-ville',
            'primary_color' => '#0f766e',
        ]);

        $hospital = Organization::create([
            'name' => 'Hopital Regional',
            'name_ar' => 'Mustachfa Jihaoui',
            'code' => 'HOSP-001',
            'type' => 'hopital',
            'address' => 'Avenue Principale',
            'primary_color' => '#1d4ed8',
        ]);

        User::firstOrCreate(
            ['email' => 'admin@queue.local'],
            [
                'organization_id' => null,
                'name' => 'Super Admin',
                'role' => 'super_admin',
                'password' => Hash::make('password'),
            ]
        );

        $cityServices = [
            ['name' => 'Etat civil', 'name_ar' => 'Etat madani', 'code' => 'ETAT', 'prefix' => 'A', 'average_service_minutes' => 7],
            ['name' => 'Urbanisme', 'name_ar' => 'Tiamir', 'code' => 'URB', 'prefix' => 'B', 'average_service_minutes' => 10],
            ['name' => 'Comptabilite', 'name_ar' => 'Mohasaba', 'code' => 'COMPTA', 'prefix' => 'C', 'average_service_minutes' => 9],
        ];

        $hospitalServices = [
            ['name' => 'Urgences', 'name_ar' => 'Mostaajalat', 'code' => 'URG', 'prefix' => 'U', 'average_service_minutes' => 5],
            ['name' => 'Consultation', 'name_ar' => 'Isticharat', 'code' => 'CONS', 'prefix' => 'D', 'average_service_minutes' => 12],
            ['name' => 'Radiologie', 'name_ar' => 'Achiaa', 'code' => 'RAD', 'prefix' => 'R', 'average_service_minutes' => 15],
        ];

        $cityContext = $this->seedOrganization($mairie, $cityServices, ['Guichet 1', 'Guichet 2', 'Guichet 3']);
        $hospitalContext = $this->seedOrganization($hospital, $hospitalServices, ['Box 1', 'Box 2', 'Box 3']);

        $cityScreen = DisplayScreen::create([
            'organization_id' => $mairie->id,
            'name' => 'TV Hall Mairie',
            'code' => 'TV-CITY-01',
            'location' => 'Hall principal',
            'video_url' => '/videos/city.mp4',
            'audio_enabled' => true,
            'audio_order' => 'ar_fr',
            'audio_repeat' => 2,
            'adhkar_enabled' => true,
            'adhkar_text' => 'سبحان الله | الحمد لله | الله أكبر',
            'tv_primary_color' => '#1D4ED8',
            'tv_secondary_color' => '#0F172A',
            'is_active' => true,
        ]);
        $cityScreen->services()->sync($cityContext['services']->take(2)->pluck('id'));

        $hospitalScreen = DisplayScreen::create([
            'organization_id' => $hospital->id,
            'name' => 'TV Hall Hopital',
            'code' => 'TV-HOSP-01',
            'location' => 'Accueil',
            'video_url' => '/videos/hospital.mp4',
            'audio_enabled' => true,
            'audio_order' => 'fr_ar',
            'audio_repeat' => 1,
            'adhkar_enabled' => false,
            'adhkar_text' => null,
            'tv_primary_color' => '#0F766E',
            'tv_secondary_color' => '#0F172A',
            'is_active' => true,
        ]);
        $hospitalScreen->services()->sync($hospitalContext['services']->pluck('id'));

        Kiosk::create([
            'organization_id' => $mairie->id,
            'name' => 'Borne Mairie 1',
            'code' => 'BK-CITY-01',
            'location' => 'Entree principale',
            'is_active' => true,
        ]);
        Kiosk::create([
            'organization_id' => $hospital->id,
            'name' => 'Borne Hopital 1',
            'code' => 'BK-HOSP-01',
            'location' => 'Accueil',
            'is_active' => true,
        ]);
    }

    private function seedOrganization(Organization $organization, array $servicesData, array $counterNames): array
    {
        $services = collect($servicesData)->map(fn (array $data) => Service::create($data + [
            'organization_id' => $organization->id,
            'is_active' => true,
        ]));

        $counters = collect($counterNames)->map(function (string $name, int $index) use ($organization): Counter {
            return Counter::create([
                'organization_id' => $organization->id,
                'name' => $name,
                'name_ar' => 'Chobbak '.($index + 1),
                'code' => 'C'.($index + 1),
                'location' => 'Bloc '.($index + 1),
                'is_active' => true,
            ]);
        });

        foreach ($counters as $counter) {
            $counter->services()->sync($services->pluck('id'));
        }

        $agents = collect(['Agent 1', 'Agent 2', 'Agent 3'])->map(function (string $name, int $index) use ($organization): Agent {
            $email = strtolower(str_replace(' ', '', $name)).'@'.$organization->code.'.local';

            $user = User::create([
                'organization_id' => $organization->id,
                'name' => $name,
                'email' => $email,
                'role' => 'agent',
                'password' => Hash::make('password'),
            ]);

            return Agent::create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'name' => $name,
                'email' => $email,
                'phone' => '06000000'.($index + 1),
                'is_active' => true,
            ]);
        });

        foreach ($agents as $index => $agent) {
            $primary = $counters[$index % $counters->count()];
            $agent->counters()->attach($primary->id, ['is_primary' => true]);

            if ($index === 0 && isset($counters[1])) {
                $agent->counters()->attach($counters[1]->id, ['is_primary' => false]);
            }
        }

        $services->each(function (Service $service): void {
            for ($i = 1; $i <= 6; $i++) {
                Ticket::create([
                    'organization_id' => $service->organization_id,
                    'service_id' => $service->id,
                    'ticket_date' => today(),
                    'sequence_number' => $i,
                    'ticket_number' => sprintf('%s-%03d', $service->prefix, $i),
                    'status' => $i <= 2 ? 'served' : 'waiting',
                    'estimated_wait_minutes' => max(0, (6 - $i) * $service->average_service_minutes),
                    'arrived_at' => now()->subMinutes(45 - ($i * 4)),
                    'called_at' => $i <= 2 ? now()->subMinutes(20 - $i) : null,
                    'served_at' => $i <= 2 ? now()->subMinutes(10 - $i) : null,
                ]);
            }
        });

        return [
            'services' => $services,
            'counters' => $counters,
            'agents' => $agents,
        ];
    }
}


<?php

namespace Database\Seeders;

use App\Models\Specialty;
use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\ClinicalRecord\Models\Questionnaire;

class QuestionnaireSeeder extends Seeder
{
    public function run(): void
    {
        $specialtyId = Specialty::where('code', 'GEN')->value('id') ?? Specialty::first()?->id;
        $creatorId = User::query()->value('id');

        Questionnaire::updateOrCreate(
            ['name' => 'Formulaire Grippe'],
            [
                'specialty_id' => $specialtyId,
                'practitioner_id' => null,
                'group_name' => 'General',
                'created_by' => $creatorId,
                'description' => 'Questionnaire standard pour syndrome grippal et triage clinique.',
                'field_schema' => [
                    [
                        'key' => 'symptom_onset_date',
                        'label' => 'Date d apparition',
                        'type' => 'date',
                        'required' => true,
                    ],
                    [
                        'key' => 'temperature_c',
                        'label' => 'Temperature',
                        'type' => 'number',
                        'required' => true,
                        'step' => '0.1',
                    ],
                    [
                        'key' => 'cough_type',
                        'label' => 'Type de toux',
                        'type' => 'select',
                        'required' => true,
                        'options' => ['Sèche', 'Grasse'],
                    ],
                    [
                        'key' => 'current_treatment',
                        'label' => 'Traitement en cours',
                        'type' => 'textarea',
                        'required' => false,
                    ],
                ],
                'is_active' => true,
            ]
        );
    }
}
